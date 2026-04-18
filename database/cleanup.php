<?php

/**
 * Datenbank-Cleanup-Skript
 *
 * Entfernt abgelaufene Passwort-Reset-Token und alte Rate-Limit-Eintraege.
 *
 * Verwendung:
 *   php database/cleanup.php
 *
 * Empfohlener Cronjob (stuendlich):
 *   0 * * * * php /pfad/zu/open-classbook/database/cleanup.php >/dev/null 2>&1
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;

try {
    $db = Database::getConnection();

    // 1) Abgelaufene Passwort-Reset-Token nullen
    $stmt = $db->prepare(
        'UPDATE users
            SET password_reset_token = NULL,
                password_reset_expires = NULL
          WHERE password_reset_expires IS NOT NULL
            AND password_reset_expires < NOW()'
    );
    $stmt->execute();
    $resetCleared = $stmt->rowCount();

    // 2) Alte Rate-Limit-Eintraege (>24h) entfernen
    $rateStmt = $db->prepare(
        'DELETE FROM rate_limits
          WHERE requested_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)'
    );
    $rateStmt->execute();
    $rateRemoved = $rateStmt->rowCount();

    echo "Cleanup abgeschlossen.\n";
    echo "  - Abgelaufene Reset-Token entfernt: {$resetCleared}\n";
    echo "  - Alte Rate-Limit-Eintraege entfernt: {$rateRemoved}\n";
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Cleanup fehlgeschlagen: " . $e->getMessage() . "\n");
    exit(1);
}
