<?php

namespace OpenClassbook\Services;

class ZeugnisPlaceholderService
{
    /**
     * All supported placeholder tokens with their labels for the editor UI.
     *
     * @return array<string, string> token => label
     */
    public static function getAvailableTokens(): array
    {
        return [
            '{{student_firstname}}'  => 'Vorname Schüler/in',
            '{{student_lastname}}'   => 'Nachname Schüler/in',
            '{{student_name}}'       => 'Vollständiger Name Schüler/in',
            '{{student_birthday}}'   => 'Geburtsdatum Schüler/in',
            '{{class}}'              => 'Klasse',
            '{{school_year}}'        => 'Schuljahr',
            '{{school_name}}'        => 'Schulname',
            '{{date_today}}'         => 'Heutiges Datum',
            '{{date_day}}'           => 'Tag (zweistellig)',
            '{{date_month}}'         => 'Monat (zweistellig)',
            '{{date_year}}'          => 'Jahr (vierstellig)',
        ];
    }

    /**
     * Build token map from student and class data.
     *
     * @param array $student  Row from students table (with class_name if joined)
     * @param array $class    Row from classes table (optional, can be empty)
     * @return array<string, string>
     */
    public static function getStudentTokens(array $student, array $class = []): array
    {
        $schoolName = defined('APP_SCHOOL_NAME') ? APP_SCHOOL_NAME : 'Schule';
        $today = new \DateTimeImmutable('today');

        return [
            '{{student_firstname}}' => htmlspecialchars($student['firstname'] ?? $student['first_name'] ?? '', ENT_QUOTES, 'UTF-8'),
            '{{student_lastname}}'  => htmlspecialchars($student['lastname'] ?? $student['last_name'] ?? '', ENT_QUOTES, 'UTF-8'),
            '{{student_name}}'      => htmlspecialchars(
                trim(($student['firstname'] ?? $student['first_name'] ?? '') . ' ' . ($student['lastname'] ?? $student['last_name'] ?? '')),
                ENT_QUOTES,
                'UTF-8'
            ),
            '{{student_birthday}}'  => isset($student['birthday']) && $student['birthday']
                ? (new \DateTimeImmutable($student['birthday']))->format('d.m.Y')
                : (isset($student['date_of_birth']) && $student['date_of_birth']
                    ? (new \DateTimeImmutable($student['date_of_birth']))->format('d.m.Y')
                    : ''),
            '{{class}}'             => htmlspecialchars(
                $class['name'] ?? $student['class_name'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ),
            '{{school_year}}'       => htmlspecialchars(
                $class['school_year'] ?? $student['school_year'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ),
            '{{school_name}}'       => htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'),
            '{{date_today}}'        => $today->format('d.m.Y'),
            '{{date_day}}'          => $today->format('d'),
            '{{date_month}}'        => $today->format('m'),
            '{{date_year}}'         => $today->format('Y'),
        ];
    }

    /**
     * Replace all known tokens in a string with their resolved values.
     */
    public static function resolvePlaceholders(string $text, array $tokens): string
    {
        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }
}
