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
     * Platzhalter fuer Werte, die zwar verschluesselt vorliegen, aber nicht mehr
     * entschluesselt werden koennen (verlorener/geaenderter Schluessel oder
     * beschaedigte Daten). So wird niemals der rohe "enc:v1:..."-Blob in der UI
     * angezeigt.
     */
    private const UNDECRYPTABLE_PLACEHOLDER = '[Nachricht konnte nicht entschlüsselt werden]';

    /**
     * Klartext verschluesseln. Ergebnis: "enc:v1:" + base64(iv(16) . ciphertext).
     */
    public static function encrypt(string $plain): string
    {
        $key = self::getEncryptionKey();
        if ($key === null) {
            // Ohne stabilen Schluessel wuerde jeder Request mit einem anderen
            // (fluechtigen) Schluessel verschluesseln und die Daten damit dauerhaft
            // unlesbar machen. Dann lieber Klartext speichern (bleibt lesbar) als
            // Datenverlust riskieren.
            Logger::error('Kein stabiler Verschluesselungsschluessel verfuegbar, Wert wird unverschluesselt gespeichert. Bitte security.app_encryption_key setzen oder storage/keys beschreibbar machen.');
            return $plain;
        }

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
     * werden unveraendert zurueckgegeben. Verschluesselte Werte, die nicht mehr
     * entschluesselt werden koennen, werden durch einen Platzhalter ersetzt,
     * damit niemals roher Chiffretext in der UI landet.
     */
    public static function decrypt(string $value): string
    {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        $key = self::getEncryptionKey();
        $data = base64_decode(substr($value, strlen(self::PREFIX)), true);
        if ($key === null || $data === false || strlen($data) < 17) {
            Logger::warning('Entschluesselung fehlgeschlagen: kein Schluessel oder ungueltiges Datenformat.');
            return self::UNDECRYPTABLE_PLACEHOLDER;
        }

        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $plain = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            Logger::warning('Entschluesselung fehlgeschlagen: falscher Schluessel oder beschaedigte Daten.');
            return self::UNDECRYPTABLE_PLACEHOLDER;
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
     *
     * Gibt null zurueck, wenn kein stabiler Schluessel ermittelt werden kann
     * (z.B. storage/keys nicht beschreibbar). Ein fluechtiger Schluessel wird
     * bewusst NICHT erzeugt, da er sich pro Request unterscheiden und damit alle
     * verschluesselten Nachrichten unlesbar machen wuerde.
     */
    private static function getEncryptionKey(): ?string
    {
        $configured = App::config('security.app_encryption_key') ?? '';
        if (!empty($configured)) {
            $decoded = @hex2bin($configured);
            return $decoded !== false ? $decoded : hash('sha256', $configured, true);
        }

        return self::loadOrCreateKeyFile();
    }

    /**
     * Persistenten Auto-Key aus storage/keys/app.key laden; falls nicht
     * vorhanden, einmalig generieren und atomar schreiben.
     *
     * Gibt null zurueck, wenn der Schluessel weder gelesen noch dauerhaft
     * gespeichert werden kann. Es wird bewusst kein fluechtiger Schluessel
     * zurueckgegeben.
     */
    private static function loadOrCreateKeyFile(): ?string
    {
        $dir = __DIR__ . '/../../storage/keys';
        $path = $dir . '/app.key';

        $existing = self::readKeyFile($path);
        if ($existing !== null) {
            return $existing;
        }

        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }

        // Eindeutiger Temp-Name, damit parallele Requests sich nicht gegenseitig
        // ueberschreiben.
        $newKey = random_bytes(32);
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $newKey, LOCK_EX) !== false && @rename($tmp, $path)) {
            @chmod($path, 0600);
            return $newKey;
        }
        @unlink($tmp);

        // Das Anlegen kann fehlschlagen, weil parallel bereits ein Schluessel
        // erzeugt wurde -> erneut versuchen zu lesen.
        $existing = self::readKeyFile($path);
        if ($existing !== null) {
            return $existing;
        }

        Logger::error('App-Verschluesselungsschluessel konnte nicht persistiert werden. Bitte security.app_encryption_key setzen oder storage/keys beschreibbar machen.', ['path' => $path]);
        return null;
    }

    /**
     * Schluesseldatei lesen; gibt die ersten 32 Bytes zurueck oder null,
     * wenn die Datei fehlt oder zu kurz/unlesbar ist.
     */
    private static function readKeyFile(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw !== false && strlen($raw) >= 32) {
            return substr($raw, 0, 32);
        }
        return null;
    }
}
