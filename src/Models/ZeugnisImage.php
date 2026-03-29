<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ZeugnisImage
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT * FROM zeugnis_images WHERE id = ?',
            [$id]
        );
    }

    public static function findByTemplate(int $templateId): array
    {
        return Database::query(
            'SELECT * FROM zeugnis_images WHERE template_id = ? ORDER BY created_at ASC',
            [$templateId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO zeugnis_images (template_id, original_name, stored_name, mime_type, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['template_id'],
                $data['original_name'],
                $data['stored_name'],
                $data['mime_type'],
                $data['file_size'],
                $data['uploaded_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    /**
     * Deletes DB record and associated file from filesystem.
     */
    public static function delete(int $id): void
    {
        $image = self::findById($id);
        if ($image) {
            $path = self::storagePath($image['stored_name']);
            if (file_exists($path)) {
                unlink($path);
            }
        }
        Database::execute('DELETE FROM zeugnis_images WHERE id = ?', [$id]);
    }

    public static function storagePath(string $storedName): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads/zeugnis/' . $storedName;
    }

    public static function publicUrl(string $storedName): string
    {
        return '/zeugnis/images/' . rawurlencode($storedName);
    }
}
