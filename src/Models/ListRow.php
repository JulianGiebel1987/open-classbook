<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ListRow
{
    public static function findByList(int $listId): array
    {
        return Database::query(
            'SELECT * FROM list_rows WHERE list_id = ? ORDER BY position ASC, id ASC',
            [$listId]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM list_rows WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        $maxPos = Database::queryOne(
            'SELECT COALESCE(MAX(position), -1) as max_pos FROM list_rows WHERE list_id = ?',
            [$data['list_id']]
        );
        $position = ((int) $maxPos['max_pos']) + 1;

        Database::execute(
            'INSERT INTO list_rows (list_id, label, position) VALUES (?, ?, ?)',
            [
                $data['list_id'],
                $data['label'] ?? null,
                $position,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM list_rows WHERE id = ?', [$id]);
    }

    /**
     * Zeilen aus Schülerliste einer Klasse vorbefüllen.
     */
    public static function createFromClass(int $listId, int $classId): void
    {
        $students = Student::findByClassId($classId);
        foreach ($students as $student) {
            self::create([
                'list_id' => $listId,
                'label' => $student['lastname'] . ', ' . $student['firstname'],
            ]);
        }
    }
}
