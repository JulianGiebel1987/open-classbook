<?php

namespace OpenClassbook\Services;

use OpenClassbook\App;
use OpenClassbook\Database;
use OpenClassbook\Models\Setting;

/**
 * Loeschkonzept / Aufbewahrungsfristen (DSGVO Art. 5 Abs. 1 lit. e, Art. 17).
 *
 * Entfernt personenbezogene Daten nach Ablauf konfigurierbarer Fristen.
 * Wird cronbasiert ueber database/cleanup.php sowie manuell aus den
 * Admin-Einstellungen aufgerufen und funktioniert damit auch ohne
 * aktivierten MariaDB-Event-Scheduler.
 *
 * Fristen (in Tagen) werden vorrangig aus den in der Datenbank
 * gespeicherten Einstellungen gelesen, andernfalls aus config('security.*').
 * Der Wert 0 deaktiviert die jeweilige Loeschung.
 */
class RetentionService
{
    /**
     * Fuehrt alle Loeschroutinen aus.
     *
     * @return array<string,int> Anzahl geloeschter/aktualisierter Zeilen je Kategorie.
     */
    public static function purge(): array
    {
        $result = [
            'messages'         => 0,
            'group_messages'   => 0,
            'audit_log'        => 0,
            'login_attempts'   => 0,
            'reset_tokens'     => 0,
            'rate_limits'      => 0,
        ];

        $messagesDays = self::days('retention_messages_days', 730);
        if ($messagesDays > 0) {
            $result['messages'] = self::deleteOlderThan('messages', 'created_at', $messagesDays);
            $result['group_messages'] = self::deleteOlderThan('group_messages', 'created_at', $messagesDays);
        }

        $auditDays = self::days('retention_audit_days', 90);
        if ($auditDays > 0) {
            $result['audit_log'] = self::deleteOlderThan('audit_log', 'created_at', $auditDays);
        }

        $loginDays = self::days('retention_login_attempts_days', 30);
        if ($loginDays > 0) {
            $result['login_attempts'] = self::deleteOlderThan('login_attempts', 'attempted_at', $loginDays);
        }

        // Abgelaufene Passwort-Reset-Token nullen (unabhaengig von Fristen)
        $now = date('Y-m-d H:i:s');
        $result['reset_tokens'] = Database::execute(
            'UPDATE users
                SET password_reset_token = NULL,
                    password_reset_expires = NULL
              WHERE password_reset_expires IS NOT NULL
                AND password_reset_expires < ?',
            [$now]
        );

        // Alte Rate-Limit-Eintraege (>24h) entfernen (nur DDoS-Schutz)
        $result['rate_limits'] = Database::execute(
            'DELETE FROM rate_limits WHERE requested_at < ?',
            [date('Y-m-d H:i:s', strtotime('-24 hours'))]
        );

        Logger::audit(
            'retention_purge',
            null,
            'system',
            null,
            json_encode($result, JSON_UNESCAPED_UNICODE)
        );

        return $result;
    }

    /**
     * Loescht Zeilen einer Tabelle, deren Zeitstempel aelter als $days Tage ist.
     * Der Stichtag wird in PHP berechnet und als Parameter uebergeben (portabel
     * fuer MariaDB/MySQL und SQLite). Tabellen- und Spaltenname stammen
     * ausschliesslich aus internem, fest kodiertem Code (nie aus Nutzereingaben).
     */
    private static function deleteOlderThan(string $table, string $column, int $days): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return Database::execute(
            "DELETE FROM {$table} WHERE {$column} < ?",
            [$cutoff]
        );
    }

    /**
     * Frist in Tagen laden: zuerst aus den DB-Einstellungen, dann aus der
     * Konfiguration, sonst der uebergebene Default. Negative Werte werden auf 0
     * (deaktiviert) normalisiert.
     */
    private static function days(string $key, int $default): int
    {
        $setting = Setting::get($key);
        if ($setting !== null && $setting !== '') {
            return max(0, (int) $setting);
        }

        $configured = App::config('security.' . $key);
        if ($configured !== null) {
            return max(0, (int) $configured);
        }

        return max(0, $default);
    }
}
