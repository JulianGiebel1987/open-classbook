<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;
use OpenClassbook\Database;
use OpenClassbook\Models\User;
use OpenClassbook\Models\Setting;

class AuthService
{
    /**
     * Login-Versuch prüfen und ausführen
     */
    public static function attempt(string $username, string $password): array
    {
        // Brute-Force-Schutz prüfen (IP-basiert, um Account-DoS zu verhindern)
        if (self::isLockedOut($username)) {
            return ['success' => false, 'message' => 'Zu viele fehlgeschlagene Versuche. Bitte warten Sie 15 Minuten.'];
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::logAttempt($username, false);
            return ['success' => false, 'message' => 'Benutzername oder Passwort ungültig.'];
        }

        if (!$user['active']) {
            return ['success' => false, 'message' => 'Ihr Account wurde deaktiviert.'];
        }

        // Erfolgreichen Login protokollieren
        self::logAttempt($username, true);

        // 2FA-Pruefung: Ist 2FA global aktiviert und hat der Nutzer 2FA eingerichtet?
        $twoFactorEnabled = Setting::get('two_factor_enabled') === '1';
        $userHas2fa = $user['two_factor_method'] !== 'none' && !empty($user['two_factor_confirmed_at']);

        if ($twoFactorEnabled && $userHas2fa) {
            // Temporaere 2FA-Session setzen (KEIN voller Login)
            session_regenerate_id(true);
            $_SESSION['2fa_pending'] = true;
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_method'] = $user['two_factor_method'];

            // Bei E-Mail-2FA sofort Code versenden
            if ($user['two_factor_method'] === 'email') {
                TwoFactorService::generateEmailCode($user['id']);
            }

            return [
                'success' => true,
                'requires_2fa' => true,
                'must_change_password' => (bool) $user['must_change_password'],
            ];
        }

        // 2FA erzwungen für diese Rolle?
        if ($twoFactorEnabled) {
            $enforcedRoles = TwoFactorService::getEnforcedRoles();
            if (in_array($user['role'], $enforcedRoles) && !$userHas2fa) {
                // Normalen Login durchfuehren, aber 2FA-Setup erzwingen
                User::updateLastLogin($user['id']);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];
                $_SESSION['session_version'] = (int) ($user['session_version'] ?? 0);
                $_SESSION['last_activity'] = time();

                return [
                    'success' => true,
                    'requires_2fa_setup' => true,
                    'must_change_password' => (bool) $user['must_change_password'],
                ];
            }
        }

        // Kein 2FA noetig - normaler Login
        User::updateLastLogin($user['id']);

        // Session-ID VOR dem Setzen der Daten regenerieren (Session-Fixation verhindern)
        session_regenerate_id(true);

        // Session setzen
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $_SESSION['session_version'] = (int) ($user['session_version'] ?? 0);
        $_SESSION['last_activity'] = time();

        return [
            'success' => true,
            'must_change_password' => (bool) $user['must_change_password'],
        ];
    }

    /**
     * Abmelden
     */
    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Passwort-Komplexität prüfen
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        $minLength = App::config('security.password_min_length') ?? 10;

        if (strlen($password) < $minLength) {
            $errors[] = "Das Passwort muss mindestens {$minLength} Zeichen lang sein.";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens einen Grossbuchstaben enthalten.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Das Passwort muss mindestens eine Ziffer enthalten.';
        }

        return $errors;
    }

    /**
     * Prueft, ob der Login-Versuch gesperrt ist.
     * Die Sperre basiert primaer auf der IP-Adresse (bzw. deren Pseudonym),
     * damit ein Angreifer nicht durch gezielte Fehlversuche fremde Accounts
     * aussperren kann. Bei nicht ermittelbarer IP wird auf Username-Scope
     * zurueckgefallen, damit der Brute-Force-Schutz erhalten bleibt.
     */
    private static function isLockedOut(string $username): bool
    {
        $maxAttempts = App::config('security.max_login_attempts') ?? 5;
        $lockoutDuration = App::config('security.lockout_duration') ?? 900;
        $cutoff = date('Y-m-d H:i:s', time() - (int) $lockoutDuration);
        $ip = self::pseudonymizeIp($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($ip === 'unknown') {
            $result = Database::queryOne(
                'SELECT COUNT(*) as cnt FROM login_attempts
                 WHERE username = ? AND successful = 0 AND attempted_at > ?',
                [$username, $cutoff]
            );
        } else {
            $result = Database::queryOne(
                'SELECT COUNT(*) as cnt FROM login_attempts
                 WHERE ip_address = ? AND successful = 0 AND attempted_at > ?',
                [$ip, $cutoff]
            );
        }

        return ($result['cnt'] ?? 0) >= $maxAttempts;
    }

    /**
     * Login-Versuch protokollieren
     */
    private static function logAttempt(string $username, bool $successful): void
    {
        Database::execute(
            'INSERT INTO login_attempts (username, ip_address, successful) VALUES (?, ?, ?)',
            [$username, self::pseudonymizeIp($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), $successful ? 1 : 0]
        );
    }

    /**
     * IP-Adresse pseudonymisieren (DSGVO Art. 5 Abs. 1 lit. e)
     */
    private static function pseudonymizeIp(string $ip): string
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

    /**
     * Passwort-Reset-Token generieren
     */
    public static function createResetToken(string $email): ?string
    {
        $user = Database::queryOne('SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND active = 1', [$email]);

        if (!$user) {
            return null;
        }

        return self::createResetTokenForUserId((int) $user['id']);
    }

    /**
     * Passwort-Reset-Token fuer einen bekannten Nutzer generieren
     * (z. B. Admin-initiierter Reset aus der Benutzerverwaltung).
     * Der Klartext-Token wird zurueckgegeben, nur der Hash wird gespeichert.
     */
    public static function createResetTokenForUserId(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $lifetime = App::config('security.password_reset_token_lifetime') ?? 3600;
        $expires = new \DateTime('+' . $lifetime . ' seconds');

        User::setResetToken($userId, hash('sha256', $token), $expires);

        return $token;
    }
}
