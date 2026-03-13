<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Student
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM students WHERE id = ?', [$id]);
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT s.*, c.name as class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE 1=1';
        $params = [];

        if (!empty($filters['class_id'])) {
            $sql .= ' AND s.class_id = ?';
            $params[] = $filters['class_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (s.firstname LIKE ? OR s.lastname LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY s.lastname, s.firstname';
        return Database::query($sql, $params);
    }

    public static function findByClassId(int $classId): array
    {
        return Database::query(
            'SELECT * FROM students WHERE class_id = ? ORDER BY lastname, firstname',
            [$classId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO students (user_id, firstname, lastname, class_id, birthday, guardian_email) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'] ?? null,
                $data['firstname'],
                $data['lastname'],
                $data['class_id'],
                $data['birthday'] ?? null,
                $data['guardian_email'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE students SET firstname = ?, lastname = ?, class_id = ?, birthday = ?, guardian_email = ? WHERE id = ?',
            [$data['firstname'], $data['lastname'], $data['class_id'], $data['birthday'] ?? null, $data['guardian_email'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM students WHERE id = ?', [$id]);
    }

    public static function countByClassId(int $classId): int
    {
        $result = Database::queryOne('SELECT COUNT(*) as cnt FROM students WHERE class_id = ?', [$classId]);
        return (int) ($result['cnt'] ?? 0);
    }
}
