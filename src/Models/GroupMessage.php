<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class GroupMessage
{
    public static function create(int $groupId, int $senderId, string $body): int
    {
        Database::execute(
            'INSERT INTO group_messages (group_id, sender_id, body) VALUES (?, ?, ?)',
            [$groupId, $senderId, $body]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Nachrichten einer Gruppe laden (neueste zuerst, für Pagination).
     */
    public static function findByGroup(int $groupId, int $limit = 50, int $offset = 0): array
    {
        return Database::query(
            'SELECT gm.*, u.username as sender_username
             FROM group_messages gm
             JOIN users u ON u.id = gm.sender_id
             WHERE gm.group_id = ?
             ORDER BY gm.created_at DESC
             LIMIT ? OFFSET ?',
            [$groupId, $limit, $offset]
        );
    }

    /**
     * Alle Nachrichten einer Gruppe als gelesen markieren (für aktuellen Nutzer).
     */
    public static function markAsRead(int $groupId, int $userId): void
    {
        // Alle ungelesenen Nachrichten holen, die nicht vom aktuellen Nutzer stammen
        $unread = Database::query(
            'SELECT gm.id FROM group_messages gm
             WHERE gm.group_id = ?
               AND gm.sender_id != ?
               AND gm.id NOT IN (
                   SELECT gmr.message_id FROM group_message_reads gmr WHERE gmr.user_id = ?
               )',
            [$groupId, $userId, $userId]
        );

        foreach ($unread as $msg) {
            Database::execute(
                'INSERT IGNORE INTO group_message_reads (message_id, user_id) VALUES (?, ?)',
                [(int) $msg['id'], $userId]
            );
        }
    }

    /**
     * Gesamtzahl ungelesener Gruppen-Nachrichten für einen Nutzer.
     */
    public static function countUnread(int $userId): int
    {
        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt
             FROM group_messages gm
             JOIN group_conversation_members gcm ON gcm.group_id = gm.group_id AND gcm.user_id = ?
             WHERE gm.sender_id != ?
               AND gm.id NOT IN (
                   SELECT gmr.message_id FROM group_message_reads gmr WHERE gmr.user_id = ?
               )',
            [$userId, $userId, $userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }
}
