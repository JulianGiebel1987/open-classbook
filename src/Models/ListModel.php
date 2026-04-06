<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ListModel
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT l.*, u.username as owner_username, c.name as class_name
             FROM lists l
             JOIN users u ON u.id = l.owner_id
             LEFT JOIN classes c ON c.id = l.class_id
             WHERE l.id = ?',
            [$id]
        );
    }

    /**
     * Alle Listen, auf die der Nutzer Zugriff hat:
     * - Eigene Listen
     * - Globale Listen
     * - Freigegebene Listen
     */
    public static function findByUser(int $userId): array
    {
        return Database::query(
            "SELECT l.*, u.username as owner_username, c.name as class_name
             FROM lists l
             JOIN users u ON u.id = l.owner_id
             LEFT JOIN classes c ON c.id = l.class_id
             WHERE l.owner_id = ?
                OR l.visibility = 'global'
                OR (l.visibility = 'shared' AND l.id IN (
                    SELECT list_id FROM list_shares WHERE user_id = ?
                ))
             ORDER BY l.updated_at DESC",
            [$userId, $userId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO lists (title, description, owner_id, visibility, class_id) VALUES (?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['description'] ?? null,
                $data['owner_id'],
                $data['visibility'] ?? 'private',
                $data['class_id'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE lists SET title = ?, description = ?, visibility = ? WHERE id = ?',
            [$data['title'], $data['description'] ?? null, $data['visibility'], $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM lists WHERE id = ?', [$id]);
    }

    /**
     * Lese-Zugriff prüfen.
     */
    public static function hasAccess(int $listId, int $userId): bool
    {
        $list = Database::queryOne('SELECT owner_id, visibility FROM lists WHERE id = ?', [$listId]);
        if (!$list) {
            return false;
        }
        if ((int) $list['owner_id'] === $userId) {
            return true;
        }
        if ($list['visibility'] === 'global') {
            return true;
        }
        if ($list['visibility'] === 'shared') {
            $share = Database::queryOne(
                'SELECT id FROM list_shares WHERE list_id = ? AND user_id = ?',
                [$listId, $userId]
            );
            return $share !== null;
        }
        return false;
    }

    /**
     * Schreib-Zugriff prüfen.
     */
    public static function canEdit(int $listId, int $userId, string $userRole = ''): bool
    {
        $list = Database::queryOne('SELECT owner_id, visibility FROM lists WHERE id = ?', [$listId]);
        if (!$list) {
            return false;
        }
        if ((int) $list['owner_id'] === $userId || $userRole === 'admin') {
            return true;
        }
        if ($list['visibility'] === 'global') {
            // Globale Listen duerfen nur von privilegierten Rollen bearbeitet werden
            return in_array($userRole, ['admin', 'schulleitung'], true);
        }
        if ($list['visibility'] === 'shared') {
            $share = Database::queryOne(
                'SELECT can_edit FROM list_shares WHERE list_id = ? AND user_id = ?',
                [$listId, $userId]
            );
            return $share && (int) $share['can_edit'] === 1;
        }
        return false;
    }

    public static function getShares(int $listId): array
    {
        return Database::query(
            'SELECT ls.*, u.username, u.role
             FROM list_shares ls
             JOIN users u ON u.id = ls.user_id
             WHERE ls.list_id = ?
             ORDER BY u.username',
            [$listId]
        );
    }

    public static function addShare(int $listId, int $userId, bool $canEdit): void
    {
        Database::execute(
            'INSERT INTO list_shares (list_id, user_id, can_edit) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE can_edit = ?',
            [$listId, $userId, $canEdit ? 1 : 0, $canEdit ? 1 : 0]
        );
    }

    public static function removeShare(int $listId, int $userId): void
    {
        Database::execute(
            'DELETE FROM list_shares WHERE list_id = ? AND user_id = ?',
            [$listId, $userId]
        );
    }
}
