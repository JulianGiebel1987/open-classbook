<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class AbsenceTeacher
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT a.*, t.firstname, t.lastname, t.abbreviation
             FROM absences_teachers a
             JOIN teachers t ON t.id = a.teacher_id
             WHERE a.id = ?',
            [$id]
        );
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT a.*, t.firstname, t.lastname, t.abbreviation
                FROM absences_teachers a
                JOIN teachers t ON t.id = a.teacher_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['teacher_id'])) {
            $sql .= ' AND a.teacher_id = ?';
            $params[] = $filters['teacher_id'];
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
            'INSERT INTO absences_teachers (teacher_id, date_from, date_to, type, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['teacher_id'],
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
            'UPDATE absences_teachers SET date_from = ?, date_to = ?, type = ?, reason = ?, notes = ? WHERE id = ?',
            [$data['date_from'], $data['date_to'], $data['type'], $data['reason'] ?? null, $data['notes'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM absences_teachers WHERE id = ?', [$id]);
    }
}
