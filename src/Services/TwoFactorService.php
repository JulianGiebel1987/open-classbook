<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;
use OpenClassbook\Database;
use OpenClassbook\Models\Setting;
use OpenClassbook\Models\User;
use OTPHP\TOTP;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class TwoFactorService
{
    /**
     * TOTP-Secret und QR-Code-URI generieren
     */
    public static function generateTotpSecret(string $username): array
    {
        $totp = TOTP::generate();
        $totp->setLabel($username);
        $totp->setIssuer(App::config('app.name') ?? 'Open-Classbook');

        $secret = $totp->getSecret();
        $provisioningUri = $totp->getProvisioningUri();

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 5,
            'addQuietzone' => true,
        ]);

        $qrSvg = (new QRCode($options))->render($provisioningUri);

        return [
            'secret' => $secret,
            'qr_svg' => $qrSvg,
            'manual_key' => $secret,
            'provisioning_uri' => $provisioningUri,
        ];
    }

    /**
     * TOTP-Code verifizieren
     */
    public static function verifyTotpCode(string $encryptedSecret, string $code): bool
    {
        $secret = self::decryptSecret($encryptedSecret);
        if ($secret === null) {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);
        // Zeitfenster ±1 Periode (30s) erlauben
        return $totp->verify($code, null, 1);
    }

    /**
     * E-Mail-Code generieren und versenden
     */
    public static function generateEmailCode(int $userId): ?string
    {
        $user = User::findById($userId);
        if (!$user || empty($user['email'])) {
            return null;
        }

        // Alte unverwendete Codes invalidieren
        Database::execute(
            'UPDATE two_factor_codes SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL',
            [$userId]
        );

        // 6-stelligen Code generieren
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Gehasht in DB speichern
        $lifetime = (int) (Setting::get('two_factor_code_lifetime') ?? 600);
        Database::execute(
            'INSERT INTO two_factor_codes (user_id, code, type, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))',
            [$userId, password_hash($code, PASSWORD_BCRYPT), 'email', $lifetime]
        );

        // Per E-Mail versenden
        NotificationService::sendTwoFactorCode($user['email'], $code);

        return $code;
    }

    /**
     * E-Mail-Code verifizieren
     */
    public static function verifyEmailCode(int $userId, string $code): bool
    {
        $entries = Database::query(
            'SELECT id, code FROM two_factor_codes WHERE user_id = ? AND type = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY created_at DESC LIMIT 5',
            [$userId, 'email']
        );

        foreach ($entries as $entry) {
            if (password_verify($code, $entry['code'])) {
                Database::execute(
                    'UPDATE two_factor_codes SET used_at = NOW() WHERE id = ?',
                    [$entry['id']]
                );
                return true;
            }
        }

        return false;
    }

    /**
     * Recovery-Codes generieren (Klartext)
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2));
        }
        return $codes;
    }

    /**
     * Recovery-Codes hashen fuer DB-Speicherung
     */
    public static function hashRecoveryCodes(array $codes): string
    {
        $hashed = [];
        foreach ($codes as $code) {
            $hashed[] = password_hash($code, PASSWORD_BCRYPT);
        }
        return json_encode($hashed);
    }

    /**
     * Recovery-Code verifizieren und verbrauchen
     */
    public static function verifyRecoveryCode(int $userId, string $code): bool
    {
        $user = User::findById($userId);
        if (!$user || empty($user['two_factor_recovery_codes'])) {
            return false;
        }

        $hashedCodes = json_decode($user['two_factor_recovery_codes'], true);
        if (!is_array($hashedCodes)) {
            return false;
        }

        $code = strtolower(trim($code));
        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Verwendeten Code entfernen
                unset($hashedCodes[$index]);
                $hashedCodes = array_values($hashedCodes);
                Database::execute(
                    'UPDATE users SET two_factor_recovery_codes = ? WHERE id = ?',
                    [json_encode($hashedCodes), $userId]
                );
                return true;
            }
        }

        return false;
    }

    /**
     * TOTP-Secret verschluesseln (AES-256-CBC)
     */
    public static function encryptSecret(string $secret): string
    {
        $key = self::getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($secret, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * TOTP-Secret entschluesseln
     */
    public static function decryptSecret(string $encrypted): ?string
    {
        $key = self::getEncryptionKey();
        $data = base64_decode($encrypted);
        if ($data === false || strlen($data) < 17) {
            return null;
        }

        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : null;
    }

    /**
     * Pruefen ob 2FA global aktiviert ist
     */
    public static function isEnabled(): bool
    {
        return Setting::get('two_factor_enabled') === '1';
    }

    /**
     * Rollen laden fuer die 2FA erzwungen wird
     */
    public static function getEnforcedRoles(): array
    {
        $roles = Setting::get('two_factor_enforce_roles');
        if (empty($roles)) {
            return [];
        }

        $decoded = json_decode($roles, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Pruefen ob Nutzer wegen zu vieler Fehlversuche gesperrt ist
     */
    public static function isLockedOut(int $userId): bool
    {
        $maxAttempts = (int) (Setting::get('two_factor_max_attempts') ?? 5);
        $lockoutDuration = App::config('security.lockout_duration') ?? 900;

        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM two_factor_codes WHERE user_id = ? AND type = ? AND used_at IS NULL AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$userId, 'email', $lockoutDuration]
        );

        // Wir zählen fehlgeschlagene Versuche über die login_attempts-Tabelle
        $attempts = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM login_attempts WHERE username = (SELECT username FROM users WHERE id = ?) AND successful = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)',
            [$userId, $lockoutDuration]
        );

        return ($attempts['cnt'] ?? 0) >= $maxAttempts;
    }

    /**
     * 2FA-Fehlversuch protokollieren
     */
    public static function logFailedAttempt(int $userId): void
    {
        $user = User::findById($userId);
        if ($user) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            // IP pseudonymisieren (DSGVO)
            if (str_contains($ip, ':')) {
                $parts = explode(':', $ip);
                $ip = implode(':', array_slice($parts, 0, 4)) . ':xxxx:xxxx:xxxx:xxxx';
            } else {
                $parts = explode('.', $ip);
                if (count($parts) === 4) {
                    $parts[3] = 'xxx';
                    $ip = implode('.', $parts);
                }
            }

            Database::execute(
                'INSERT INTO login_attempts (username, ip_address, successful) VALUES (?, ?, 0)',
                [$user['username'], $ip]
            );
        }
    }

    /**
     * Verschluesselungsschluessel laden
     */
    private static function getEncryptionKey(): string
    {
        $key = App::config('security.two_factor_encryption_key') ?? '';
        if (empty($key)) {
            // Fallback: SHA-256 Hash des Datenbank-Passworts als Key
            $key = hash('sha256', App::config('database.password') ?? 'open-classbook-default-key', true);
        } else {
            $key = hex2bin($key) ?: hash('sha256', $key, true);
        }
        return $key;
    }
}
