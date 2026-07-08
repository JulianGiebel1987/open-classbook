<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

/**
 * Schulbegleiter:innen (Integrationshilfen). Stammdaten analog Teacher,
 * verknuepft mit einem users-Datensatz (role = schulbegleiter) und ueber
 * die aide_student-Tabelle n:m mit den begleiteten Schueler:innen.
 */
class SchoolAide
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM school_aides WHERE id = ?', [$id]);
    }

    public static function findByUserId(int $userId): ?array
    {
        return Database::queryOne('SELECT * FROM school_aides WHERE user_id = ?', [$userId]);
    }

    public static function findAll(): array
    {
        return Database::query(
            'SELECT a.*, u.username, u.email, u.active
             FROM school_aides a
             JOIN users u ON u.id = a.user_id
             ORDER BY a.lastname, a.firstname'
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO school_aides (user_id, firstname, lastname, comment) VALUES (?, ?, ?, ?)',
            [$data['user_id'], $data['firstname'], $data['lastname'], $data['comment'] ?? null]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE school_aides SET firstname = ?, lastname = ?, comment = ? WHERE id = ?',
            [$data['firstname'], $data['lastname'], $data['comment'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM school_aides WHERE id = ?', [$id]);
    }

    public static function getAideIdByUserId(int $userId): ?int
    {
        $result = Database::queryOne('SELECT id FROM school_aides WHERE user_id = ?', [$userId]);
        return $result ? (int) $result['id'] : null;
    }

    /**
     * Von einer Begleitung begleitete Schueler:innen (n:m ueber aide_student).
     */
    public static function getStudents(int $aideId): array
    {
        return Database::query(
            'SELECT s.*, c.name as class_name
             FROM students s
             JOIN aide_student ast ON ast.student_id = s.id
             JOIN classes c ON c.id = s.class_id
             WHERE ast.aide_id = ?
             ORDER BY s.lastname, s.firstname',
            [$aideId]
        );
    }

    /**
     * Zuweisung der begleiteten Schueler:innen komplett ersetzen.
     */
    public static function setStudents(int $aideId, array $studentIds): void
    {
        Database::execute('DELETE FROM aide_student WHERE aide_id = ?', [$aideId]);
        foreach (array_unique(array_map('intval', $studentIds)) as $studentId) {
            if ($studentId <= 0) {
                continue;
            }
            Database::execute(
                'INSERT INTO aide_student (aide_id, student_id) VALUES (?, ?)',
                [$aideId, $studentId]
            );
        }
    }
}
