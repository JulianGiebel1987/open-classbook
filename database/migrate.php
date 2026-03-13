<?php

/**
 * Datenbank-Migrationsskript
 *
 * Fuehrt alle noch nicht ausgefuehrten SQL-Migrationen aus.
 * Verwendung: php database/migrate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;

try {
    $db = Database::getConnection();

    // Migrationstabelle erstellen falls nicht vorhanden
    $db->exec('
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ');

    // Bereits ausgefuehrte Migrationen laden
    $stmt = $db->query('SELECT filename FROM migrations');
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Migrationsdateien laden und sortieren
    $migrationsDir = __DIR__ . '/migrations/';
    $files = glob($migrationsDir . '*.sql');
    sort($files);

    $count = 0;
    foreach ($files as $file) {
        $filename = basename($file);

        if (in_array($filename, $executed)) {
            continue;
        }

        echo "Migriere: {$filename} ... ";

        $sql = file_get_contents($file);

        try {
            $db->exec($sql);

            $stmt = $db->prepare('INSERT INTO migrations (filename) VALUES (?)');
            $stmt->execute([$filename]);

            echo "OK\n";
            $count++;
        } catch (PDOException $e) {
            echo "FEHLER: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    if ($count === 0) {
        echo "Keine neuen Migrationen gefunden.\n";
    } else {
        echo "\n{$count} Migration(en) erfolgreich ausgefuehrt.\n";
    }
} catch (PDOException $e) {
    echo "Datenbankfehler: " . $e->getMessage() . "\n";
    exit(1);
}
