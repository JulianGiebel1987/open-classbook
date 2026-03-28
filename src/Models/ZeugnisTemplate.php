<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ZeugnisTemplate
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT zt.*, u.username as creator_username
             FROM zeugnis_templates zt
             JOIN users u ON u.id = zt.created_by
             WHERE zt.id = ?',
            [$id]
        );
    }

    public static function findAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'zt.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['school_year'])) {
            $where[] = 'zt.school_year = ?';
            $params[] = $filters['school_year'];
        }
        if (!empty($filters['created_by'])) {
            $where[] = 'zt.created_by = ?';
            $params[] = $filters['created_by'];
        }

        $sql = 'SELECT zt.*, u.username as creator_username
                FROM zeugnis_templates zt
                JOIN users u ON u.id = zt.created_by';

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY zt.updated_at DESC';

        return Database::query($sql, $params);
    }

    public static function findPublished(): array
    {
        return Database::query(
            "SELECT zt.*, u.username as creator_username
             FROM zeugnis_templates zt
             JOIN users u ON u.id = zt.created_by
             WHERE zt.status = 'published'
             ORDER BY zt.name ASC",
            []
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO zeugnis_templates
             (name, description, school_year, grade_levels, page_orientation, page_format, template_canvas, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['description'] ?? null,
                $data['school_year'] ?? null,
                $data['grade_levels'] ?? null,
                $data['page_orientation'] ?? 'P',
                $data['page_format'] ?? 'A4',
                $data['template_canvas'],
                $data['status'] ?? 'draft',
                $data['created_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE zeugnis_templates
             SET name = ?, description = ?, school_year = ?, grade_levels = ?,
                 page_orientation = ?, page_format = ?, template_canvas = ?, updated_by = ?
             WHERE id = ?',
            [
                $data['name'],
                $data['description'] ?? null,
                $data['school_year'] ?? null,
                $data['grade_levels'] ?? null,
                $data['page_orientation'] ?? 'P',
                $data['page_format'] ?? 'A4',
                $data['template_canvas'],
                $data['updated_by'],
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM zeugnis_templates WHERE id = ?', [$id]);
    }

    public static function publish(int $id, int $userId): void
    {
        Database::execute(
            "UPDATE zeugnis_templates
             SET status = 'published', published_at = NOW(), published_by = ?
             WHERE id = ?",
            [$userId, $id]
        );
    }

    public static function unpublish(int $id): void
    {
        Database::execute(
            "UPDATE zeugnis_templates
             SET status = 'draft', published_at = NULL, published_by = NULL
             WHERE id = ?",
            [$id]
        );
    }

    public static function duplicate(int $id, int $userId): int
    {
        $original = self::findById($id);
        if (!$original) {
            throw new \RuntimeException('Template not found: ' . $id);
        }

        Database::execute(
            'INSERT INTO zeugnis_templates
             (name, description, school_year, grade_levels, page_orientation, page_format, template_canvas, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $original['name'] . ' (Kopie)',
                $original['description'],
                $original['school_year'],
                $original['grade_levels'],
                $original['page_orientation'],
                $original['page_format'],
                $original['template_canvas'],
                'draft',
                $userId,
            ]
        );

        return (int) Database::lastInsertId();
    }
}
