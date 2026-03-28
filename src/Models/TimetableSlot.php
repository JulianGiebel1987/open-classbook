<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class TimetableSlot
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT ts.*, t.firstname, t.lastname, t.abbreviation, t.subjects,
                    c.name as class_name
             FROM timetable_slots ts
             JOIN teachers t ON t.id = ts.teacher_id
             JOIN classes c ON c.id = ts.class_id
             WHERE ts.id = ?',
            [$id]
        );
    }

    public static function findBySettingAndClass(int $settingId, int $classId): array
    {
        return Database::query(
            'SELECT ts.*, t.firstname, t.lastname, t.abbreviation
             FROM timetable_slots ts
             JOIN teachers t ON t.id = ts.teacher_id
             WHERE ts.timetable_setting_id = ? AND ts.class_id = ?
             ORDER BY ts.day_of_week, ts.slot_number',
            [$settingId, $classId]
        );
    }

    public static function findBySettingAndTeacher(int $settingId, int $teacherId): array
    {
        return Database::query(
            'SELECT ts.*, c.name as class_name
             FROM timetable_slots ts
             JOIN classes c ON c.id = ts.class_id
             WHERE ts.timetable_setting_id = ? AND ts.teacher_id = ?
             ORDER BY ts.day_of_week, ts.slot_number',
            [$settingId, $teacherId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO timetable_slots (timetable_setting_id, class_id, teacher_id, day_of_week, slot_number, subject, room)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['timetable_setting_id'],
                $data['class_id'],
                $data['teacher_id'],
                $data['day_of_week'],
                $data['slot_number'],
                $data['subject'] ?? null,
                $data['room'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE timetable_slots SET teacher_id = ?, subject = ?, room = ? WHERE id = ?',
            [$data['teacher_id'], $data['subject'] ?? null, $data['room'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM timetable_slots WHERE id = ?', [$id]);
    }

    public static function deleteBySettingAndClass(int $settingId, int $classId): void
    {
        Database::execute(
            'DELETE FROM timetable_slots WHERE timetable_setting_id = ? AND class_id = ?',
            [$settingId, $classId]
        );
    }

    /**
     * Pruefen ob ein Lehrer im selben Zeitslot bereits in einer anderen Klasse eingeplant ist.
     * Gibt Konflikt-Info zurueck oder null.
     */
    public static function checkTeacherConflict(int $settingId, int $teacherId, int $dayOfWeek, int $slotNumber, ?int $excludeClassId = null): ?array
    {
        $sql = 'SELECT ts.*, c.name as class_name
                FROM timetable_slots ts
                JOIN classes c ON c.id = ts.class_id
                WHERE ts.timetable_setting_id = ?
                  AND ts.teacher_id = ?
                  AND ts.day_of_week = ?
                  AND ts.slot_number = ?';
        $params = [$settingId, $teacherId, $dayOfWeek, $slotNumber];

        if ($excludeClassId !== null) {
            $sql .= ' AND ts.class_id != ?';
            $params[] = $excludeClassId;
        }

        return Database::queryOne($sql, $params);
    }

    /**
     * Anzahl belegter Einheiten eines Lehrers in einem Stundenplan.
     */
    public static function countTeacherUnits(int $settingId, int $teacherId): int
    {
        $result = Database::queryOne(
            'SELECT COUNT(*) as cnt FROM timetable_slots WHERE timetable_setting_id = ? AND teacher_id = ?',
            [$settingId, $teacherId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Bulk-Abfrage: Einheiten-Zaehler fuer alle Lehrer.
     */
    public static function getTeacherUnitCounts(int $settingId): array
    {
        $rows = Database::query(
            'SELECT teacher_id, COUNT(*) as unit_count
             FROM timetable_slots
             WHERE timetable_setting_id = ?
             GROUP BY teacher_id',
            [$settingId]
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['teacher_id']] = (int) $row['unit_count'];
        }
        return $counts;
    }
}
