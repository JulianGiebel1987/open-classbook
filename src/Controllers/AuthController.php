<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\Database;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\User;
use OpenClassbook\Services\AuthService;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\NotificationService;

class AuthController
{
    public function loginForm(): void
    {
        if (App::isLoggedIn()) {
            App::redirect('/dashboard');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('auth/login', ['title' => 'Anmelden'], 'auth');
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            App::setFlash('error', 'Bitte Benutzername und Passwort eingeben.');
            App::redirect('/login');
            return;
        }

        $result = AuthService::attempt($username, $password);

        if (!$result['success']) {
            App::setFlash('error', $result['message']);
            App::redirect('/login');
            return;
        }

        // 2FA erforderlich - Redirect zur Code-Eingabe
        if (!empty($result['requires_2fa'])) {
            App::redirect('/two-factor/verify');
            return;
        }

        // 2FA-Setup erzwungen (Rolle erfordert 2FA, aber noch nicht eingerichtet)
        if (!empty($result['requires_2fa_setup'])) {
            if ($result['must_change_password']) {
                App::redirect('/change-password');
                return;
            }
            App::setFlash('warning', 'Für Ihre Rolle ist die Zwei-Faktor-Authentifizierung verpflichtend. Bitte richten Sie diese jetzt ein.');
            App::redirect('/two-factor/setup');
            return;
        }

        if ($result['must_change_password']) {
            App::redirect('/change-password');
            return;
        }

        App::redirect('/dashboard');
    }

    public function logout(): void
    {
        AuthService::logout();
        session_start();
        App::setFlash('success', 'Sie wurden erfolgreich abgemeldet.');
        App::redirect('/login');
    }

