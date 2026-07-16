<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

/**
 * Protokoll aller Klassenwechsel (Versetzungen) von Schueler:innen.
 * Jeder Eintrag haelt fest, von welcher in welche Klasse eine Schueler:in
 * versetzt wurde, wann und durch wen.
 */
class StudentClassHistory
{
    /**
     * Einen Versetzungsvorgang protokollieren.
     */
    public static function record(int $studentId, ?int $fromClassId, int $toClassId, ?int $changedBy): void
    {
        Database::execute(
            'INSERT INTO student_class_history (student_id, from_class_id, to_class_id, changed_by)
             VALUES (?, ?, ?, ?)',
            [$studentId, $fromClassId, $toClassId, $changedBy]
        );
    }

    /**
     * Historie fuer eine Klasse: alle Vorgaenge, bei denen die Klasse Quelle
     * oder Ziel war. Liefert lesbare Namen fuer Schueler:in, Klassen und Bearbeiter:in.
     */
    public static function findByClassId(int $classId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 500));
        return Database::query(
            'SELECT h.*,
                    s.firstname AS student_firstname,
                    s.lastname AS student_lastname,
                    fc.name AS from_class_name,
                    tc.name AS to_class_name,
                    u.username AS changed_by_username
             FROM student_class_history h
             JOIN students s ON s.id = h.student_id
             LEFT JOIN classes fc ON fc.id = h.from_class_id
             LEFT JOIN classes tc ON tc.id = h.to_class_id
             LEFT JOIN users u ON u.id = h.changed_by
             WHERE h.from_class_id = ? OR h.to_class_id = ?
             ORDER BY h.changed_at DESC, h.id DESC
             LIMIT ' . $limit,
            [$classId, $classId]
        );
    }
}
