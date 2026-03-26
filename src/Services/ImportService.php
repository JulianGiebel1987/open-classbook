<?php

namespace OpenClassbook\Services;

use OpenClassbook\Models\User;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\SchoolClass;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    /**
     * CSV-Datei einlesen (Semikolon oder Komma als Trennzeichen, BOM-tolerant)
     * Gibt alle Datenzeilen ab Zeile 2 (nach dem Header) zurück.
     */
    private static function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // UTF-8 BOM entfernen falls vorhanden
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Trennzeichen automatisch erkennen
        $headerLine = fgets($handle);
        $delimiter = (substr_count($headerLine, ';') >= substr_count($headerLine, ',')) ? ';' : ',';
        rewind($handle);
        // BOM ggf. erneut ueberspringen
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Header-Zeile ueberspringen
        fgetcsv($handle, 0, $delimiter);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row = array_map('trim', $row);
            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Lehrer-Import aus Excel-Datei
     * Spalten: A=Vorname, B=Nachname, C=Kuerzel, D=E-Mail, E=Faecher, F=Klassen
     */
    public static function previewTeachers(string $filePath): array
    {
        $rows = [];
        $errors = [];
        $isCsv = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv';

        if ($isCsv) {
            $rawRows = self::parseCsvFile($filePath);
            $rowIndex = 2;
            foreach ($rawRows as $cells) {
                $cells = array_pad($cells, 6, '');
                [$firstname, $lastname, $abbreviation, $email, $subjects, $classes] = $cells;
                $rowErrors = [];
                if (empty($firstname) && empty($lastname)) { $rowIndex++; continue; }
                if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
                if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';
                if (empty($abbreviation)) $rowErrors[] = 'Kuerzel fehlt';
                if (empty($email)) $rowErrors[] = 'E-Mail fehlt';
                if (!empty($abbreviation) && Teacher::abbreviationExists($abbreviation)) {
                    $rowErrors[] = 'Kuerzel "' . $abbreviation . '" existiert bereits';
                }
                $rows[] = compact('firstname', 'lastname', 'abbreviation', 'email', 'subjects', 'classes') + ['row' => $rowIndex, 'errors' => $rowErrors];
                if (!empty($rowErrors)) $errors[] = "Zeile {$rowIndex}: " . implode(', ', $rowErrors);
                $rowIndex++;
            }
            return ['rows' => $rows, 'errors' => $errors];
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator(2) as $row) { // Ab Zeile 2 (Zeile 1 = Header)
            $rowIndex = $row->getRowIndex();
            $cells = [];
            foreach ($row->getCellIterator('A', 'F') as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            [$firstname, $lastname, $abbreviation, $email, $subjects, $classes] = $cells;

            if (empty($firstname) && empty($lastname)) {
                continue; // Leere Zeile
            }

            $rowErrors = [];
            if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
            if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';
            if (empty($abbreviation)) $rowErrors[] = 'Kuerzel fehlt';
            if (empty($email)) $rowErrors[] = 'E-Mail fehlt';

            if (!empty($abbreviation) && Teacher::abbreviationExists($abbreviation)) {
                $rowErrors[] = 'Kuerzel "' . $abbreviation . '" existiert bereits';
            }

            $rows[] = [
                'row' => $rowIndex,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'abbreviation' => $abbreviation,
                'email' => $email,
                'subjects' => $subjects,
                'classes' => $classes,
                'errors' => $rowErrors,
            ];

            if (!empty($rowErrors)) {
                $errors[] = "Zeile {$rowIndex}: " . implode(', ', $rowErrors);
            }
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Lehrer tatsaechlich importieren
     */
    public static function importTeachers(string $filePath): array
    {
        $preview = self::previewTeachers($filePath);
        $imported = 0;
        $skipped = 0;

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            // User-Account erstellen
            $password = bin2hex(random_bytes(5));
            $username = strtolower($row['abbreviation']);

            // Sicherstellen, dass Username eindeutig ist
            $baseUsername = $username;
            $counter = 1;
            while (User::usernameExists($username)) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $userId = User::create([
                'username' => $username,
                'email' => $row['email'],
                'password' => $password,
                'role' => 'lehrer',
                'must_change_password' => 1,
            ]);

            Teacher::create([
                'user_id' => $userId,
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'abbreviation' => $row['abbreviation'],
                'subjects' => $row['subjects'] ?: null,
            ]);

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $preview['errors']];
    }

    /**
     * Schueler-Import aus Excel-Datei
     * Spalten: A=Vorname, B=Nachname, C=Klasse, D=Geburtsdatum, E=Erziehungsberechtigten-Email
     */
    public static function previewStudents(string $filePath, string $schoolYear): array
    {
        $rows = [];
        $errors = [];
        $isCsv = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv';

        if ($isCsv) {
            $rawRows = self::parseCsvFile($filePath);
            $rowIndex = 2;
            foreach ($rawRows as $cells) {
                $cells = array_pad($cells, 5, '');
                [$firstname, $lastname, $className, $birthday, $guardianEmail] = $cells;
                if (empty($firstname) && empty($lastname)) { $rowIndex++; continue; }
                $rowErrors = [];
                if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
                if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';
                if (empty($className)) {
                    $rowErrors[] = 'Klasse fehlt';
                } else {
                    $class = SchoolClass::findByName($className, $schoolYear);
                    if (!$class) $rowErrors[] = 'Klasse "' . $className . '" nicht gefunden';
                }
                $parsedBirthday = null;
                if (!empty($birthday)) {
                    $date = \DateTime::createFromFormat('d.m.Y', $birthday);
                    if ($date) {
                        $parsedBirthday = $date->format('Y-m-d');
                    } else {
                        $rowErrors[] = 'Ungueltiges Datumsformat (erwartet: TT.MM.JJJJ)';
                    }
                }
                $rows[] = ['row' => $rowIndex, 'firstname' => $firstname, 'lastname' => $lastname, 'class_name' => $className, 'birthday' => $parsedBirthday, 'guardian_email' => $guardianEmail, 'errors' => $rowErrors];
                if (!empty($rowErrors)) $errors[] = "Zeile {$rowIndex}: " . implode(', ', $rowErrors);
                $rowIndex++;
            }
            return ['rows' => $rows, 'errors' => $errors];
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();
            $cells = [];
            foreach ($row->getCellIterator('A', 'E') as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            [$firstname, $lastname, $className, $birthday, $guardianEmail] = $cells;

            if (empty($firstname) && empty($lastname)) {
                continue;
            }

            $rowErrors = [];
            if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
            if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';
            if (empty($className)) {
                $rowErrors[] = 'Klasse fehlt';
            } else {
                $class = SchoolClass::findByName($className, $schoolYear);
                if (!$class) {
                    $rowErrors[] = 'Klasse "' . $className . '" nicht gefunden';
                }
            }

            // Geburtsdatum validieren
            $parsedBirthday = null;
            if (!empty($birthday)) {
                $date = \DateTime::createFromFormat('d.m.Y', $birthday);
                if ($date) {
                    $parsedBirthday = $date->format('Y-m-d');
                } else {
                    $rowErrors[] = 'Ungueltiges Datumsformat (erwartet: TT.MM.JJJJ)';
                }
            }

            $rows[] = [
                'row' => $rowIndex,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'class_name' => $className,
                'birthday' => $parsedBirthday,
                'guardian_email' => $guardianEmail,
                'errors' => $rowErrors,
            ];

            if (!empty($rowErrors)) {
                $errors[] = "Zeile {$rowIndex}: " . implode(', ', $rowErrors);
            }
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Schueler tatsaechlich importieren
     */
    public static function importStudents(string $filePath, string $schoolYear): array
    {
        $preview = self::previewStudents($filePath, $schoolYear);
        $imported = 0;
        $skipped = 0;
        $credentials = [];

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            $class = SchoolClass::findByName($row['class_name'], $schoolYear);

            // User-Account erstellen (analog zum Lehrer-Import)
            $password = bin2hex(random_bytes(5));
            $username = strtolower(
                mb_substr($row['firstname'], 0, 1) . '.' . self::sanitizeUsername($row['lastname'])
            );

            // Sicherstellen, dass Username eindeutig ist
            $baseUsername = $username;
            $counter = 1;
            while (User::usernameExists($username)) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $userId = User::create([
                'username' => $username,
                'email' => $row['guardian_email'] ?: null,
                'password' => $password,
                'role' => 'schueler',
                'must_change_password' => 1,
            ]);

            Student::create([
                'user_id' => $userId,
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'class_id' => $class['id'],
                'birthday' => $row['birthday'],
                'guardian_email' => $row['guardian_email'] ?: null,
            ]);

            $credentials[] = [
                'name' => $row['firstname'] . ' ' . $row['lastname'],
                'username' => $username,
                'password' => $password,
            ];

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $preview['errors'],
            'credentials' => $credentials,
        ];
    }

    /**
     * Username-sicheren String aus Namen erzeugen (Umlaute ersetzen, Sonderzeichen entfernen)
     */
    private static function sanitizeUsername(string $name): string
    {
        $replacements = [
            'ae' => 'ae', 'oe' => 'oe', 'ue' => 'ue', 'ss' => 'ss',
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
        ];
        $name = str_replace(array_keys($replacements), array_values($replacements), $name);
        $name = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        return $name;
    }
}
