<?php

namespace OpenClassbook\Services;

use OpenClassbook\Database;

/**
 * DSGVO-Datenauskunft / -export (Art. 15 Auskunftsrecht, Art. 20 Datenportabilität).
 *
 * Aggregiert alle zu einem Benutzerkonto gespeicherten personenbezogenen Daten
 * in eine strukturierte, maschinenlesbare Form. Sensible Sicherheitsattribute
 * (Passwort-Hash, Reset-Token, 2FA-Secrets) werden bewusst NICHT exportiert.
 */
class DataExportService
{
    /**
     * Attribute des Benutzerkontos, die aus Sicherheitsgründen nicht
     * exportiert werden.
     */
    private const SENSITIVE_USER_FIELDS = [
        'password_hash',
        'password_reset_token',
        'password_reset_expires',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Alle personenbezogenen Daten eines Nutzers als strukturiertes Array.
     *
     * @return array<string,mixed>|null null, wenn der Nutzer nicht existiert.
     */
    public static function exportUser(int $userId): ?array
    {
        $user = Database::queryOne('SELECT * FROM users WHERE id = ?', [$userId]);
        if ($user === null) {
            return null;
        }

        foreach (self::SENSITIVE_USER_FIELDS as $field) {
            unset($user[$field]);
        }

        $export = [
            'meta' => [
                'exportiert_am'  => date('c'),
                'hinweis'        => 'DSGVO-Auskunft (Art. 15). Sicherheitsattribute wie Passwort-Hash und 2FA-Geheimnisse sind aus Datenschutzgründen nicht enthalten.',
                'benutzer_id'    => (int) $userId,
            ],
            'konto'                 => $user,
            'lehrerdaten'           => self::teacherData($userId),
            'schuelerdaten'         => self::studentData($userId),
            'gesendete_nachrichten' => self::sentMessages($userId),
            'audit_protokoll'       => self::auditEntries($userId),
        ];

        return $export;
    }

    /**
     * Lehrerdatensatz inkl. Abwesenheiten und selbst verfasster Klassenbucheinträge.
     */
    private static function teacherData(int $userId): ?array
    {
        $teacher = Database::queryOne('SELECT * FROM teachers WHERE user_id = ?', [$userId]);
        if ($teacher === null) {
            return null;
        }

        $teacherId = (int) $teacher['id'];
        $teacher['abwesenheiten'] = Database::query(
            'SELECT date_from, date_to, type, reason, notes, created_at
               FROM absences_teachers WHERE teacher_id = ? ORDER BY date_from DESC',
            [$teacherId]
        );
        $teacher['klassenbucheintraege'] = Database::query(
            'SELECT class_id, entry_date, lesson, topic, notes, created_at
               FROM classbook_entries WHERE teacher_id = ? ORDER BY entry_date DESC',
            [$teacherId]
        );

        return $teacher;
    }

    /**
     * Schülerdatensatz inkl. Fehlzeiten.
     */
    private static function studentData(int $userId): ?array
    {
        $student = Database::queryOne('SELECT * FROM students WHERE user_id = ?', [$userId]);
        if ($student === null) {
            return null;
        }

        $student['fehlzeiten'] = Database::query(
            'SELECT date_from, date_to, excused, reason, notes, created_at
               FROM absences_students WHERE student_id = ? ORDER BY date_from DESC',
            [(int) $student['id']]
        );

        return $student;
    }

    /**
     * Vom Nutzer gesendete Nachrichten (1:1 und Gruppen), Inhalte entschlüsselt.
     */
    private static function sentMessages(int $userId): array
    {
        $direct = Database::query(
            'SELECT conversation_id, body, read_at, created_at
               FROM messages WHERE sender_id = ? ORDER BY created_at DESC',
            [$userId]
        );
        foreach ($direct as &$m) {
            $m['body'] = EncryptionService::decrypt($m['body']);
        }
        unset($m);

        $group = Database::query(
            'SELECT group_id, body, created_at
               FROM group_messages WHERE sender_id = ? ORDER BY created_at DESC',
            [$userId]
        );
        foreach ($group as &$g) {
            $g['body'] = EncryptionService::decrypt($g['body']);
        }
        unset($g);

        return [
            'einzelnachrichten' => $direct,
            'gruppennachrichten' => $group,
        ];
    }

    /**
     * Audit-Protokolleinträge des Nutzers (IP bereits pseudonymisiert gespeichert).
     */
    private static function auditEntries(int $userId): array
    {
        return Database::query(
            'SELECT action, entity_type, entity_id, details, ip_address, created_at
               FROM audit_log WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }
}
