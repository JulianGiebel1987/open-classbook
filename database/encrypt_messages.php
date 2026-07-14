<?php

/**
 * Einmaliges Backfill-Skript: verschluesselt bestehende Klartext-Nachrichten
 * in den Tabellen `messages` und `group_messages` (Encryption at Rest).
 *
 * Idempotent: Zeilen, deren `body` bereits mit "enc:v1:" beginnt, werden
 * uebersprungen. Das Skript kann daher gefahrlos mehrfach ausgefuehrt werden.
 *
 * Verwendung:
 *   php database/encrypt_messages.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use OpenClassbook\Database;
use OpenClassbook\Services\EncryptionService;

/**
 * Verschluesselt alle noch unverschluesselten Zeilen einer Nachrichtentabelle.
 *
 * @return int Anzahl der neu verschluesselten Zeilen.
 */
function encrypt_table(string $table): int
{
    $db = Database::getConnection();
    $rows = $db->query("SELECT id, body FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);

    $update = $db->prepare("UPDATE {$table} SET body = ? WHERE id = ?");
    $count = 0;

    foreach ($rows as $row) {
        if ($row['body'] === null || EncryptionService::isEncrypted($row['body'])) {
            continue;
        }
        $update->execute([EncryptionService::encrypt($row['body']), (int) $row['id']]);
        $count++;
    }

    return $count;
}

try {
    $messages = encrypt_table('messages');
    echo "messages:       {$messages} Zeile(n) verschluesselt.\n";

    $groupMessages = encrypt_table('group_messages');
    echo "group_messages: {$groupMessages} Zeile(n) verschluesselt.\n";

    $total = $messages + $groupMessages;
    if ($total === 0) {
        echo "Keine unverschluesselten Nachrichten gefunden – bereits aktuell.\n";
    } else {
        echo "\nFertig: {$total} Nachricht(en) insgesamt verschluesselt.\n";
    }
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Backfill fehlgeschlagen: " . $e->getMessage() . "\n");
    exit(1);
}
