<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class SupervisionAssignment
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT sa.*, t.firstname, t.lastname, t.abbreviation
             FROM supervision_assignments sa
             JOIN teachers t ON t.id = sa.teacher_id
             WHERE sa.id = ?',
            [$id]
        );
    }

    /**
     * Alle Zuweisungen eines Plans inkl. Lehrer-Kurzdaten.
     */
    public static function findByPlan(int $planId): array
    {
        return Database::query(
            'SELECT sa.*, t.firstname, t.lastname, t.abbreviation
             FROM supervision_assignments sa
             JOIN teachers t ON t.id = sa.teacher_id
             WHERE sa.plan_id = ?
             ORDER BY sa.location_id, sa.day_of_week, sa.break_id, t.lastname',
            [$planId]
        );
    }

    /**
     * Zuweisungen einer bestimmten Lehrkraft inkl. Pausen- und Ortsbezeichnung.
     */
    public static function findByPlanAndTeacher(int $planId, int $teacherId): array
    {
        return Database::query(
            'SELECT sa.*, b.label AS break_label, b.start_time, b.end_time, b.sort_order AS break_sort,
                    l.name AS location_name
             FROM supervision_assignments sa
             JOIN supervision_breaks b ON b.id = sa.break_id
             JOIN supervision_locations l ON l.id = sa.location_id
             WHERE sa.plan_id = ? AND sa.teacher_id = ?
             ORDER BY sa.day_of_week, b.sort_order, l.sort_order',
            [$planId, $teacherId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO supervision_assignments (plan_id, break_id, location_id, day_of_week, teacher_id)
             VALUES (?, ?, ?, ?, ?)',
            [
                $data['plan_id'],
                $data['break_id'],
                $data['location_id'],
                $data['day_of_week'],
                $data['teacher_id'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM supervision_assignments WHERE id = ?', [$id]);
    }

    /**
     * Pruefen ob dieselbe Lehrkraft im selben Zeitfenster (Tag + Pause) bereits
     * an einem anderen Aufsichtspunkt eingeplant ist.
     */
    public static function checkTeacherConflict(int $planId, int $teacherId, int $dayOfWeek, int $breakId, ?int $excludeLocationId = null): ?array
    {
        $sql = 'SELECT sa.*, l.name AS location_name
                FROM supervision_assignments sa
                JOIN supervision_locations l ON l.id = sa.location_id
                WHERE sa.plan_id = ?
                  AND sa.teacher_id = ?
                  AND sa.day_of_week = ?
                  AND sa.break_id = ?';
        $params = [$planId, $teacherId, $dayOfWeek, $breakId];

        if ($excludeLocationId !== null) {
            $sql .= ' AND sa.location_id != ?';
            $params[] = $excludeLocationId;
        }

        return Database::queryOne($sql, $params);
    }

    /**
     * Pruefen ob eine identische Zuweisung (gleiche Zelle + Lehrkraft) bereits existiert.
     */
    public static function exists(int $breakId, int $locationId, int $dayOfWeek, int $teacherId): bool
    {
        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM supervision_assignments
             WHERE break_id = ? AND location_id = ? AND day_of_week = ? AND teacher_id = ?',
            [$breakId, $locationId, $dayOfWeek, $teacherId]
        );
        return (int) ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Bulk-Zaehler: Anzahl Aufsichten je Lehrkraft im Plan.
     */
    public static function getTeacherCounts(int $planId): array
    {
        $rows = Database::query(
            'SELECT teacher_id, COUNT(*) as cnt
             FROM supervision_assignments
             WHERE plan_id = ?
             GROUP BY teacher_id',
            [$planId]
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['teacher_id']] = (int) $row['cnt'];
        }
        return $counts;
    }
}
