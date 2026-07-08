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
     * CSV-Datei einlesen und als Array zurückgeben (Delimiter-Erkennung: ; oder ,)
     */
    private static function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

        $rows = [];
        $lineNum = 0;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNum++;
            if ($lineNum === 1) {
                continue; // Header-Zeile überspringen
            }
            if (empty(array_filter($data, fn($v) => trim($v) !== ''))) {
                continue; // Leere Zeile überspringen
            }
            $rows[] = ['lineNum' => $lineNum, 'data' => array_map('trim', $data)];
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Prueft, ob eine Lehrkraft-E-Mail als Anmeldename verwendbar ist.
     * Gibt einen Fehlertext zurueck, falls die E-Mail bereits als Anmeldename
     * vergeben ist oder innerhalb derselben Datei mehrfach vorkommt; sonst null.
     * $seenEmails wird dabei fortgeschrieben (Referenz).
     */
    private static function teacherEmailLoginError(string $email, array &$seenEmails): ?string
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }
        if (isset($seenEmails[$normalized])) {
            return 'E-Mail "' . $email . '" kommt in der Datei mehrfach vor';
        }
        $seenEmails[$normalized] = true;
        if (User::usernameExists($normalized)) {
            return 'E-Mail "' . $email . '" ist bereits als Anmeldename vergeben';
        }
        return null;
    }

    /**
     * Lehrer-Vorschau aus CSV-Datei
     */
    private static function previewTeachersFromCsv(string $filePath): array
    {
        $csvRows = self::parseCsv($filePath);
        $rows = [];
        $errors = [];
        $seenEmails = [];

        foreach ($csvRows as $csvRow) {
            $data = array_pad($csvRow['data'], 6, '');
            [$firstname, $lastname, $abbreviation, $email, $subjects, $classes] = $data;

            $rowErrors = [];
            if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
            if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';
            if (empty($abbreviation)) $rowErrors[] = 'Kürzel fehlt';
            if (empty($email)) $rowErrors[] = 'E-Mail fehlt';

            if (!empty($abbreviation) && Teacher::abbreviationExists($abbreviation)) {
                $rowErrors[] = 'Kürzel "' . $abbreviation . '" existiert bereits';
            }

            if (!empty($email) && ($emailError = self::teacherEmailLoginError($email, $seenEmails)) !== null) {
                $rowErrors[] = $emailError;
            }

            $rows[] = [
                'row' => $csvRow['lineNum'],
                'firstname' => $firstname,
                'lastname' => $lastname,
                'abbreviation' => $abbreviation,
                'email' => $email,
                'subjects' => $subjects,
                'classes' => $classes,
                'errors' => $rowErrors,
            ];

            if (!empty($rowErrors)) {
                $errors[] = "Zeile {$csvRow['lineNum']}: " . implode(', ', $rowErrors);
            }
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Schüler-Vorschau aus CSV-Datei
     */
    private static function previewStudentsFromCsv(string $filePath, string $schoolYear): array
    {
        $csvRows = self::parseCsv($filePath);
        $rows = [];
        $errors = [];

        foreach ($csvRows as $csvRow) {
            $data = array_pad($csvRow['data'], 5, '');
            [$firstname, $lastname, $className, $birthday, $guardianEmail] = $data;

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
                'row' => $csvRow['lineNum'],
                'firstname' => $firstname,
                'lastname' => $lastname,
                'class_name' => $className,
                'birthday' => $parsedBirthday,
                'guardian_email' => $guardianEmail,
                'errors' => $rowErrors,
            ];

            if (!empty($rowErrors)) {
                $errors[] = "Zeile {$csvRow['lineNum']}: " . implode(', ', $rowErrors);
            }
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Lehrer-Import aus Excel- oder CSV-Datei
     * Spalten: Vorname, Nachname, Kürzel, E-Mail, Fächer, Klassen
     */
    public static function previewTeachers(string $filePath, string $format = 'xlsx'): array
    {
        if ($format === 'csv') {
            return self::previewTeachersFromCsv($filePath);
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $errors = [];
        $seenEmails = [];

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
            if (empty($abbreviation)) $rowErrors[] = 'Kürzel fehlt';
            if (empty($email)) $rowErrors[] = 'E-Mail fehlt';

            if (!empty($abbreviation) && Teacher::abbreviationExists($abbreviation)) {
                $rowErrors[] = 'Kürzel "' . $abbreviation . '" existiert bereits';
            }

            if (!empty($email) && ($emailError = self::teacherEmailLoginError($email, $seenEmails)) !== null) {
                $rowErrors[] = $emailError;
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
     * Lehrer tatsächlich importieren
     */
    public static function importTeachers(string $filePath): array
    {
        $format = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $preview = self::previewTeachers($filePath, $format);
        $imported = 0;
        $skipped = 0;

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            // User-Account erstellen: Anmeldename ist die E-Mail-Adresse
            $password = bin2hex(random_bytes(5));
            $username = strtolower(trim($row['email']));

            // E-Mail bereits als Anmeldename vergeben -> Zeile ueberspringen
            // (Duplikate werden bereits in der Vorschau als Fehler markiert)
            if ($username === '' || User::usernameExists($username)) {
                $skipped++;
                continue;
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
     * Schüler-Import aus Excel- oder CSV-Datei
     * Spalten: Vorname, Nachname, Klasse, Geburtsdatum, Erziehungsberechtigten-Email
     */
    public static function previewStudents(string $filePath, string $schoolYear, string $format = 'xlsx'): array
    {
        if ($format === 'csv') {
            return self::previewStudentsFromCsv($filePath, $schoolYear);
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $errors = [];

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
     * Schüler tatsächlich importieren
     */
    public static function importStudents(string $filePath, string $schoolYear): array
    {
        $format = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $preview = self::previewStudents($filePath, $schoolYear, $format);
        $imported = 0;
        $skipped = 0;
        $credentials = [];

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            $class = SchoolClass::findByName($row['class_name'], $schoolYear);

            // Schueler-Datensatz + verknuepftes Benutzerkonto ueber den
            // gemeinsamen Service anlegen (identisch zur Einzelanlage).
            $created = StudentService::createStudentWithAccount([
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'class_id' => $class['id'],
                'birthday' => $row['birthday'],
                'guardian_email' => $row['guardian_email'] ?: null,
            ]);

            $credentials[] = $created['credentials'];

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
     * Schulbegleiter:innen-Vorschau aus Excel- oder CSV-Datei.
     * Spalten: Vorname, Nachname, Kommentar
     */
    public static function previewSchoolAides(string $filePath, string $format = 'xlsx'): array
    {
        if ($format === 'csv') {
            $csvRows = self::parseCsv($filePath);
            $rows = [];
            $errors = [];
            foreach ($csvRows as $csvRow) {
                $data = array_pad($csvRow['data'], 3, '');
                [$firstname, $lastname, $comment] = $data;
                $rows[] = self::buildAideRow($csvRow['lineNum'], $firstname, $lastname, $comment, $errors);
            }
            return ['rows' => $rows, 'errors' => $errors];
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $errors = [];

        foreach ($sheet->getRowIterator(2) as $row) { // Ab Zeile 2 (Zeile 1 = Header)
            $rowIndex = $row->getRowIndex();
            $cells = [];
            foreach ($row->getCellIterator('A', 'C') as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }
            [$firstname, $lastname, $comment] = array_pad($cells, 3, '');

            if (empty($firstname) && empty($lastname)) {
                continue; // Leere Zeile
            }

            $rows[] = self::buildAideRow($rowIndex, $firstname, $lastname, $comment, $errors);
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Eine einzelne Schulbegleiter-Zeile validieren und aufbereiten.
     */
    private static function buildAideRow(int $rowIndex, string $firstname, string $lastname, string $comment, array &$errors): array
    {
        $rowErrors = [];
        if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
        if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';

        if (!empty($rowErrors)) {
            $errors[] = "Zeile {$rowIndex}: " . implode(', ', $rowErrors);
        }

        return [
            'row' => $rowIndex,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'comment' => $comment,
            'errors' => $rowErrors,
        ];
    }

    /**
     * Schulbegleiter:innen tatsächlich importieren (inkl. Benutzerkonto).
     */
    public static function importSchoolAides(string $filePath): array
    {
        $format = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $preview = self::previewSchoolAides($filePath, $format);
        $imported = 0;
        $skipped = 0;
        $credentials = [];

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            $created = AideService::createAideWithAccount([
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'comment' => $row['comment'] ?: null,
            ]);

            $credentials[] = $created['credentials'];
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $preview['errors'],
            'credentials' => $credentials,
        ];
    }

}