    public function changePasswordForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('auth/change-password', [
            'title' => 'Passwort ändern',
            'forced' => (bool) ($_SESSION['user']['must_change_password'] ?? false),
        ]);
    }

    public function changePassword(): void
    {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            App::setFlash('error', 'Die Passwörter stimmen nicht überein.');
            App::redirect('/change-password');
            return;
        }

        $errors = AuthService::validatePassword($newPassword);
        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/change-password');
            return;
        }

        $user = User::findById($_SESSION['user_id']);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            App::setFlash('error', 'Das aktuelle Passwort ist falsch.');
            App::redirect('/change-password');
            return;
        }

        User::updatePassword($user['id'], $newPassword);
        session_regenerate_id(true);
        App::setFlash('success', 'Passwort erfolgreich geändert.');
        App::redirect('/dashboard');
    }

    public function forgotPasswordForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('auth/forgot-password', ['title' => 'Passwort zurücksetzen'], 'auth');
    }

    public function forgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            App::setFlash('error', 'Bitte geben Sie Ihre E-Mail-Adresse ein.');
            App::redirect('/forgot-password');
            return;
        }

        // Route-spezifischer Rate-Limit pro IP: max N Anfragen pro Zeitfenster.
        // Bei Ueberschreitung wird die identische Erfolgsmeldung gezeigt (User-Enumeration
        // verhindert), aber KEIN Token generiert und KEINE Mail verschickt.
        if (self::isForgotPasswordRateLimited()) {
            Logger::info('Passwort-Reset-Rate-Limit fuer IP ueberschritten');
            App::setFlash('success', 'Wenn ein Account mit dieser E-Mail existiert, erhalten Sie eine E-Mail mit weiteren Anweisungen.');
            App::redirect('/login');
            return;
        }

        // Token generieren (gibt null zurück wenn E-Mail nicht gefunden oder User inaktiv)
        $token = AuthService::createResetToken($email);

        if ($token !== null) {
            $user = Database::queryOne(
                'SELECT id, username, email FROM users WHERE LOWER(email) = LOWER(?) AND active = 1',
                [$email]
            );

            if ($user !== null) {
                $resetUrl = rtrim(self::baseUrl(), '/') . '/reset-password/' . $token;
                NotificationService::sendPasswordResetMail($user['email'], $user['username'], $resetUrl);
                Logger::audit('password_reset_requested', (int) $user['id']);
            }
        } else {
            Logger::info('Passwort-Reset für unbekannte oder inaktive E-Mail angefordert');
        }

        // Immer gleiche Meldung anzeigen (verhindert User-Enumeration)
        App::setFlash('success', 'Wenn ein Account mit dieser E-Mail existiert, erhalten Sie eine E-Mail mit weiteren Anweisungen.');
        App::redirect('/login');
    }

    private static function isForgotPasswordRateLimited(): bool
    {
        $maxRequests = (int) (App::config('security.password_reset_rate_limit') ?? 3);
        $windowSeconds = (int) (App::config('security.password_reset_rate_window') ?? 3600);

        if ($maxRequests <= 0) {
            return false;
        }

        $endpoint = 'forgot-password';
        $ip = self::pseudonymizeIpForRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        // Aktuellen Versuch protokollieren
        Database::execute(
            'INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)',
            [$ip, $endpoint]
        );

        // Portabler Cutoff (MySQL + SQLite)
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM rate_limits
             WHERE ip_address = ? AND endpoint = ? AND requested_at > ?',
            [$ip, $endpoint, $cutoff]
        );

        return ((int) ($result['cnt'] ?? 0)) > $maxRequests;
    }

    private static function pseudonymizeIpForRateLimit(string $ip): string
    {
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . ':xxxx:xxxx:xxxx:xxxx';
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = 'xxx';
            return implode('.', $parts);
        }
        return 'unknown';
    }

    public static function baseUrl(): string
    {
        $configured = App::config('app.url');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    public function resetPasswordForm(string $token): void
    {
        $user = User::findByResetToken(hash('sha256', $token));

        if (!$user) {
            App::setFlash('error', 'Ungültiger oder abgelaufener Link.');
            App::redirect('/login');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('auth/reset-password', [
            'title' => 'Neues Passwort setzen',
            'token' => $token,
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = User::findByResetToken(hash('sha256', $token));

        if (!$user) {
            App::setFlash('error', 'Ungültiger oder abgelaufener Link.');
            App::redirect('/login');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            App::setFlash('error', 'Die Passwörter stimmen nicht überein.');
            App::redirect('/reset-password/' . $token);
            return;
        }

        $errors = AuthService::validatePassword($newPassword);
        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/reset-password/' . $token);
            return;
        }

        User::updatePassword($user['id'], $newPassword);
        User::clearResetToken($user['id']);
        // Wer den per E-Mail zugestellten Link öffnet, hat den Zugriff auf die
        // E-Mail-Adresse nachgewiesen -> als verifiziert markieren (verifiziert
        // zugleich neu eingeladene Konten). Eine evtl. ausstehende E-Mail-
        // Änderung bleibt davon unberührt.
        User::markEmailVerified((int) $user['id']);
        // Aktive Sessions invalidieren (z.B. bei kompromittierten Konten)
        User::incrementSessionVersion((int) $user['id']);
        Logger::audit('password_reset_completed', (int) $user['id']);

        App::setFlash('success', 'Passwort erfolgreich geändert. Sie können sich jetzt anmelden.');
        App::redirect('/login');
    }

    public function privacy(): void
    {
        $layout = App::isLoggedIn() ? 'main' : 'auth';
        View::render('auth/privacy', ['title' => 'Datenschutzhinweise'], $layout);
    }

    /** Rollen, die sich per E-Mail anmelden (alle ausser Schueler:innen). */
    private const EMAIL_LOGIN_ROLES = ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schulbegleiter'];

    /**
     * Formular zur Self-Service-Aenderung der eigenen E-Mail-Adresse.
     */
    public function emailChangeForm(): void
    {
        $user = User::findById((int) ($_SESSION['user_id'] ?? 0));
        if (!$user) {
            App::redirect('/login');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('account/email', [
            'title' => 'E-Mail-Adresse ändern',
            'user' => $user,
            'canChange' => in_array($user['role'], self::EMAIL_LOGIN_ROLES, true),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'E-Mail-Adresse ändern'],
            ]),
        ]);
    }

    /**
     * Aenderung anfordern: neue Adresse validieren, Bestaetigungslink (Double-
     * Opt-in) an die NEUE Adresse senden. Erst nach Bestaetigung wird sie aktiv.
     */
    public function requestEmailChange(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $user = User::findById($userId);
        if (!$user) {
            App::redirect('/login');
            return;
        }

        if (!in_array($user['role'], self::EMAIL_LOGIN_ROLES, true)) {
            App::setFlash('error', 'Für Ihr Konto steht die E-Mail-Änderung nicht zur Verfügung.');
            App::redirect('/dashboard');
            return;
        }

        $newEmail = strtolower(trim($_POST['new_email'] ?? ''));

        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($newEmail) > 255) {
            App::setFlash('error', 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
            App::redirect('/account/email');
            return;
        }

        if ($newEmail === strtolower((string) ($user['email'] ?? ''))) {
            App::setFlash('error', 'Das ist bereits Ihre aktuelle E-Mail-Adresse.');
            App::redirect('/account/email');
            return;
        }

        if (User::emailExists($newEmail, $userId) || User::usernameExists($newEmail, $userId)) {
            App::setFlash('error', 'Diese E-Mail-Adresse ist bereits vergeben.');
            App::redirect('/account/email');
            return;
        }

        if (!App::config('mail.enabled')) {
            App::setFlash('error', 'Der E-Mail-Versand ist nicht konfiguriert. Bitte wenden Sie sich an die Administration.');
            App::redirect('/account/email');
            return;
        }

        $token = bin2hex(random_bytes(32));
        $lifetime = App::config('security.email_verification_token_lifetime') ?? 86400;
        $expires = new \DateTime('+' . $lifetime . ' seconds');
        User::setEmailVerificationToken($userId, hash('sha256', $token), $expires, $newEmail);

        $confirmUrl = rtrim(self::baseUrl(), '/') . '/account/email/confirm/' . $token;
        NotificationService::sendEmailChangeConfirmationMail($newEmail, (string) $user['username'], $confirmUrl);
        if (!empty($user['email'])) {
            NotificationService::sendEmailChangeNoticeMail((string) $user['email'], (string) $user['username'], $newEmail);
        }

        Logger::audit('email_change_requested', $userId, 'User', $userId, 'Neue Adresse angefordert: ' . $newEmail);

        App::setFlash('success', 'Wir haben einen Bestätigungslink an die neue Adresse gesendet. Bitte bestätigen Sie die Änderung dort. Bis dahin gilt Ihre bisherige Adresse.');
        App::redirect('/account/email');
    }

    /**
     * Bestaetigung der E-Mail-Aenderung ueber den zugesandten Link (oeffentlich,
     * da der Token selbst den Zugriff auf die neue Adresse nachweist).
     */
    public function confirmEmailChange(string $token): void
    {
        $user = User::findByEmailVerificationToken(hash('sha256', $token));

        if (!$user || empty($user['pending_email'])) {
            App::setFlash('error', 'Ungültiger oder abgelaufener Bestätigungslink.');
            App::redirect('/login');
            return;
        }

        User::applyPendingEmail((int) $user['id']);
        // Anmeldename hat sich geändert -> alle aktiven Sitzungen invalidieren.
        User::incrementSessionVersion((int) $user['id']);
        Logger::audit('email_change_confirmed', (int) $user['id'], 'User', (int) $user['id']);

        App::setFlash('success', 'Ihre E-Mail-Adresse wurde geändert. Bitte melden Sie sich mit der neuen Adresse an.');
        App::redirect('/login');
    }
}
