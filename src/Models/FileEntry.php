<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class FileEntry
{
    private const STORAGE_PATH = __DIR__ . '/../../storage/files/';

    public static function findById(int $id): ?array
    {
        return Database::queryOne('SELECT * FROM files WHERE id = ?', [$id]);
    }

    /**
     * Dateien in einem Ordner auflisten.
     */
    public static function findByFolder(?int $folderId, int $ownerId, bool $shared): array
    {
        if ($shared) {
            $sql = 'SELECT * FROM files WHERE is_shared = 1 AND folder_id ';
            $params = [];
        } else {
            $sql = 'SELECT * FROM files WHERE is_shared = 0 AND owner_id = ? AND folder_id ';
            $params = [$ownerId];
        }

        if ($folderId === null) {
            $sql .= 'IS NULL';
        } else {
            $sql .= '= ?';
            $params[] = $folderId;
        }

        $sql .= ' ORDER BY original_name ASC';
        return Database::query($sql, $params);
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO files (folder_id, owner_id, is_shared, original_name, stored_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['folder_id'] ?? null,
                $data['owner_id'],
                $data['is_shared'] ? 1 : 0,
                $data['original_name'],
                $data['stored_name'],
                $data['mime_type'],
                $data['file_size'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Datei-Eintrag + physische Datei loeschen.
     */
    public static function delete(int $id): void
    {
        $file = self::findById($id);
        if ($file) {
            $path = self::STORAGE_PATH . $file['stored_name'];
            if (file_exists($path)) {
                unlink($path);
            }
            Database::execute('DELETE FROM files WHERE id = ?', [$id]);
        }
    }

    /**
     * Gesamtspeicher eines Nutzers in Bytes.
     */
    public static function getTotalSizeByUser(int $userId): int
    {
        $result = Database::queryOne(
            'SELECT COALESCE(SUM(file_size), 0) as total FROM files WHERE owner_id = ?',
            [$userId]
        );
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Zugriffspruefung: eigene Datei oder shared.
     */
    public static function hasAccess(int $fileId, int $userId): bool
    {
        $file = self::findById($fileId);
        if (!$file) {
            return false;
        }
        if ($file['is_shared']) {
            return true;
        }
        return (int) $file['owner_id'] === $userId;
    }

    /**
     * Vollstaendiger Speicherpfad einer Datei.
     */
    public static function getStoragePath(string $storedName): string
    {
        return self::STORAGE_PATH . $storedName;
    }

    /**
     * Dateigroesse menschenlesbar formatieren.
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
