<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Folder
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM folders WHERE id = ?', [$id]);
    }

    /**
     * Ordner in einem Verzeichnis auflisten.
     * Fuer private Ordner: owner_id muss passen.
     * Fuer gemeinschaftliche Ordner: is_shared = 1.
     */
    public static function findByParent(?int $parentId, int $ownerId, bool $shared): array
    {
        if ($shared) {
            $sql = 'SELECT * FROM folders WHERE is_shared = 1 AND parent_id ';
            $params = [];
        } else {
            $sql = 'SELECT * FROM folders WHERE is_shared = 0 AND owner_id = ? AND parent_id ';
            $params = [$ownerId];
        }

        if ($parentId === null) {
            $sql .= 'IS NULL';
        } else {
            $sql .= '= ?';
            $params[] = $parentId;
        }

        $sql .= ' ORDER BY name ASC';
        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO folders (name, parent_id, owner_id, is_shared, created_by) VALUES (?, ?, ?, ?, ?)',
            [
                $data['name'],
                $data['parent_id'] ?? null,
                $data['owner_id'] ?? null,
                $data['is_shared'] ? 1 : 0,
                $data['created_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Ordner rekursiv loeschen (Unterordner + Dateien werden durch CASCADE geloescht,
     * physische Dateien muessen separat entfernt werden).
     */
    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM folders WHERE id = ?', [$id]);
    }

    /**
     * Alle physischen Dateien eines Ordners und seiner Unterordner sammeln (fuer Cleanup).
     */
    public static function collectStoredNames(int $folderId): array
    {
        $files = Database::query('SELECT stored_name FROM files WHERE folder_id = ?', [$folderId]);
        $names = array_column($files, 'stored_name');

        $subfolders = Database::query('SELECT id FROM folders WHERE parent_id = ?', [$folderId]);
        foreach ($subfolders as $sub) {
            $names = array_merge($names, self::collectStoredNames($sub['id']));
        }

        return $names;
    }

    /**
     * Breadcrumb-Pfad vom Ordner bis zur Wurzel.
     */
    public static function getPath(int $id): array
    {
        $path = [];
        $current = self::findById($id);
        while ($current) {
            array_unshift($path, $current);
            $current = $current['parent_id'] ? self::findById($current['parent_id']) : null;
        }
        return $path;
    }

    /**
     * Zugriffspruefung: Nutzer darf auf den Ordner zugreifen?
     */
    public static function hasAccess(int $folderId, int $userId): bool
    {
        $folder = self::findById($folderId);
        if (!$folder) {
            return false;
        }
        if ($folder['is_shared']) {
            return true;
        }
        return (int) $folder['owner_id'] === $userId;
    }
}
