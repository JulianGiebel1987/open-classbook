<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class SupervisionPlan
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM supervision_plans WHERE id = ?', [$id]);
    }

    public static function findBySchoolYear(string $schoolYear): ?array
    {
        return Database::queryOne('SELECT * FROM supervision_plans WHERE school_year = ?', [$schoolYear]);
    }

    public static function findAll(): array
    {
        return Database::query(
            'SELECT sp.*, u.username as created_by_username
             FROM supervision_plans sp
             LEFT JOIN users u ON u.id = sp.created_by
             ORDER BY sp.school_year DESC, sp.name'
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO supervision_plans (name, school_year, days_of_week, created_by)
             VALUES (?, ?, ?, ?)',
            [
                $data['name'],
                $data['school_year'],
                json_encode($data['days_of_week']),
                $data['created_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE supervision_plans SET name = ?, school_year = ?, days_of_week = ? WHERE id = ?',
            [
                $data['name'],
                $data['school_year'],
                json_encode($data['days_of_week']),
                $id,
            ]
        );
    }

    public static function publish(int $id, int $userId): void
    {
        Database::execute(
            'UPDATE supervision_plans SET is_published = 1, published_at = NOW(), published_by = ? WHERE id = ?',
            [$userId, $id]
        );
    }

    public static function unpublish(int $id): void
    {
        Database::execute(
            'UPDATE supervision_plans SET is_published = 0, published_at = NULL, published_by = NULL WHERE id = ?',
            [$id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM supervision_plans WHERE id = ?', [$id]);
    }
}
