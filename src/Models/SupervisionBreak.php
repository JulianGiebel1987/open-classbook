<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class SupervisionBreak
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM supervision_breaks WHERE id = ?', [$id]);
    }

    public static function findByPlan(int $planId): array
    {
        return Database::query(
            'SELECT * FROM supervision_breaks WHERE plan_id = ? ORDER BY sort_order, id',
            [$planId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO supervision_breaks (plan_id, label, start_time, end_time, sort_order)
             VALUES (?, ?, ?, ?, ?)',
            [
                $data['plan_id'],
                $data['label'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null,
                $data['sort_order'] ?? 0,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE supervision_breaks SET label = ?, start_time = ?, end_time = ?, sort_order = ? WHERE id = ?',
            [
                $data['label'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null,
                $data['sort_order'] ?? 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM supervision_breaks WHERE id = ?', [$id]);
    }

    public static function deleteByPlan(int $planId): void
    {
        Database::execute('DELETE FROM supervision_breaks WHERE plan_id = ?', [$planId]);
    }
}
