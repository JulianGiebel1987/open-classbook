<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class AbsenceStudent
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT a.*, s.firstname, s.lastname, s.class_id, c.name as class_name
             FROM absences_students a
             JOIN students s ON s.id = a.student_id
             JOIN classes c ON c.id = s.class_id
             WHERE a.id = ?',
            [$id]
        );
    }

    public static function findAll(array $filters = []): array
    {
        $sql = 'SELECT a.*, s.firstname, s.lastname, s.class_id, c.name as class_name
                FROM absences_students a
                JOIN students s ON s.id = a.student_id
                JOIN classes c ON c.id = s.class_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['class_id'])) {
            $sql .= ' AND s.class_id = ?';
            $params[] = $filters['class_id'];
        }

        if (!empty($filters['student_id'])) {
            $sql .= ' AND a.student_id = ?';
            $params[] = $filters['student_id'];
        }

        if (!empty($filters['excused'])) {
            $sql .= ' AND a.excused = ?';
            $params[] = $filters['excused'];
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
        $dateFrom = new \DateTime($data['date_from']);
        $dateTo = new \DateTime($data['date_to']);

        // Mehrtageseintraege auf einzelne Tage aufschluesseln
        if ($dateFrom < $dateTo) {
            $lastId = 0;
            $period = new \DatePeriod($dateFrom, new \DateInterval('P1D'), (clone $dateTo)->modify('+1 day'));
            foreach ($period as $day) {
                $dayStr = $day->format('Y-m-d');
                Database::execute(
                    'INSERT INTO absences_students (student_id, date_from, date_to, excused, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $data['student_id'],
                        $dayStr,
                        $dayStr,
                        $data['excused'] ?? 'offen',
                        $data['reason'] ?? null,
                        $data['notes'] ?? null,
                        $data['created_by'] ?? null,
                    ]
                );
                $lastId = (int) Database::lastInsertId();
            }
            return $lastId;
        }

        Database::execute(
            'INSERT INTO absences_students (student_id, date_from, date_to, excused, reason, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['student_id'],
                $data['date_from'],
                $data['date_to'],
                $data['excused'] ?? 'offen',
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
            'UPDATE absences_students SET date_from = ?, date_to = ?, excused = ?, reason = ?, notes = ? WHERE id = ?',
            [$data['date_from'], $data['date_to'], $data['excused'], $data['reason'] ?? null, $data['notes'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM absences_students WHERE id = ?', [$id]);
    }

    public static function countUnexcused(): int
    {
        $result = Database::queryOne('SELECT COUNT(*) as cnt FROM absences_students WHERE excused = "offen"');
        return (int) ($result['cnt'] ?? 0);
    }

    public static function getStudentSummary(int $studentId): array
    {
        return Database::query(
            'SELECT excused, COUNT(*) as cnt,
                    SUM(DATEDIFF(date_to, date_from) + 1) as total_days
             FROM absences_students
             WHERE student_id = ?
             GROUP BY excused',
            [$studentId]
        );
    }
}
