<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ZeugnisInstance
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT zi.*,
                    s.firstname as student_first_name, s.lastname as student_last_name,
                    c.name as class_name,
                    zt.name as template_name, zt.page_orientation, zt.page_format, zt.template_canvas,
                    u.username as creator_username
             FROM zeugnis_instances zi
             JOIN students s ON s.id = zi.student_id
             JOIN zeugnis_templates zt ON zt.id = zi.template_id
             LEFT JOIN classes c ON c.id = s.class_id
             JOIN users u ON u.id = zi.created_by
             WHERE zi.id = ?',
            [$id]
        );
    }

    /**
     * All instances the user owns or that are shared with them.
     */
    public static function findByUser(int $userId): array
    {
        return Database::query(
            "SELECT zi.*,
                    s.firstname as student_first_name, s.lastname as student_last_name,
                    c.name as class_name,
                    zt.name as template_name,
                    u.username as creator_username,
                    zs.can_edit as shared_can_edit
             FROM zeugnis_instances zi
             JOIN students s ON s.id = zi.student_id
             JOIN zeugnis_templates zt ON zt.id = zi.template_id
             LEFT JOIN classes c ON c.id = s.class_id
             JOIN users u ON u.id = zi.created_by
             LEFT JOIN zeugnis_shares zs ON zs.instance_id = zi.id AND zs.user_id = ?
             WHERE zi.created_by = ? OR zs.user_id = ?
             ORDER BY zi.updated_at DESC",
            [$userId, $userId, $userId]
        );
    }

    public static function findByTemplate(int $templateId): array
    {
        return Database::query(
            'SELECT zi.*,
                    s.firstname as student_first_name, s.lastname as student_last_name,
                    c.name as class_name,
                    u.username as creator_username
             FROM zeugnis_instances zi
             JOIN students s ON s.id = zi.student_id
             LEFT JOIN classes c ON c.id = s.class_id
             JOIN users u ON u.id = zi.created_by
             WHERE zi.template_id = ?
             ORDER BY s.lastname, s.firstname',
            [$templateId]
        );
    }

    public static function findByStudent(int $studentId): array
    {
        return Database::query(
            'SELECT zi.*, zt.name as template_name, u.username as creator_username
             FROM zeugnis_instances zi
             JOIN zeugnis_templates zt ON zt.id = zi.template_id
             JOIN users u ON u.id = zi.created_by
             WHERE zi.student_id = ?
             ORDER BY zi.updated_at DESC',
            [$studentId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO zeugnis_instances (template_id, student_id, created_by, title, status, field_values)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['template_id'],
                $data['student_id'],
                $data['created_by'],
                $data['title'] ?? null,
                $data['status'] ?? 'draft',
                $data['field_values'] ?? '{}',
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE zeugnis_instances SET title = ?, status = ?, field_values = ? WHERE id = ?',
            [
                $data['title'] ?? null,
                $data['status'] ?? 'draft',
                $data['field_values'] ?? '{}',
                $id,
            ]
        );
    }

    public static function updateFieldValues(int $id, array $fieldValues): void
    {
        Database::execute(
            'UPDATE zeugnis_instances SET field_values = ? WHERE id = ?',
            [json_encode($fieldValues, JSON_UNESCAPED_UNICODE), $id]
        );
    }

    public static function updateSingleField(int $id, string $fieldId, mixed $value): void
    {
        // Merge single field into existing JSON using SQL JSON_SET
        Database::execute(
            'UPDATE zeugnis_instances
             SET field_values = JSON_SET(field_values, ?, CAST(? AS JSON))
             WHERE id = ?',
            ['$.' . $fieldId, json_encode($value), $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM zeugnis_instances WHERE id = ?', [$id]);
    }

    public static function hasAccess(int $instanceId, int $userId, string $role): bool
    {
        if (in_array($role, ['admin', 'schulleitung', 'sekretariat'], true)) {
            return Database::queryOne('SELECT id FROM zeugnis_instances WHERE id = ?', [$instanceId]) !== null;
        }

        $row = Database::queryOne('SELECT created_by FROM zeugnis_instances WHERE id = ?', [$instanceId]);
        if (!$row) {
            return false;
        }
        if ((int) $row['created_by'] === $userId) {
            return true;
        }

        $share = Database::queryOne(
            'SELECT id FROM zeugnis_shares WHERE instance_id = ? AND user_id = ?',
            [$instanceId, $userId]
        );
        return $share !== null;
    }

    public static function canEdit(int $instanceId, int $userId, string $role): bool
    {
        if (in_array($role, ['admin', 'schulleitung', 'sekretariat'], true)) {
            return Database::queryOne('SELECT id FROM zeugnis_instances WHERE id = ?', [$instanceId]) !== null;
        }

        $row = Database::queryOne('SELECT created_by FROM zeugnis_instances WHERE id = ?', [$instanceId]);
        if (!$row) {
            return false;
        }
        if ((int) $row['created_by'] === $userId) {
            return true;
        }

        $share = Database::queryOne(
            'SELECT can_edit FROM zeugnis_shares WHERE instance_id = ? AND user_id = ?',
            [$instanceId, $userId]
        );
        return $share && (int) $share['can_edit'] === 1;
    }

    public static function getShares(int $instanceId): array
    {
        return Database::query(
            'SELECT zs.*, u.username, u.role
             FROM zeugnis_shares zs
             JOIN users u ON u.id = zs.user_id
             WHERE zs.instance_id = ?
             ORDER BY u.username',
            [$instanceId]
        );
    }

    public static function addShare(int $instanceId, int $userId, bool $canEdit): void
    {
        Database::execute(
            'INSERT INTO zeugnis_shares (instance_id, user_id, can_edit) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE can_edit = ?',
            [$instanceId, $userId, $canEdit ? 1 : 0, $canEdit ? 1 : 0]
        );
    }

    public static function removeShare(int $instanceId, int $userId): void
    {
        Database::execute(
            'DELETE FROM zeugnis_shares WHERE instance_id = ? AND user_id = ?',
            [$instanceId, $userId]
        );
    }
}
