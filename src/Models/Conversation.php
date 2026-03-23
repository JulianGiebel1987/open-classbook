<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Conversation
{
    /**
     * Konversation zwischen zwei Nutzern finden oder erstellen.
     * Sortiert IDs automatisch fuer Eindeutigkeit.
     */
    public static function findOrCreate(int $userA, int $userB): array
    {
        $userOneId = min($userA, $userB);
        $userTwoId = max($userA, $userB);

        $existing = Database::queryOne(
            'SELECT * FROM conversations WHERE user_one_id = ? AND user_two_id = ?',
            [$userOneId, $userTwoId]
        );

        if ($existing) {
            return $existing;
        }

        Database::execute(
            'INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)',
            [$userOneId, $userTwoId]
        );

        return Database::queryOne(
            'SELECT * FROM conversations WHERE id = ?',
            [(int) Database::lastInsertId()]
        );
    }

    /**
     * Alle Konversationen eines Nutzers mit Vorschau der letzten Nachricht.
     */
    public static function findByUserId(int $userId): array
    {
        return Database::query(
            'SELECT c.*,
                    CASE WHEN c.user_one_id = ? THEN u2.username ELSE u1.username END as partner_username,
                    CASE WHEN c.user_one_id = ? THEN u2.id ELSE u1.id END as partner_id,
                    CASE WHEN c.user_one_id = ? THEN u2.role ELSE u1.role END as partner_role,
                    m.body as last_message_body,
                    m.sender_id as last_message_sender_id,
                    m.created_at as last_message_created_at,
                    (SELECT COUNT(*) FROM messages m2
                     WHERE m2.conversation_id = c.id
                       AND m2.sender_id != ?
                       AND m2.read_at IS NULL) as unread_count
             FROM conversations c
             JOIN users u1 ON u1.id = c.user_one_id
             JOIN users u2 ON u2.id = c.user_two_id
             LEFT JOIN messages m ON m.id = (
                 SELECT m3.id FROM messages m3
                 WHERE m3.conversation_id = c.id
                 ORDER BY m3.created_at DESC LIMIT 1
             )
             WHERE c.user_one_id = ? OR c.user_two_id = ?
             ORDER BY COALESCE(c.last_message_at, c.created_at) DESC',
            [$userId, $userId, $userId, $userId, $userId, $userId]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT * FROM conversations WHERE id = ?',
            [$id]
        );
    }

    /**
     * Pruefen ob ein Nutzer Teilnehmer der Konversation ist.
     */
    public static function hasAccess(int $conversationId, int $userId): bool
    {
        $row = Database::queryOne(
            'SELECT id FROM conversations WHERE id = ? AND (user_one_id = ? OR user_two_id = ?)',
            [$conversationId, $userId, $userId]
        );
        return $row !== null;
    }

    public static function updateLastMessageAt(int $conversationId): void
    {
        Database::execute(
            'UPDATE conversations SET last_message_at = NOW() WHERE id = ?',
            [$conversationId]
        );
    }

    /**
     * Partner-Daten fuer eine Konversation laden.
     */
    public static function getPartner(int $conversationId, int $currentUserId): ?array
    {
        return Database::queryOne(
            'SELECT u.id, u.username, u.role
             FROM conversations c
             JOIN users u ON u.id = CASE WHEN c.user_one_id = ? THEN c.user_two_id ELSE c.user_one_id END
             WHERE c.id = ?',
            [$currentUserId, $conversationId]
        );
    }
}
