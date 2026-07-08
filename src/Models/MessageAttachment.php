<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class MessageAttachment
{
    private const STORAGE_PATH = __DIR__ . '/../../storage/message_attachments/';

    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM message_attachments WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO message_attachments (message_id, group_message_id, original_name, stored_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['message_id'] ?? null,
                $data['group_message_id'] ?? null,
                $data['original_name'],
                $data['stored_name'],
                $data['mime_type'],
                $data['file_size'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Anhänge für mehrere 1:1-Nachrichten laden, gruppiert nach message_id.
     * @param int[] $messageIds
     * @return array<int, array<int, array>> [message_id => [attachment, ...]]
     */
    public static function findByMessageIds(array $messageIds): array
    {
        return self::groupByColumn($messageIds, 'message_id');
    }

    /**
     * Anhänge für mehrere Gruppen-Nachrichten laden, gruppiert nach group_message_id.
     * @param int[] $groupMessageIds
     * @return array<int, array<int, array>> [group_message_id => [attachment, ...]]
     */
    public static function findByGroupMessageIds(array $groupMessageIds): array
    {
        return self::groupByColumn($groupMessageIds, 'group_message_id');
    }

    private static function groupByColumn(array $ids, string $column): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::query(
            "SELECT * FROM message_attachments WHERE {$column} IN ({$placeholders}) ORDER BY id ASC",
            $ids
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row[$column]][] = $row;
        }
        return $grouped;
    }

    /**
     * Vollständiger Speicherpfad eines Anhangs.
     */
    public static function getStoragePath(string $storedName): string
    {
        return self::STORAGE_PATH . $storedName;
    }

    /**
     * Speicherverzeichnis sicherstellen und zurückgeben.
     */
    public static function ensureStorageDir(): string
    {
        if (!is_dir(self::STORAGE_PATH)) {
            mkdir(self::STORAGE_PATH, 0770, true);
        }
        return self::STORAGE_PATH;
    }

    /**
     * Zugriffsprüfung: Darf der Nutzer diesen Anhang abrufen?
     * Ableitung über die zugehörige Konversation bzw. Gruppe.
     */
    public static function userCanAccess(array $attachment, int $userId): bool
    {
        if (!empty($attachment['message_id'])) {
            $msg = Database::queryOne(
                'SELECT conversation_id FROM messages WHERE id = ?',
                [(int) $attachment['message_id']]
            );
            if (!$msg) {
                return false;
            }
            return Conversation::hasAccess((int) $msg['conversation_id'], $userId);
        }

        if (!empty($attachment['group_message_id'])) {
            $msg = Database::queryOne(
                'SELECT group_id FROM group_messages WHERE id = ?',
                [(int) $attachment['group_message_id']]
            );
            if (!$msg) {
                return false;
            }
            return GroupConversation::hasAccess((int) $msg['group_id'], $userId);
        }

        return false;
    }
}
