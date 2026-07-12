<?php

namespace OpenClassbook\Models;

use OpenClassbook\Database;

class Substitution
{
    public static function findById(int $id): ?array
    {
        return Database::queryOne(
            'SELECT s.*,
                    at.firstname as absent_firstname, at.lastname as absent_lastname, at.abbreviation as absent_abbreviation,
                    st.firstname as substitute_firstname, st.lastname as substitute_lastname, st.abbreviation as substitute_abbreviation,
                    c.name as class_name
             FROM substitutions s
             JOIN teachers at ON at.id = s.absent_teacher_id
             LEFT JOIN teachers st ON st.id = s.substitute_teacher_id
             JOIN classes c ON c.id = s.class_id
             WHERE s.id = ?',
            [$id]
        );
    }

    public static function findByDate(int $settingId, string $date): array
    {
        return Database::query(
            'SELECT s.*,
                    at.firstname as absent_firstname, at.lastname as absent_lastname, at.abbreviation as absent_abbreviation,
                    st.firstname as substitute_firstname, st.lastname as substitute_lastname, st.abbreviation as substitute_abbreviation,
                    c.name as class_name
             FROM substitutions s
             JOIN teachers at ON at.id = s.absent_teacher_id
             LEFT JOIN teachers st ON st.id = s.substitute_teacher_id
             JOIN classes c ON c.id = s.class_id
             WHERE s.timetable_setting_id = ? AND s.date = ?
             ORDER BY s.slot_number, c.name',
            [$settingId, $date]
        );
    }

    public static function findByDateAndSubstitute(int $settingId, string $date, int $teacherId): array
    {
        return Database::query(
            'SELECT s.*, c.name as class_name,
                    at.firstname as absent_firstname, at.lastname as absent_lastname, at.abbreviation as absent_abbreviation
             FROM substitutions s
             JOIN classes c ON c.id = s.class_id
             JOIN teachers at ON at.id = s.absent_teacher_id
             WHERE s.timetable_setting_id = ? AND s.date = ? AND s.substitute_teacher_id = ?
             ORDER BY s.slot_number',
            [$settingId, $date, $teacherId]
        );
    }

    public static function findUpcomingForTeacher(int $settingId, int $teacherId, string $fromDate): array
    {
        return Database::query(
            'SELECT s.*, c.name as class_name,
                    at.firstname as absent_firstname, at.lastname as absent_lastname, at.abbreviation as absent_abbreviation,
                    sp.is_published
             FROM substitutions s
             JOIN classes c ON c.id = s.class_id
             JOIN teachers at ON at.id = s.absent_teacher_id
             JOIN substitution_plans sp ON sp.timetable_setting_id = s.timetable_setting_id AND sp.date = s.date
             WHERE s.timetable_setting_id = ?
               AND s.substitute_teacher_id = ?
               AND s.date >= ?
               AND sp.is_published = 1
             ORDER BY s.date, s.slot_number',
            [$settingId, $teacherId, $fromDate]
        );
    }

