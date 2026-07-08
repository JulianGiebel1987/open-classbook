<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

/**
 * Abwesenheiten von Schulbegleiter:innen (analog AbsenceTeacher).
 */
class AbsenceSchoolAide
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT a.*, sa.firstname, sa.lastname
             FROM absences_school_aides a
             JOIN school_aides sa ON sa.id = a.aide_id
             WHERE a.id = ?',
            [$id]
        );
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT a.*, sa.firstname, sa.lastname
                FROM absences_school_aides a
                JOIN school_aides sa ON sa.id = a.aide_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['aide_id'])) {
            $sql .= ' AND a.aide_id = ?';
            $params[] = $filters['aide_id'];
        }

        if (!empty($filters['type'])) {
            $sql .= ' AND a.type = ?';
            $params[] = $filters['type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND a.date_to >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND a.date_from <= ?';
            $params[] = $filters['date_to'];
        }

        $sql .= ' ORDER BY a.date_from DESC';
        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO absences_school_aides (aide_id, date_from, date_to, type, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['aide_id'],
                $data['date_from'],
                $data['date_to'],
                $data['type'] ?? 'krank',
                $data['reason'] ?? null,
                $data['notes'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE absences_school_aides SET date_from = ?, date_to = ?, type = ?, reason = ?, notes = ? WHERE id = ?',
            [$data['date_from'], $data['date_to'], $data['type'], $data['reason'] ?? null, $data['notes'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM absences_school_aides WHERE id = ?', [$id]);
    }
}
