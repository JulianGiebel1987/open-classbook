<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

/**
 * Vertretungsbedarfe fuer Schulbegleiter:innen (schueler-/abwesenheitsbasiert).
 * Anders als bei Lehrkraeften nicht an den Stundenplan gekoppelt, sondern an
 * die Abwesenheit einer Begleitung und deren zugewiesene Schueler:innen.
 */
class AideSubstitution
{
    /** Prioritaetsstufen: 1 = sehr hoch ... 4 = niedrig */
    public const PRIORITIES = [
        1 => 'Sehr hoch',
        2 => 'Hoch',
        3 => 'Mittel',
        4 => 'Niedrig',
    ];

    private const SELECT = 'SELECT sub.*,
                    aa.firstname AS absent_firstname, aa.lastname AS absent_lastname,
                    su.firstname AS substitute_firstname, su.lastname AS substitute_lastname,
                    st.firstname AS student_firstname, st.lastname AS student_lastname,
                    c.name AS class_name
             FROM aide_substitutions sub
             JOIN school_aides aa ON aa.id = sub.absent_aide_id
             LEFT JOIN school_aides su ON su.id = sub.substitute_aide_id
             JOIN students st ON st.id = sub.student_id
             JOIN classes c ON c.id = st.class_id';

    public static function findById(int $id): ?array
    {
        return Database::queryOne(self::SELECT . ' WHERE sub.id = ?', [$id]);
    }

    /**
     * Alle Vertretungsbedarfe, die sich mit dem Zeitraum ueberschneiden,
     * sortiert nach Prioritaet (dringlichste zuerst) und Startdatum.
     */
    public static function findByDateRange(string $dateFrom, string $dateTo): array
    {
        return Database::query(
            self::SELECT . ' WHERE sub.date_from <= ? AND sub.date_to >= ?
             ORDER BY sub.priority ASC, sub.date_from ASC, st.lastname',
            [$dateTo, $dateFrom]
        );
    }

    /**
     * Bestehende Vertretungsbedarfe einer konkreten Abwesenheit.
     */
    public static function findForAbsence(int $absenceAideId): array
    {
        return Database::query(
            self::SELECT . ' WHERE sub.absence_aide_id = ?
             ORDER BY sub.priority ASC, st.lastname',
            [$absenceAideId]
        );
    }

    /**
     * Vertretungen, in denen die Begleitung als Ersatz eingeteilt ist
     * (Sicht "Meine Vertretungen").
     */
    public static function findUpcomingForAide(int $aideId, string $fromDate): array
    {
        return Database::query(
            self::SELECT . ' WHERE sub.substitute_aide_id = ? AND sub.date_to >= ?
             ORDER BY sub.date_from ASC, sub.priority ASC',
            [$aideId, $fromDate]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO aide_substitutions
                (date_from, date_to, absent_aide_id, student_id, substitute_aide_id,
                 absence_aide_id, priority, status, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['date_from'],
                $data['date_to'],
                $data['absent_aide_id'],
                $data['student_id'],
                $data['substitute_aide_id'] ?? null,
                $data['absence_aide_id'] ?? null,
                $data['priority'] ?? 3,
                $data['status'] ?? 'offen',
                $data['notes'] ?? null,
                $data['created_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE aide_substitutions SET substitute_aide_id = ?, priority = ?, status = ?, notes = ? WHERE id = ?',
            [
                $data['substitute_aide_id'] ?? null,
                $data['priority'] ?? 3,
                $data['status'] ?? 'offen',
                $data['notes'] ?? null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM aide_substitutions WHERE id = ?', [$id]);
    }

    /**
     * Bestehenden Bedarf fuer (Abwesenheit, Kind) finden, falls vorhanden.
     */
    public static function findForAbsenceAndStudent(int $absenceAideId, int $studentId): ?array
    {
        return Database::queryOne(
            self::SELECT . ' WHERE sub.absence_aide_id = ? AND sub.student_id = ?',
            [$absenceAideId, $studentId]
        );
    }

    /**
     * Abwesende Schulbegleiter:innen im Zeitraum (mit Abwesenheitsdaten).
     */
    public static function getAbsentAidesForDateRange(string $dateFrom, string $dateTo): array
    {
        return Database::query(
            'SELECT ab.id AS absence_id, ab.aide_id, ab.type AS absence_type,
                    ab.reason AS absence_reason, ab.date_from, ab.date_to,
                    sa.firstname, sa.lastname
             FROM absences_school_aides ab
             JOIN school_aides sa ON sa.id = ab.aide_id
             WHERE ab.date_from <= ? AND ab.date_to >= ?
             ORDER BY ab.date_from, sa.lastname, sa.firstname',
            [$dateTo, $dateFrom]
        );
    }

    /**
     * Aktive Begleitungen, die im Zeitraum nicht selbst abwesend sind
     * und daher als Vertretung in Frage kommen.
     */
    public static function getAvailableAides(string $dateFrom, string $dateTo, int $excludeAideId = 0): array
    {
        return Database::query(
            'SELECT sa.id, sa.firstname, sa.lastname
             FROM school_aides sa
             JOIN users u ON u.id = sa.user_id
             WHERE u.active = 1
               AND sa.id != ?
               AND sa.id NOT IN (
                   SELECT ab.aide_id FROM absences_school_aides ab
                   WHERE ab.date_from <= ? AND ab.date_to >= ?
               )
             ORDER BY sa.lastname, sa.firstname',
            [$excludeAideId, $dateTo, $dateFrom]
        );
    }

    public static function priorityLabel(int $priority): string
    {
        return self::PRIORITIES[$priority] ?? (string) $priority;
    }
}
