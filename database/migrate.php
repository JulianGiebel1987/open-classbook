<?php

/**
 * Datenbank-Migrationsskript
 *
 * Führt alle noch nicht ausgeführten SQL-Migrationen aus.
 * Verwendung: php database/migrate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;

/**
 * Entfernt SQL-Kommentare aus einem Skript, ohne Kommentare innerhalb
 * von String-Literalen zu beruehren. Erkennt -- ..., # ... und / * ... * /.
 */
function strip_sql_comments(string $sql): string
{
    $out = '';
    $len = strlen($sql);
    $i = 0;
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    while ($i < $len) {
        $c = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        if (!$inSingle && !$inDouble && !$inBacktick) {
            if (($c === '-' && $next === '-') || $c === '#') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }
            if ($c === '/' && $next === '*') {
                $i += 2;
                while ($i < $len - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i += 2;
                continue;
            }
        }

        if (!$inDouble && !$inBacktick && $c === "'" && ($i === 0 || $sql[$i - 1] !== '\\')) {
            $inSingle = !$inSingle;
        } elseif (!$inSingle && !$inBacktick && $c === '"' && ($i === 0 || $sql[$i - 1] !== '\\')) {
            $inDouble = !$inDouble;
        } elseif (!$inSingle && !$inDouble && $c === '`') {
            $inBacktick = !$inBacktick;
        }

        $out .= $c;
        $i++;
    }
    return $out;
}

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

    // Bereits ausgeführte Migrationen laden
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
            // Multi-Statement SQL aufteilen — PDO::exec() kann bei
            // ATTR_EMULATE_PREPARES=false nur ein Statement zuverlässig ausführen.
            // Vorher SQL-Kommentare entfernen, damit Semikolons in Kommentaren
            // nicht das Splitting zerstoeren.
            $statements = array_filter(
                array_map('trim', explode(';', strip_sql_comments($sql))),
                fn($s) => $s !== ''
            );
            foreach ($statements as $statement) {
                $db->exec($statement);
            }

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
        echo "\n{$count} Migration(en) erfolgreich ausgeführt.\n";
    }
} catch (PDOException $e) {
    echo "Datenbankfehler: " . $e->getMessage() . "\n";
    exit(1);
}
