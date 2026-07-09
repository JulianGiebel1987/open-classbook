<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class SupervisionLocation
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM supervision_locations WHERE id = ?', [$id]);
    }

    public static function findByPlan(int $planId): array
    {
        return Database::query(
            'SELECT * FROM supervision_locations WHERE plan_id = ? ORDER BY sort_order, id',
            [$planId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO supervision_locations (plan_id, name, sort_order) VALUES (?, ?, ?)',
            [
                $data['plan_id'],
                $data['name'],
                $data['sort_order'] ?? 0,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM supervision_locations WHERE id = ?', [$id]);
    }
}
