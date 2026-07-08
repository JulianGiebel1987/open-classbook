<?php

/**
 * Nachzug von Benutzerkonten fuer bestehende Schueler:innen ohne Account.
 *
 * Findet alle students mit user_id IS NULL, legt je ein Benutzerkonto
 * (role=schueler) an, verknuepft es und schreibt die einmalig sichtbaren
 * Zugangsdaten in eine Datei unter storage/. Das Skript ist idempotent:
 * bereits verknuepfte Schueler:innen werden uebersprungen.
 *
 * Verwendung: php database/backfill_student_accounts.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;
use OpenClassbook\Services\StudentService;

try {
    // Nur Schueler:innen ohne verknuepftes Konto (und nicht archiviert).
    $students = Database::query(
        'SELECT id, firstname, lastname, guardian_email
         FROM students
         WHERE user_id IS NULL AND archived_at IS NULL
         ORDER BY lastname, firstname'
    );

    if (empty($students)) {
        echo "Keine Schueler:innen ohne Benutzerkonto gefunden.\n";
        exit(0);
    }

    echo count($students) . " Schueler:in(nen) ohne Konto gefunden. Lege Konten an ...\n";

    $credentials = [];
    foreach ($students as $student) {
        try {
            $result = StudentService::createAccountForExistingStudent($student);
            $credentials[] = $result['credentials'];
            echo "  OK: {$result['credentials']['name']} -> {$result['credentials']['username']}\n";
        } catch (\Throwable $e) {
            echo "  FEHLER bei {$student['firstname']} {$student['lastname']}: " . $e->getMessage() . "\n";
        }
    }

    if (empty($credentials)) {
        echo "Es wurden keine Konten angelegt.\n";
        exit(1);
    }

    // Zugangsdaten in eine geschuetzte Datei schreiben (einmalige Weitergabe).
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0770, true);
    }
    $outFile = $storageDir . '/student_credentials_' . date('Ymd_His') . '.csv';

    $handle = fopen($outFile, 'w');
    fputcsv($handle, ['Name', 'Benutzername', 'Temporaeres Passwort'], ';');
    foreach ($credentials as $cred) {
        fputcsv($handle, [$cred['name'], $cred['username'], $cred['password']], ';');
    }
    fclose($handle);
    @chmod($outFile, 0600);

    echo "\n" . count($credentials) . " Konto/Konten angelegt.\n";
    echo "Zugangsdaten gespeichert unter: {$outFile}\n";
    echo "WICHTIG: Datei sicher weitergeben und anschliessend loeschen.\n";
} catch (\Throwable $e) {
    echo "Datenbankfehler: " . $e->getMessage() . "\n";
    exit(1);
}
