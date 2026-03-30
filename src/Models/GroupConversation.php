<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class GroupConversation
{
    /**
     * Neue Gruppe erstellen und Mitglieder hinzufügen.
     * $memberIds enthält die IDs aller Mitglieder (ohne den Ersteller).
     */
    public static function create(string $name, int $createdBy, array $memberIds): array
    {
        Database::execute(
            'INSERT INTO group_conversations (name, created_by) VALUES (?, ?)',
            [$name, $createdBy]
        );
        $groupId = (int) Database::lastInsertId();

        // Ersteller als Mitglied hinzufügen
        $allMembers = array_unique(array_merge([$createdBy], $memberIds));
        foreach ($allMembers as $uid) {
            Database::execute(
                'INSERT IGNORE INTO group_conversation_members (group_id, user_id) VALUES (?, ?)',
                [$groupId, (int) $uid]
            );
        }

        return Database::queryOne('SELECT * FROM group_conversations WHERE id = ?', [$groupId]);
    }

    /**
     * Alle Gruppen eines Nutzers laden (mit Vorschau der letzten Nachricht).
     */
    public static function findByUserId(int $userId): array
    {
        return Database::query(
            'SELECT gc.*,
                    (SELECT COUNT(*) FROM group_conversation_members gcm2 WHERE gcm2.group_id = gc.id) as member_count,
                    gm.body as last_message_body,
                    gm.sender_id as last_message_sender_id,
                    gm.created_at as last_message_created_at,
                    (SELECT COUNT(*)
                     FROM group_messages gm2
                     WHERE gm2.group_id = gc.id
                       AND gm2.sender_id != ?
                       AND gm2.id NOT IN (
                           SELECT gmr.message_id FROM group_message_reads gmr WHERE gmr.user_id = ?
                       )
                    ) as unread_count
             FROM group_conversations gc
             JOIN group_conversation_members gcm ON gcm.group_id = gc.id AND gcm.user_id = ?
             LEFT JOIN group_messages gm ON gm.id = (
                 SELECT gm3.id FROM group_messages gm3
                 WHERE gm3.group_id = gc.id
                 ORDER BY gm3.created_at DESC LIMIT 1
             )
             ORDER BY COALESCE(gc.last_message_at, gc.created_at) DESC',
            [$userId, $userId, $userId]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM group_conversations WHERE id = ?', [$id]);
    }

    /**
     * Prüfen ob ein Nutzer Mitglied der Gruppe ist.
     */
    public static function hasAccess(int $groupId, int $userId): bool
    {
        $row = Database::queryOne(
            'SELECT id FROM group_conversation_members WHERE group_id = ? AND user_id = ?',
            [$groupId, $userId]
        );
        return $row !== null;
    }

    /**
     * Alle Mitglieder einer Gruppe laden.
     */
    public static function getMembers(int $groupId): array
    {
        return Database::query(
            'SELECT u.id, u.username, u.role
             FROM group_conversation_members gcm
             JOIN users u ON u.id = gcm.user_id
             WHERE gcm.group_id = ?
             ORDER BY u.username',
            [$groupId]
        );
    }

    public static function updateLastMessageAt(int $groupId): void
    {
        Database::execute(
            'UPDATE group_conversations SET last_message_at = NOW() WHERE id = ?',
            [$groupId]
        );
    }
}
