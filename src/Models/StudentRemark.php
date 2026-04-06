<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class StudentRemark
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT sr.*,
                    s.firstname   AS student_firstname,
                    s.lastname    AS student_lastname,
                    t.firstname   AS teacher_firstname,
                    t.lastname    AS teacher_lastname,
                    t.abbreviation,
                    c.name        AS class_name
             FROM student_remarks sr
             JOIN students s ON s.id = sr.student_id
             JOIN teachers t ON t.id = sr.teacher_id
             JOIN classes  c ON c.id = sr.class_id
             WHERE sr.id = ?',
            [$id]
        );
    }

    /**
     * Alle Bemerkungen einer Klasse, optional gefiltert nach Schüler und Datum.
     */
    public static function findByClass(int $classId, array $filters = []): array
    {
        $sql = 'SELECT sr.*,
                       s.firstname   AS student_firstname,
                       s.lastname    AS student_lastname,
                       t.firstname   AS teacher_firstname,
                       t.lastname    AS teacher_lastname,
                       t.abbreviation
                FROM student_remarks sr
                JOIN students s ON s.id = sr.student_id
                JOIN teachers t ON t.id = sr.teacher_id
                WHERE sr.class_id = ?';
        $params = [$classId];

        if (!empty($filters['student_id'])) {
            $sql .= ' AND sr.student_id = ?';
            $params[] = (int) $filters['student_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND sr.remark_date >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND sr.remark_date <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY sr.remark_date DESC, sr.created_at DESC';
        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO student_remarks (student_id, class_id, teacher_id, remark, remark_date)
             VALUES (?, ?, ?, ?, ?)',
            [
                $data['student_id'],
                $data['class_id'],
                $data['teacher_id'],
                $data['remark'],
                $data['remark_date'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM student_remarks WHERE id = ?', [$id]);
    }

    /**
     * Löschen ist erlaubt für: Admin (immer), Lehrer (eigene Bemerkung, max. 24 h).
     */
    public static function canDelete(array $remark, int $userId, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }
        if ($role === 'lehrer') {
            $teacher = Teacher::findByUserId($userId);
            if (!$teacher || (int) $teacher['id'] !== (int) $remark['teacher_id']) {
                return false;
            }
            return (time() - strtotime($remark['created_at'])) < 86400;
        }
        return false;
    }
}
