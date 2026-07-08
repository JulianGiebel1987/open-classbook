<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Student
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM students WHERE id = ?', [$id]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return Database::queryOne(
            'SELECT s.*, c.name as class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE s.user_id = ?',
            [$userId]
        );
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT s.*, c.name as class_name FROM students s JOIN classes c ON c.id = s.class_id WHERE 1=1';
        $params = [];

        if (empty($filters['include_archived'])) {
            $sql .= ' AND s.archived_at IS NULL';
        }

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

    public static function findByClassId(int $classId, bool $includeArchived = false): array
    {
        $sql = 'SELECT * FROM students WHERE class_id = ?';
        if (!$includeArchived) {
            $sql .= ' AND archived_at IS NULL';
        }
        $sql .= ' ORDER BY lastname, firstname';
        return Database::query($sql, [$classId]);
    }

    /**
     * Nur archivierte Schueler:innen einer Klasse.
     */
    public static function findArchivedByClassId(int $classId): array
    {
        return Database::query(
            'SELECT * FROM students WHERE class_id = ? AND archived_at IS NOT NULL ORDER BY lastname, firstname',
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

    public static function changeClass(int $id, int $newClassId): void
    {
        Database::execute('UPDATE students SET class_id = ? WHERE id = ?', [$newClassId, $id]);
    }

    /**
     * Verknuepft einen Schueler-Datensatz nachtraeglich mit einem Benutzerkonto.
     */
    public static function setUserId(int $id, int $userId): void
    {
        Database::execute('UPDATE students SET user_id = ? WHERE id = ?', [$userId, $id]);
    }

    /**
     * Schueler:in archivieren (Soft-Delete): archived_at wird gesetzt.
     */
    public static function archive(int $id): void
    {
        Database::execute('UPDATE students SET archived_at = NOW() WHERE id = ?', [$id]);
    }

    /**
     * Archivierte Schueler:in wiederherstellen.
     */
    public static function restore(int $id): void
    {
        Database::execute('UPDATE students SET archived_at = NULL WHERE id = ?', [$id]);
    }

    /**
     * Der/den Schueler:in begleitende Schulbegleiter:innen (n:m ueber aide_student).
     */
    public static function getAides(int $studentId): array
    {
        return Database::query(
            'SELECT a.* FROM school_aides a
             JOIN aide_student ast ON ast.aide_id = a.id
             WHERE ast.student_id = ?
             ORDER BY a.lastname, a.firstname',
            [$studentId]
        );
    }

    public static function countByClassId(int $classId, bool $includeArchived = false): int
    {
        $sql = 'SELECT COUNT(*) as cnt FROM students WHERE class_id = ?';
        if (!$includeArchived) {
            $sql .= ' AND archived_at IS NULL';
        }
        $result = Database::queryOne($sql, [$classId]);
        return (int) ($result['cnt'] ?? 0);
    }
}