    public static function findAbsentTeacherEntries(int $settingId, int $teacherId, string $fromDate): array
    {
        return Database::query(
            'SELECT s.*, c.name as class_name,
                    st.firstname as substitute_firstname, st.lastname as substitute_lastname, st.abbreviation as substitute_abbreviation,
                    sp.is_published
             FROM substitutions s
             JOIN classes c ON c.id = s.class_id
             LEFT JOIN teachers st ON st.id = s.substitute_teacher_id
             JOIN substitution_plans sp ON sp.timetable_setting_id = s.timetable_setting_id AND sp.date = s.date
             WHERE s.timetable_setting_id = ?
               AND s.absent_teacher_id = ?
               AND s.date >= ?
               AND sp.is_published = 1
             ORDER BY s.date, s.slot_number',
            [$settingId, $teacherId, $fromDate]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO substitutions (timetable_setting_id, date, day_of_week, slot_number, class_id,
                absent_teacher_id, substitute_teacher_id, absence_teacher_id, subject, room, notes, is_cancelled, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['timetable_setting_id'],
                $data['date'],
                $data['day_of_week'],
                $data['slot_number'],
                $data['class_id'],
                $data['absent_teacher_id'],
                $data['substitute_teacher_id'] ?? null,
                $data['absence_teacher_id'] ?? null,
                $data['subject'] ?? null,
                $data['room'] ?? null,
                $data['notes'] ?? null,
                $data['is_cancelled'] ?? 0,
                $data['created_by'],
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE substitutions SET substitute_teacher_id = ?, subject = ?, room = ?, notes = ?, is_cancelled = ? WHERE id = ?',
            [
                $data['substitute_teacher_id'] ?? null,
                $data['subject'] ?? null,
                $data['room'] ?? null,
                $data['notes'] ?? null,
                $data['is_cancelled'] ?? 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM substitutions WHERE id = ?', [$id]);
    }

    /**
     * Offene Slots ermitteln: Stundenplan-Einträge abwesender Lehrer, die noch keine Vertretung haben.
     */
    public static function getOpenSlots(int $settingId, string $date): array
    {
        $dayOfWeek = (int) date('N', strtotime($date));

        return Database::query(
            'SELECT ts.id as timetable_slot_id, ts.slot_number, ts.subject, ts.room,
                    ts.teacher_id as absent_teacher_id,
                    t.firstname as absent_firstname, t.lastname as absent_lastname, t.abbreviation as absent_abbreviation,
                    c.id as class_id, c.name as class_name,
                    ab.id as absence_id, ab.type as absence_type, ab.reason as absence_reason
             FROM timetable_slots ts
             JOIN teachers t ON t.id = ts.teacher_id
             JOIN classes c ON c.id = ts.class_id
             JOIN absences_teachers ab ON ab.teacher_id = ts.teacher_id
                  AND ab.date_from <= ? AND ab.date_to >= ?
             WHERE ts.timetable_setting_id = ?
               AND ts.day_of_week = ?
               AND NOT EXISTS (
                   SELECT 1 FROM substitutions s
                   WHERE s.timetable_setting_id = ts.timetable_setting_id
                     AND s.date = ?
                     AND s.slot_number = ts.slot_number
                     AND s.class_id = ts.class_id
                     AND s.absent_teacher_id = ts.teacher_id
               )
             ORDER BY ts.slot_number, c.name',
            [$date, $date, $settingId, $dayOfWeek, $date]
        );
    }

    /**
     * Offene Slots einer bestimmten abwesenden Lehrkraft ermitteln
     * (fuer die Ganztags-Vertretung mit einem Klick).
     */
    public static function getOpenSlotsForTeacher(int $settingId, string $date, int $absentTeacherId): array
    {
        $dayOfWeek = (int) date('N', strtotime($date));

        return Database::query(
            'SELECT ts.id as timetable_slot_id, ts.slot_number, ts.subject, ts.room,
                    ts.teacher_id as absent_teacher_id,
                    t.firstname as absent_firstname, t.lastname as absent_lastname, t.abbreviation as absent_abbreviation,
                    c.id as class_id, c.name as class_name,
                    ab.id as absence_id, ab.type as absence_type, ab.reason as absence_reason
             FROM timetable_slots ts
             JOIN teachers t ON t.id = ts.teacher_id
             JOIN classes c ON c.id = ts.class_id
             JOIN absences_teachers ab ON ab.teacher_id = ts.teacher_id
                  AND ab.date_from <= ? AND ab.date_to >= ?
             WHERE ts.timetable_setting_id = ?
               AND ts.day_of_week = ?
               AND ts.teacher_id = ?
               AND NOT EXISTS (
                   SELECT 1 FROM substitutions s
                   WHERE s.timetable_setting_id = ts.timetable_setting_id
                     AND s.date = ?
                     AND s.slot_number = ts.slot_number
                     AND s.class_id = ts.class_id
                     AND s.absent_teacher_id = ts.teacher_id
               )
             ORDER BY ts.slot_number, c.name',
            [$date, $date, $settingId, $dayOfWeek, $absentTeacherId, $date]
        );
    }

    /**
     * Anzahl offener Slots je abwesender Lehrkraft an einem Datum.
     * Rueckgabe: [absent_teacher_id => Anzahl]
     */
    public static function countOpenSlotsByTeacher(int $settingId, string $date): array
    {
        $rows = self::getOpenSlots($settingId, $date);
        $counts = [];
        foreach ($rows as $row) {
            $tid = (int) $row['absent_teacher_id'];
            $counts[$tid] = ($counts[$tid] ?? 0) + 1;
        }
        return $counts;
    }

    /**
     * Abwesende Lehrer für ein Datum ermitteln.
     */
    public static function getAbsentTeachersForDate(string $date): array
    {
        return Database::query(
            'SELECT DISTINCT t.id, t.firstname, t.lastname, t.abbreviation,
                    ab.type as absence_type, ab.reason as absence_reason, ab.date_from, ab.date_to, ab.id as absence_id
             FROM absences_teachers ab
             JOIN teachers t ON t.id = ab.teacher_id
             WHERE ab.date_from <= ? AND ab.date_to >= ?
             ORDER BY t.lastname, t.firstname',
            [$date, $date]
        );
    }

    /**
     * Prüfen ob Vertretungslehrer im selben Slot bereits belegt ist
     * (regulaerer Unterricht oder andere Vertretung).
     */
    public static function checkSubstituteConflict(int $settingId, string $date, int $teacherId, int $slotNumber): array
    {
        $dayOfWeek = (int) date('N', strtotime($date));
        $conflicts = [];

        // Regulaerer Stundenplan
        $regular = Database::queryOne(
            'SELECT ts.*, c.name as class_name
             FROM timetable_slots ts
             JOIN classes c ON c.id = ts.class_id
             WHERE ts.timetable_setting_id = ? AND ts.teacher_id = ? AND ts.day_of_week = ? AND ts.slot_number = ?',
            [$settingId, $teacherId, $dayOfWeek, $slotNumber]
        );
        if ($regular) {
            $conflicts[] = [
                'type' => 'regular',
                'message' => 'Hat regulaeren Unterricht in Klasse ' . $regular['class_name'],
                'class_name' => $regular['class_name'],
            ];
        }

        // Andere Vertretung am selben Tag/Slot
        $otherSub = Database::queryOne(
            'SELECT s.*, c.name as class_name
             FROM substitutions s
             JOIN classes c ON c.id = s.class_id
             WHERE s.timetable_setting_id = ? AND s.date = ? AND s.substitute_teacher_id = ? AND s.slot_number = ?',
            [$settingId, $date, $teacherId, $slotNumber]
        );
        if ($otherSub) {
            $conflicts[] = [
                'type' => 'substitution',
                'message' => 'Vertritt bereits in Klasse ' . $otherSub['class_name'],
                'class_name' => $otherSub['class_name'],
            ];
        }

        return $conflicts;
    }

    /**
     * Verfügbare Lehrer für einen Slot ermitteln.
     * Gibt alle Lehrer zurück, kategorisiert als "frei" oder "belegt".
     */
    public static function getAvailableTeachers(int $settingId, string $date, int $slotNumber): array
    {
        $dayOfWeek = (int) date('N', strtotime($date));

        // Alle Lehrer
        $allTeachers = Database::query(
            'SELECT t.id, t.firstname, t.lastname, t.abbreviation, t.subjects
             FROM teachers t
             JOIN users u ON u.id = t.user_id
             WHERE u.active = 1
             ORDER BY t.lastname, t.firstname'
        );

        // Abwesende Lehrer an diesem Datum
        $absentIds = Database::query(
            'SELECT DISTINCT teacher_id FROM absences_teachers WHERE date_from <= ? AND date_to >= ?',
            [$date, $date]
        );
        $absentSet = array_column($absentIds, 'teacher_id');

        // Lehrer mit regulaerem Unterricht in diesem Slot
        $busyRegular = Database::query(
            'SELECT teacher_id, class_id FROM timetable_slots
             WHERE timetable_setting_id = ? AND day_of_week = ? AND slot_number = ?',
            [$settingId, $dayOfWeek, $slotNumber]
        );
        $busyRegularMap = [];
        foreach ($busyRegular as $b) {
            $busyRegularMap[$b['teacher_id']] = true;
        }

        // Lehrer mit anderer Vertretung in diesem Slot
        $busySub = Database::query(
            'SELECT substitute_teacher_id FROM substitutions
             WHERE timetable_setting_id = ? AND date = ? AND slot_number = ? AND substitute_teacher_id IS NOT NULL',
            [$settingId, $date, $slotNumber]
        );
        $busySubSet = array_column($busySub, 'substitute_teacher_id');

        $result = [];
        foreach ($allTeachers as $t) {
            // Abwesende ausschließen
            if (in_array($t['id'], $absentSet)) {
                continue;
            }

            $status = 'available';
            $info = '';

            if (isset($busyRegularMap[$t['id']])) {
                $status = 'busy_regular';
                $info = 'Regulaerer Unterricht';
            }

            if (in_array($t['id'], $busySubSet)) {
                $status = 'busy_substitution';
                $info = 'Andere Vertretung';
            }

            $t['status'] = $status;
            $t['status_info'] = $info;
            $result[] = $t;
        }

        // Freie zuerst, dann belegte
        usort($result, function ($a, $b) {
            $order = ['available' => 0, 'busy_regular' => 1, 'busy_substitution' => 2];
            return ($order[$a['status']] ?? 9) - ($order[$b['status']] ?? 9);
        });

        return $result;
    }

    /**
     * Verfügbare Lehrer für einen ganzen Tag (mehrere Einheiten) ermitteln.
     * Markiert jede Lehrkraft als "frei" (in allen Einheiten verfügbar) oder
     * "teilweise belegt" (in einzelnen Einheiten Unterricht/Vertretung).
     *
     * @param int[] $slotNumbers Einheitsnummern der zu vertretenden Stunden
     */
    public static function getAvailableTeachersForDay(int $settingId, string $date, array $slotNumbers): array
    {
        $slotNumbers = array_values(array_unique(array_map('intval', $slotNumbers)));
        if (empty($slotNumbers)) {
            return [];
        }

        $dayOfWeek = (int) date('N', strtotime($date));
        $placeholders = implode(',', array_fill(0, count($slotNumbers), '?'));

        // Alle aktiven Lehrer
        $allTeachers = Database::query(
            'SELECT t.id, t.firstname, t.lastname, t.abbreviation, t.subjects
             FROM teachers t
             JOIN users u ON u.id = t.user_id
             WHERE u.active = 1
             ORDER BY t.lastname, t.firstname'
        );

        // Abwesende Lehrer an diesem Datum
        $absentIds = Database::query(
            'SELECT DISTINCT teacher_id FROM absences_teachers WHERE date_from <= ? AND date_to >= ?',
            [$date, $date]
        );
        $absentSet = array_column($absentIds, 'teacher_id');

        // Belegte Einheiten pro Lehrer aus dem regulaeren Stundenplan
        $busyMap = [];
        $regular = Database::query(
            'SELECT teacher_id, slot_number FROM timetable_slots
             WHERE timetable_setting_id = ? AND day_of_week = ? AND slot_number IN (' . $placeholders . ')',
            array_merge([$settingId, $dayOfWeek], $slotNumbers)
        );
        foreach ($regular as $r) {
            $busyMap[(int) $r['teacher_id']][(int) $r['slot_number']] = true;
        }

        // Belegte Einheiten pro Lehrer aus bestehenden Vertretungen
        $subs = Database::query(
            'SELECT substitute_teacher_id, slot_number FROM substitutions
             WHERE timetable_setting_id = ? AND date = ? AND substitute_teacher_id IS NOT NULL
               AND slot_number IN (' . $placeholders . ')',
            array_merge([$settingId, $date], $slotNumbers)
        );
        foreach ($subs as $s) {
            $busyMap[(int) $s['substitute_teacher_id']][(int) $s['slot_number']] = true;
        }

        $totalSlots = count($slotNumbers);
        $result = [];
        foreach ($allTeachers as $t) {
            if (in_array($t['id'], $absentSet)) {
                continue;
            }

            $busyCount = isset($busyMap[(int) $t['id']]) ? count($busyMap[(int) $t['id']]) : 0;

            if ($busyCount === 0) {
                $t['status'] = 'available';
                $t['status_info'] = '';
            } else {
                $t['status'] = 'busy_partial';
                $t['status_info'] = 'In ' . $busyCount . ' von ' . $totalSlots . ' Einheiten belegt';
            }
            $t['busy_count'] = $busyCount;
            $result[] = $t;
        }

        // Vollstaendig freie zuerst, dann nach Anzahl belegter Einheiten
        usort($result, function ($a, $b) {
            return $a['busy_count'] - $b['busy_count'];
        });

        return $result;
    }
}
