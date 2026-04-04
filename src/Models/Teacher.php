<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Teacher
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM teachers WHERE id = ?', [$id]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return Database::queryOne('SELECT * FROM teachers WHERE user_id = ?', [$userId]);
    }

    public static function findAll(): array
    {
        return Database::query(
            'SELECT t.*, u.username, u.email, u.active
             FROM teachers t
             JOIN users u ON u.id = t.user_id
             ORDER BY t.lastname, t.firstname'
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO teachers (user_id, firstname, lastname, abbreviation, subjects) VALUES (?, ?, ?, ?, ?)',
            [$data['user_id'], $data['firstname'], $data['lastname'], $data['abbreviation'], $data['subjects'] ?? null]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE teachers SET firstname = ?, lastname = ?, abbreviation = ?, subjects = ? WHERE id = ?',
            [$data['firstname'], $data['lastname'], $data['abbreviation'], $data['subjects'] ?? null, $id]
        );
    }

    public static function abbreviationExists(string $abbr, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as cnt FROM teachers WHERE abbreviation = ?';
        $params = [$abbr];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $result = Database::queryOne($sql, $params);
        return ($result['cnt'] ?? 0) > 0;
    }

    public static function getClassesForTeacher(int $teacherId): array
    {
        return Database::query(
            'SELECT DISTINCT c.* FROM classes c
             LEFT JOIN class_teacher ct ON ct.class_id = c.id AND ct.teacher_id = ?
             WHERE ct.teacher_id IS NOT NULL OR c.head_teacher_id = ?
             ORDER BY c.name',
            [$teacherId, $teacherId]
        );
    }

    public static function getTeacherIdByUserId(int $userId): ?int
    {
        $result = Database::queryOne('SELECT id FROM teachers WHERE user_id = ?', [$userId]);
        return $result ? (int) $result['id'] : null;
    }
}
