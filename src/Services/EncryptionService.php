<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;

/**
 * Generische Verschluesselung ruhender Daten (Encryption at Rest).
 *
 * Verwendet AES-256-CBC (analog zu TwoFactorService), jedoch mit einem
 * eigenen Anwendungsschluessel, damit Schluesselrotation getrennt vom
 * 2FA-Schluessel moeglich ist.
 *
 * Verschluesselte Werte tragen das Praefix "enc:v1:". Werte ohne dieses
 * Praefix werden bei decrypt() unveraendert zurueckgegeben. Dadurch koennen
 * Bestandsdaten (Klartext) schrittweise und gefahrlos migriert werden.
 */
class EncryptionService
{
    private const PREFIX = 'enc:v1:';

    /**
     * Klartext verschluesseln. Ergebnis: "enc:v1:" + base64(iv(16) . ciphertext).
     */
    public static function encrypt(string $plain): string
    {
        $key = self::getEncryptionKey();
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            // Im Fehlerfall lieber Klartext speichern als Datenverlust riskieren.
            Logger::error('Verschluesselung fehlgeschlagen, Wert wird unverschluesselt gespeichert.');
            return $plain;
        }
        return self::PREFIX . base64_encode($iv . $ciphertext);
    }

    /**
     * Wert entschluesseln. Werte ohne "enc:v1:"-Praefix (Legacy-Klartext)
     * werden unveraendert zurueckgegeben.
     */
    public static function decrypt(string $value): string
    {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        $data = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($data === false || strlen($data) < 17) {
            Logger::warning('Entschluesselung fehlgeschlagen: ungueltiges Datenformat.');
            return $value;
        }

        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $plain = openssl_decrypt($ciphertext, 'aes-256-cbc', self::getEncryptionKey(), OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            Logger::warning('Entschluesselung fehlgeschlagen: falscher Schluessel oder beschaedigte Daten.');
            return $value;
        }

        return $plain;
    }

    /**
     * Prueft, ob ein Wert bereits mit diesem Dienst verschluesselt wurde.
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    /**
     * Verschluesselungsschluessel laden.
     * Reihenfolge:
     *   1. Explizit konfigurierter Key (security.app_encryption_key)
     *   2. Persistenter Auto-Key in storage/keys/app.key
     *      (wird einmalig mit 32 zufaelligen Bytes erzeugt, Datei 0600)
     */
    private static function getEncryptionKey(): string
    {
        $configured = App::config('security.app_encryption_key') ?? '';
        if (!empty($configured)) {
            $decoded = @hex2bin($configured);
            return $decoded !== false ? $decoded : hash('sha256', $configured, true);
        }

        return self::loadOrCreateKeyFile();
    }

    /**
     * Persistenten Auto-Key aus storage/keys/app.key laden;
     * falls nicht vorhanden, einmalig generieren und schreiben.
     */
    private static function loadOrCreateKeyFile(): string
    {
        $dir = __DIR__ . '/../../storage/keys';
        $path = $dir . '/app.key';

        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if ($raw !== false && strlen($raw) >= 32) {
                return substr($raw, 0, 32);
            }
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        $newKey = random_bytes(32);
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $newKey, LOCK_EX) === false || !@rename($tmp, $path)) {
            Logger::error('App-Verschluesselungsschluessel konnte nicht persistiert werden. Bitte security.app_encryption_key setzen.', ['path' => $path]);
            return $newKey;
        }
        @chmod($path, 0600);

        return $newKey;
    }
}
