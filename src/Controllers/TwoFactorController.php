<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\User;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\TwoFactorService;

class TwoFactorController
{
    /**
     * 2FA-Verifizierungsformular anzeigen (nach Passwort-Login)
     */
    public function verifyForm(): void
    {
        if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_user_id'])) {
            App::redirect('/login');
            return;
        }

        CsrfMiddleware::generateToken();
        $method = $_SESSION['2fa_method'] ?? 'totp';

        View::render('auth/two-factor-verify', [
            'title' => 'Zwei-Faktor-Authentifizierung',
            'method' => $method,
        ], 'auth');
    }

    /**
     * 2FA-Code verifizieren
     */
    public function verify(): void
    {
        if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_user_id'])) {
            App::redirect('/login');
            return;
        }

        $userId = (int) $_SESSION['2fa_user_id'];
        $method = $_SESSION['2fa_method'] ?? 'totp';
        $code = trim($_POST['code'] ?? '');
        $useRecovery = !empty($_POST['use_recovery']);

        if (empty($code)) {
            App::setFlash('error', 'Bitte geben Sie einen Code ein.');
            App::redirect('/two-factor/verify');
            return;
        }

        // Rate-Limiting prüfen
        if (TwoFactorService::isLockedOut($userId)) {
            App::setFlash('error', 'Zu viele fehlgeschlagene Versuche. Bitte warten Sie 15 Minuten.');
            App::redirect('/two-factor/verify');
            return;
        }

        $verified = false;

        if ($useRecovery) {
            $verified = TwoFactorService::verifyRecoveryCode($userId, $code);
        } elseif ($method === 'totp') {
            $user = User::findById($userId);
            if ($user && !empty($user['two_factor_secret'])) {
                $verified = TwoFactorService::verifyTotpCode($user['two_factor_secret'], $code);
            }
        } elseif ($method === 'email') {
            $verified = TwoFactorService::verifyEmailCode($userId, $code);
        }

        if (!$verified) {
            TwoFactorService::logFailedAttempt($userId);
            App::setFlash('error', 'Ungültiger Code. Bitte versuchen Sie es erneut.');
            App::redirect('/two-factor/verify');
            return;
        }

        // 2FA erfolgreich - volle Session setzen
        $user = User::findById($userId);
        self::completeLogin($user);

        if ($user['must_change_password']) {
            App::redirect('/change-password');
            return;
        }

        App::redirect('/dashboard');
    }

    /**
     * Code erneut senden (nur E-Mail)
     */
    public function resendCode(): void
    {
        if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_user_id'])) {
            App::redirect('/login');
            return;
        }

        $userId = (int) $_SESSION['2fa_user_id'];
        $method = $_SESSION['2fa_method'] ?? '';

        if ($method === 'email') {
            TwoFactorService::generateEmailCode($userId);
            App::setFlash('success', 'Ein neuer Code wurde an Ihre E-Mail-Adresse gesendet.');
        }

        App::redirect('/two-factor/verify');
    }

    /**
     * 2FA-Einrichtungsseite anzeigen
     */
    public function setupForm(): void
    {
        CsrfMiddleware::generateToken();

        $user = User::findById($_SESSION['user_id']);

        View::render('auth/two-factor-setup', [
            'title' => 'Zwei-Faktor-Authentifizierung einrichten',
            'user' => $user,
            'currentMethod' => $user['two_factor_method'] ?? 'none',
            'isConfirmed' => !empty($user['two_factor_confirmed_at']),
        ]);
    }

    /**
     * 2FA-Methode wählen und Einrichtung starten
     */
    public function setup(): void
    {
        $method = $_POST['method'] ?? '';

        if (!in_array($method, ['email', 'totp'])) {
            App::setFlash('error', 'Ungültige 2FA-Methode.');
            App::redirect('/two-factor/setup');
            return;
        }

        $user = User::findById($_SESSION['user_id']);

        if ($method === 'email') {
            if (empty($user['email'])) {
                App::setFlash('error', 'Sie benötigen eine hinterlegte E-Mail-Adresse für die E-Mail-basierte 2FA.');
                App::redirect('/two-factor/setup');
                return;
            }

            // E-Mail-2FA direkt aktivieren + Recovery-Codes generieren
            $recoveryCodes = TwoFactorService::generateRecoveryCodes();
            $hashedCodes = TwoFactorService::hashRecoveryCodes($recoveryCodes);
            \OpenClassbook\Database::execute(
                'UPDATE users SET two_factor_method = ?, two_factor_confirmed_at = NOW(), two_factor_recovery_codes = ? WHERE id = ?',
                ['email', $hashedCodes, $user['id']]
            );

            $_SESSION['recovery_codes'] = $recoveryCodes;
            App::setFlash('success', 'E-Mail-basierte 2FA wurde aktiviert.');
            App::redirect('/two-factor/recovery-codes');
            return;
        }

        if ($method === 'totp') {
            // TOTP-Secret generieren
            $totpData = TwoFactorService::generateTotpSecret($user['username']);
            $encryptedSecret = TwoFactorService::encryptSecret($totpData['secret']);

            // Temporaer in Session speichern (noch nicht bestaetigt)
            $_SESSION['totp_setup'] = [
                'encrypted_secret' => $encryptedSecret,
                'qr_svg' => $totpData['qr_svg'],
                'manual_key' => $totpData['manual_key'],
            ];

            CsrfMiddleware::generateToken();
            View::render('auth/two-factor-setup-totp', [
                'title' => 'Authenticator-App einrichten',
                'qr_svg' => $totpData['qr_svg'],
                'manual_key' => $totpData['manual_key'],
            ]);
            return;
        }
    }

    /**
     * TOTP-Setup mit Testcode bestaetigen
     */
    public function confirmTotp(): void
    {
        if (empty($_SESSION['totp_setup'])) {
            App::setFlash('error', 'Kein TOTP-Setup aktiv.');
            App::redirect('/two-factor/setup');
            return;
        }

        $code = trim($_POST['code'] ?? '');
        $encryptedSecret = $_SESSION['totp_setup']['encrypted_secret'];

        if (empty($code)) {
            App::setFlash('error', 'Bitte geben Sie den Code aus Ihrer Authenticator-App ein.');
            App::redirect('/two-factor/setup');
            return;
        }

        if (!TwoFactorService::verifyTotpCode($encryptedSecret, $code)) {
            App::setFlash('error', 'Ungültiger Code. Bitte versuchen Sie es erneut.');

            // Setup-Daten beibehalten und erneut anzeigen
            CsrfMiddleware::generateToken();
            View::render('auth/two-factor-setup-totp', [
                'title' => 'Authenticator-App einrichten',
                'qr_svg' => $_SESSION['totp_setup']['qr_svg'],
                'manual_key' => $_SESSION['totp_setup']['manual_key'],
            ]);
            return;
        }

        // TOTP bestaetigt - in DB speichern
        $userId = $_SESSION['user_id'];
        $recoveryCodes = TwoFactorService::generateRecoveryCodes();
        $hashedCodes = TwoFactorService::hashRecoveryCodes($recoveryCodes);

        \OpenClassbook\Database::execute(
            'UPDATE users SET two_factor_method = ?, two_factor_secret = ?, two_factor_confirmed_at = NOW(), two_factor_recovery_codes = ? WHERE id = ?',
            ['totp', $encryptedSecret, $hashedCodes, $userId]
        );

        unset($_SESSION['totp_setup']);
        $_SESSION['recovery_codes'] = $recoveryCodes;

        App::setFlash('success', 'Authenticator-App wurde erfolgreich eingerichtet.');
        App::redirect('/two-factor/recovery-codes');
    }

    /**
     * Recovery-Codes anzeigen
     */
    public function recoveryCodes(): void
    {
        $codes = $_SESSION['recovery_codes'] ?? null;

        View::render('auth/two-factor-recovery-codes', [
            'title' => 'Recovery-Codes',
            'codes' => $codes,
        ]);
    }

    /**
     * Neue Recovery-Codes generieren
     */
    public function regenerateCodes(): void
    {
        $userId = $_SESSION['user_id'];
        $recoveryCodes = TwoFactorService::generateRecoveryCodes();
        $hashedCodes = TwoFactorService::hashRecoveryCodes($recoveryCodes);

        \OpenClassbook\Database::execute(
            'UPDATE users SET two_factor_recovery_codes = ? WHERE id = ?',
            [$hashedCodes, $userId]
        );

        $_SESSION['recovery_codes'] = $recoveryCodes;
        App::setFlash('success', 'Neue Recovery-Codes wurden generiert.');
        App::redirect('/two-factor/recovery-codes');
    }

    /**
     * 2FA deaktivieren
     */
    public function disable(): void
    {
        $userId = $_SESSION['user_id'];

        // Prüfen ob 2FA für die Rolle erzwungen wird
        $user = User::findById($userId);
        $enforcedRoles = TwoFactorService::getEnforcedRoles();

        if (in_array($user['role'], $enforcedRoles)) {
            App::setFlash('error', 'Die Zwei-Faktor-Authentifizierung ist für Ihre Rolle verpflichtend und kann nicht deaktiviert werden.');
            App::redirect('/two-factor/setup');
            return;
        }

        \OpenClassbook\Database::execute(
            'UPDATE users SET two_factor_method = ?, two_factor_secret = NULL, two_factor_confirmed_at = NULL, two_factor_recovery_codes = NULL WHERE id = ?',
            ['none', $userId]
        );

        unset($_SESSION['recovery_codes']);
        App::setFlash('success', 'Zwei-Faktor-Authentifizierung wurde deaktiviert.');
        App::redirect('/two-factor/setup');
    }

    /**
     * Volle Session nach erfolgreicher 2FA setzen
     */
    private static function completeLogin(array $user): void
    {
        // Temporaere 2FA-Session-Daten löschen
        unset($_SESSION['2fa_pending'], $_SESSION['2fa_user_id'], $_SESSION['2fa_method']);

        // Session-ID regenerieren
        session_regenerate_id(true);

        // Volle Session setzen
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $_SESSION['session_version'] = (int) ($user['session_version'] ?? 0);
        $_SESSION['last_activity'] = time();

        User::updateLastLogin($user['id']);
    }
}
