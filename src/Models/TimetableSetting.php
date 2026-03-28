<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class TimetableSetting
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM timetable_settings WHERE id = ?', [$id]);
    }

    public static function findBySchoolYear(string $schoolYear): ?array
    {
        return Database::queryOne('SELECT * FROM timetable_settings WHERE school_year = ?', [$schoolYear]);
    }

    public static function findAll(): array
    {
        return Database::query(
            'SELECT ts.*, u.username as created_by_username
             FROM timetable_settings ts
             LEFT JOIN users u ON u.id = ts.created_by
             ORDER BY ts.school_year DESC'
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO timetable_settings (school_year, unit_duration, units_per_day, day_start_time, days_of_week, created_by)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['school_year'],
                $data['unit_duration'],
                $data['units_per_day'],
                $data['day_start_time'],
                json_encode($data['days_of_week']),
                $data['created_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE timetable_settings SET school_year = ?, unit_duration = ?, units_per_day = ?, day_start_time = ?, days_of_week = ? WHERE id = ?',
            [
                $data['school_year'],
                $data['unit_duration'],
                $data['units_per_day'],
                $data['day_start_time'],
                json_encode($data['days_of_week']),
                $id,
            ]
        );
    }

    public static function publish(int $id, int $userId): void
    {
        Database::execute(
            'UPDATE timetable_settings SET is_published = 1, published_at = NOW(), published_by = ? WHERE id = ?',
            [$userId, $id]
        );
    }

    public static function unpublish(int $id): void
    {
        Database::execute(
            'UPDATE timetable_settings SET is_published = 0, published_at = NULL, published_by = NULL WHERE id = ?',
            [$id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM timetable_settings WHERE id = ?', [$id]);
    }
}
