<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Setting
{
    /**
     * Einzelnen Einstellungswert laden
     */
    public static function get(string $key): ?string
    {
        $result = Database::queryOne(
            'SELECT setting_value FROM settings WHERE setting_key = ?',
            [$key]
        );

        return $result['setting_value'] ?? null;
    }

    /**
     * Einstellungswert speichern oder aktualisieren
     */
    public static function set(string $key, ?string $value): void
    {
        $existing = Database::queryOne(
            'SELECT id FROM settings WHERE setting_key = ?',
            [$key]
        );

        if ($existing) {
            Database::execute(
                'UPDATE settings SET setting_value = ? WHERE setting_key = ?',
                [$value, $key]
            );
        } else {
            Database::execute(
                'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)',
                [$key, $value]
            );
        }
    }

    /**
     * Alle Einstellungen laden
     */
    public static function getAll(): array
    {
        $rows = Database::query('SELECT setting_key, setting_value FROM settings');
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Mehrere Einstellungen auf einmal laden
     */
    public static function getMultiple(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = Database::query(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ({$placeholders})",
            $keys
        );

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
