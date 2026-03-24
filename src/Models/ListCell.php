<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class ListCell
{
    /**
     * Alle Zellen einer Liste als assoziatives Array: [row_id][column_id] => value
     */
    public static function findByList(int $listId): array
    {
        $rows = Database::query(
            'SELECT row_id, column_id, value FROM list_cells WHERE list_id = ?',
            [$listId]
        );

        $cells = [];
        foreach ($rows as $row) {
            $cells[$row['row_id']][$row['column_id']] = $row['value'];
        }
        return $cells;
    }

    /**
     * Zellenwert setzen oder aktualisieren (Upsert).
     */
    public static function upsert(int $rowId, int $columnId, int $listId, ?string $value): void
    {
        Database::execute(
            'INSERT INTO list_cells (list_id, row_id, column_id, value)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = ?',
            [$listId, $rowId, $columnId, $value, $value]
        );
    }
}
