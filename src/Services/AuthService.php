<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;
use OpenClassbook\Database;
use OpenClassbook\Models\User;

class AuthService
{
    /**
     * Login-Versuch pruefen und ausfuehren
     */
    public static function attempt(string $username, string $password): array
    {
        // Brute-Force-Schutz pruefen
        if (self::isLockedOut($username)) {
            return ['success' => false, 'message' => 'Zu viele fehlgeschlagene Versuche. Bitte warten Sie 15 Minuten.'];
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::logAttempt($username, false);
            return ['success' => false, 'message' => 'Benutzername oder Passwort ungueltig.'];
        }

        if (!$user['active']) {
            return ['success' => false, 'message' => 'Ihr Account wurde deaktiviert.'];
        }

        // Erfolgreichen Login protokollieren
        self::logAttempt($username, true);
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
     * Passwort-Komplexitaet pruefen
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
     * Pruefen ob ein Benutzer gesperrt ist
     */
    private static function isLockedOut(string $username): bool
    {
        $maxAttempts = App::config('security.max_login_attempts') ?? 5;
        $lockoutDuration = App::config('security.lockout_duration') ?? 900;

        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM login_attempts
             WHERE username = ? AND successful = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$username, $lockoutDuration]
        );

        return ($result['cnt'] ?? 0) >= $maxAttempts;
    }

    /**
     * Login-Versuch protokollieren
     */
    private static function logAttempt(string $username, bool $successful): void
    {
        Database::execute(
            'INSERT INTO login_attempts (username, ip_address, successful) VALUES (?, ?, ?)',
            [$username, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', $successful ? 1 : 0]
        );
    }

    /**
     * Passwort-Reset-Token generieren
     */
    public static function createResetToken(string $email): ?string
    {
        $user = Database::queryOne('SELECT id FROM users WHERE email = ? AND active = 1', [$email]);

        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $lifetime = App::config('security.password_reset_token_lifetime') ?? 3600;
        $expires = new \DateTime('+' . $lifetime . ' seconds');

        User::setResetToken($user['id'], hash('sha256', $token), $expires);

        return $token;
    }
}
