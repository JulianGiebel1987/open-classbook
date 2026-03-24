<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ListColumn
{
    public static function findByList(int $listId): array
    {
        return Database::query(
            'SELECT * FROM list_columns WHERE list_id = ? ORDER BY position ASC, id ASC',
            [$listId]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM list_columns WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        // Naechste Position ermitteln
        $maxPos = Database::queryOne(
            'SELECT COALESCE(MAX(position), -1) as max_pos FROM list_columns WHERE list_id = ?',
            [$data['list_id']]
        );
        $position = ((int) $maxPos['max_pos']) + 1;

        Database::execute(
            'INSERT INTO list_columns (list_id, title, type, options, position) VALUES (?, ?, ?, ?, ?)',
            [
                $data['list_id'],
                $data['title'],
                $data['type'] ?? 'text',
                $data['options'] ?? null,
                $position,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE list_columns SET title = ?, type = ?, options = ? WHERE id = ?',
            [$data['title'], $data['type'], $data['options'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM list_columns WHERE id = ?', [$id]);
    }
}
