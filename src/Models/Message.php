<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Message
{
    public static function create(int $conversationId, int $senderId, string $body): int
    {
        Database::execute(
            'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)',
            [$conversationId, $senderId, $body]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Nachrichten einer Konversation laden (chronologisch).
     */
    public static function findByConversation(int $conversationId, int $limit = 50, int $offset = 0): array
    {
        return Database::query(
            'SELECT m.*, u.username as sender_username
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at DESC
             LIMIT ? OFFSET ?',
            [$conversationId, $limit, $offset]
        );
    }

    /**
     * Alle ungelesenen Nachrichten in einer Konversation als gelesen markieren.
     */
    public static function markAsRead(int $conversationId, int $userId): void
    {
        Database::execute(
            'UPDATE messages SET read_at = NOW() WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL',
            [$conversationId, $userId]
        );
    }

    /**
     * Gesamtzahl ungelesener Nachrichten fuer einen Nutzer.
     */
    public static function countUnread(int $userId): int
    {
        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt
             FROM messages m
             JOIN conversations c ON c.id = m.conversation_id
             WHERE (c.user_one_id = ? OR c.user_two_id = ?)
               AND m.sender_id != ?
               AND m.read_at IS NULL',
            [$userId, $userId, $userId]
        );
        return (int) ($result['cnt'] ?? 0);
    }
}
