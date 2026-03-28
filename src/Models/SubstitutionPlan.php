<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class SubstitutionPlan
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM substitution_plans WHERE id = ?', [$id]);
    }

    public static function findByDate(int $settingId, string $date): ?array
    {
        return Database::queryOne(
            'SELECT * FROM substitution_plans WHERE timetable_setting_id = ? AND date = ?',
            [$settingId, $date]
        );
    }

    public static function findAll(int $settingId): array
    {
        return Database::query(
            'SELECT sp.*, u.username as published_by_username
             FROM substitution_plans sp
             LEFT JOIN users u ON u.id = sp.published_by
             WHERE sp.timetable_setting_id = ?
             ORDER BY sp.date DESC',
            [$settingId]
        );
    }

    public static function findUpcoming(int $settingId, string $fromDate, int $limit = 14): array
    {
        return Database::query(
            'SELECT sp.*, u.username as published_by_username
             FROM substitution_plans sp
             LEFT JOIN users u ON u.id = sp.published_by
             WHERE sp.timetable_setting_id = ? AND sp.date >= ? AND sp.is_published = 1
             ORDER BY sp.date ASC
             LIMIT ?',
            [$settingId, $fromDate, $limit]
        );
    }

    public static function createOrUpdate(int $settingId, string $date, int $userId): int
    {
        $existing = self::findByDate($settingId, $date);
        if ($existing) {
            return (int) $existing['id'];
        }

        Database::execute(
            'INSERT INTO substitution_plans (timetable_setting_id, date, created_by) VALUES (?, ?, ?)',
            [$settingId, $date, $userId]
        );
        return (int) Database::lastInsertId();
    }

    public static function publish(int $id, int $userId): void
    {
        Database::execute(
            'UPDATE substitution_plans SET is_published = 1, published_at = NOW(), published_by = ? WHERE id = ?',
            [$userId, $id]
        );
    }

    public static function unpublish(int $id): void
    {
        Database::execute(
            'UPDATE substitution_plans SET is_published = 0, published_at = NULL, published_by = NULL WHERE id = ?',
            [$id]
        );
    }
}
