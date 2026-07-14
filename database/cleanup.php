<?php

/**
 * Datenbank-Cleanup-Skript (Löschkonzept / Aufbewahrungsfristen).
 *
 * Führt alle Löschroutinen des RetentionService aus:
 *   - Nachrichten (1:1 und Gruppen) nach Ablauf der Aufbewahrungsfrist
 *   - Audit-Log-Einträge
 *   - Login-Versuche
 *   - abgelaufene Passwort-Reset-Token
 *   - alte Rate-Limit-Einträge
 *
 * Die Fristen sind in den Admin-Einstellungen bzw. in config('security.*')
 * konfigurierbar (Wert 0 = deaktiviert). Funktioniert unabhängig vom
 * MariaDB-Event-Scheduler.
 *
 * Verwendung:
 *   php database/cleanup.php
 *
 * Empfohlener Cronjob (stündlich):
 *   0 * * * * php /pfad/zu/open-classbook/database/cleanup.php >/dev/null 2>&1
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Services\RetentionService;

try {
    $result = RetentionService::purge();

    echo "Cleanup abgeschlossen.\n";
    echo "  - Nachrichten (1:1) gelöscht:        {$result['messages']}\n";
    echo "  - Nachrichten (Gruppen) gelöscht:    {$result['group_messages']}\n";
    echo "  - Audit-Log-Einträge gelöscht:       {$result['audit_log']}\n";
    echo "  - Login-Versuche gelöscht:           {$result['login_attempts']}\n";
    echo "  - Abgelaufene Reset-Token entfernt:  {$result['reset_tokens']}\n";
    echo "  - Alte Rate-Limit-Einträge entfernt: {$result['rate_limits']}\n";
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Cleanup fehlgeschlagen: " . $e->getMessage() . "\n");
    exit(1);
}
