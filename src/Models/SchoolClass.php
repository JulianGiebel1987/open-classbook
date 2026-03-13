<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class SchoolClass
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM classes WHERE id = ?', [$id]);
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT c.*, t.firstname as head_teacher_firstname, t.lastname as head_teacher_lastname
                FROM classes c
                LEFT JOIN teachers t ON t.id = c.head_teacher_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['school_year'])) {
            $sql .= ' AND c.school_year = ?';
            $params[] = $filters['school_year'];
        }

        $sql .= ' ORDER BY c.name';
        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO classes (name, school_year, head_teacher_id) VALUES (?, ?, ?)',
            [$data['name'], $data['school_year'], $data['head_teacher_id'] ?? null]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE classes SET name = ?, school_year = ?, head_teacher_id = ? WHERE id = ?',
            [$data['name'], $data['school_year'], $data['head_teacher_id'] ?? null, $id]
        );
    }

    public static function getTeachers(int $classId): array
    {
        return Database::query(
            'SELECT t.* FROM teachers t
             JOIN class_teacher ct ON ct.teacher_id = t.id
             WHERE ct.class_id = ?
             ORDER BY t.lastname, t.firstname',
            [$classId]
        );
    }

    public static function setTeachers(int $classId, array $teacherIds): void
    {
        Database::execute('DELETE FROM class_teacher WHERE class_id = ?', [$classId]);
        foreach ($teacherIds as $teacherId) {
            Database::execute(
                'INSERT INTO class_teacher (class_id, teacher_id) VALUES (?, ?)',
                [$classId, (int) $teacherId]
            );
        }
    }

    public static function findByName(string $name, string $schoolYear): ?array
    {
        return Database::queryOne(
            'SELECT * FROM classes WHERE name = ? AND school_year = ?',
            [$name, $schoolYear]
        );
    }

    public static function getSchoolYears(): array
    {
        return Database::query('SELECT DISTINCT school_year FROM classes ORDER BY school_year DESC');
    }
}
