<?php

/**
 * Umstellung bestehender Lehrer-Accounts auf E-Mail als Anmeldename.
 *
 * Setzt bei allen Benutzern mit role='lehrer', die eine E-Mail-Adresse haben
 * und deren Benutzername noch nicht der E-Mail entspricht, den Benutzernamen
 * auf die (kleingeschriebene) E-Mail. Accounts, bei denen die Ziel-E-Mail
 * bereits von einem anderen Konto als Anmeldename belegt ist, werden
 * uebersprungen und als Konflikt gemeldet.
 *
 * Das Skript ist idempotent: ein erneuter Lauf stellt nichts mehr um.
 *
 * Verwendung: php database/backfill_teacher_usernames.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;

try {
    $teachers = Database::query(
        "SELECT id, username, email
         FROM users
         WHERE role = 'lehrer'
           AND email IS NOT NULL
           AND email <> ''
           AND username <> LOWER(email)
         ORDER BY username"
    );

    if (empty($teachers)) {
        echo "Keine Lehrer-Accounts zum Umstellen gefunden.\n";
        exit(0);
    }

    echo count($teachers) . " Lehrer-Account(s) zu pruefen ...\n";

    $updated = 0;
    $conflicts = 0;
    foreach ($teachers as $t) {
        $target = strtolower(trim($t['email']));

        // Ziel-Anmeldename bereits von einem ANDEREN Konto belegt?
        $existing = Database::queryOne(
            'SELECT id FROM users WHERE username = ? AND id <> ?',
            [$target, $t['id']]
        );
        if ($existing) {
            $conflicts++;
            echo "  KONFLIKT: {$t['username']} -> {$target} (bereits vergeben, uebersprungen)\n";
            continue;
        }

        Database::execute(
            'UPDATE users SET username = ? WHERE id = ?',
            [$target, $t['id']]
        );
        $updated++;
        echo "  OK: {$t['username']} -> {$target}\n";
    }

    echo "\nFertig: {$updated} umgestellt, {$conflicts} Konflikt(e).\n";
    if ($conflicts > 0) {
        echo "Bitte Konflikte manuell pruefen (doppelte E-Mail-Adressen).\n";
    }
} catch (\Throwable $e) {
    echo "Datenbankfehler: " . $e->getMessage() . "\n";
    exit(1);
}
