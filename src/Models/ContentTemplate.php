<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

/**
 * Unterrichtsinhalt-Vorlagen: wiederverwendbare Themen/Notizen fuers Klassenbuch.
 *
 * Sichtbarkeit ueber owner_user_id:
 *   NULL          = schulweit geteilt (von Verwaltung gepflegt)
 *   <user id>     = persoenliche Vorlage der jeweiligen Nutzer:in
 */
class ContentTemplate
{
    /** Rollen, die geteilte Vorlagen (owner_user_id = NULL) verwalten duerfen. */
    private const SHARED_MANAGER_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT * FROM content_templates WHERE id = ?',
            [$id]
        );
    }

    /**
     * Alle fuer eine Nutzer:in sichtbaren Vorlagen: eigene persoenliche + geteilte.
     * Sortiert: persoenliche zuerst, dann nach Kategorie und Thema.
     */
    public static function findForUser(int $userId, string $role): array
    {
        return Database::query(
            'SELECT * FROM content_templates
             WHERE owner_user_id = ? OR owner_user_id IS NULL
             ORDER BY (owner_user_id IS NULL), category IS NULL, category ASC, topic ASC',
            [$userId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO content_templates (owner_user_id, category, topic, notes) VALUES (?, ?, ?, ?)',
            [
                $data['owner_user_id'] ?? null,
                $data['category'] ?? null,
                $data['topic'],
                $data['notes'] ?? null,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE content_templates SET owner_user_id = ?, category = ?, topic = ?, notes = ? WHERE id = ?',
            [
                $data['owner_user_id'] ?? null,
                $data['category'] ?? null,
                $data['topic'],
                $data['notes'] ?? null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM content_templates WHERE id = ?', [$id]);
    }

    /**
     * Distinct, nicht-leere Kategorien der sichtbaren Vorlagen (fuer Autovervollstaendigung).
     *
     * @return string[]
     */
    public static function getCategories(int $userId, string $role): array
    {
        $rows = Database::query(
            "SELECT DISTINCT category FROM content_templates
             WHERE (owner_user_id = ? OR owner_user_id IS NULL)
               AND category IS NOT NULL AND category <> ''
             ORDER BY category ASC",
            [$userId]
        );
        return array_map(static fn (array $r): string => $r['category'], $rows);
    }

    /**
     * Darf die Nutzer:in diese Vorlage bearbeiten/loeschen?
     * - Admin: immer.
     * - Geteilte Vorlage (owner NULL): nur Verwaltungsrollen.
     * - Persoenliche Vorlage: nur die Eigentuemer:in.
     */
    public static function canManage(array $template, int $userId, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }

        if ($template['owner_user_id'] === null) {
            return in_array($role, self::SHARED_MANAGER_ROLES, true);
        }

        return (int) $template['owner_user_id'] === $userId;
    }
}
