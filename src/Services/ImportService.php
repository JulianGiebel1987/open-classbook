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
     * Prueft, ob eine E-Mail als Anmeldename verwendbar ist (Lehrkraefte und
     * Schulbegleiter:innen). Gibt einen Fehlertext zurueck, falls die E-Mail ein
     * ungueltiges Format hat, innerhalb derselben Datei mehrfach vorkommt oder
     * bereits vergeben ist; sonst null. $seenEmails wird fortgeschrieben (Referenz).
     * Der reine Pflichtfeld-Check (leer) erfolgt separat im Aufrufer.
     */
    private static function emailLoginError(string $email, array &$seenEmails): ?string
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL) || mb_strlen($normalized) > 255) {
            return 'E-Mail "' . $email . '" ist ungültig';
        }
        if (isset($seenEmails[$normalized])) {
            return 'E-Mail "' . $email . '" kommt in der Datei mehrfach vor';
        }
        $seenEmails[$normalized] = true;
        if (User::emailExists($normalized) || User::usernameExists($normalized)) {
            return 'E-Mail "' . $email . '" ist bereits vergeben';
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

            if (!empty($email) && ($emailError = self::emailLoginError($email, $seenEmails)) !== null) {
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
            $data = array_pad($csvRow['data'], 6, '');
            [$firstname, $lastname, $className, $birthday, $guardianEmail, $guardianPhone] = $data;

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

            if ($guardianEmail !== '' && (!filter_var($guardianEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($guardianEmail) > 255)) {
                $rowErrors[] = 'Ungültige Erziehungsberechtigten-E-Mail';
            }

            $rows[] = [
                'row' => $csvRow['lineNum'],
                'firstname' => $firstname,
                'lastname' => $lastname,
                'class_name' => $className,
                'birthday' => $parsedBirthday,
                'guardian_email' => $guardianEmail,
                'guardian_phone' => $guardianPhone,
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

            if (!empty($email) && ($emailError = self::emailLoginError($email, $seenEmails)) !== null) {
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
        $invitations = [];

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            // Anmeldename = E-Mail-Adresse. Aktivierung erfolgt per Einladungslink;
            // es wird nur ein unbrauchbares Zufallspasswort gesetzt.
            $email = strtolower(trim($row['email']));

            // E-Mail bereits vergeben -> Zeile ueberspringen
            // (Duplikate werden bereits in der Vorschau als Fehler markiert)
            if ($email === '' || User::emailExists($email) || User::usernameExists($email)) {
                $skipped++;
                continue;
            }

            $userId = User::create([
                'username' => $email,
                'email' => $email,
                'password' => AuthService::generateRandomPassword(),
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

            $invitations[] = [
                'user_id' => $userId,
                'email' => $email,
                'name' => trim($row['firstname'] . ' ' . $row['lastname']),
            ];
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $preview['errors'], 'invitations' => $invitations];
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
            foreach ($row->getCellIterator('A', 'F') as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }

            [$firstname, $lastname, $className, $birthday, $guardianEmail, $guardianPhone] = array_pad($cells, 6, '');

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

            if ($guardianEmail !== '' && (!filter_var($guardianEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($guardianEmail) > 255)) {
                $rowErrors[] = 'Ungültige Erziehungsberechtigten-E-Mail';
            }

            $rows[] = [
                'row' => $rowIndex,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'class_name' => $className,
                'birthday' => $parsedBirthday,
                'guardian_email' => $guardianEmail,
                'guardian_phone' => $guardianPhone,
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
                'guardian_phone' => ($row['guardian_phone'] ?? '') ?: null,
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
     * Spalten: Vorname, Nachname, E-Mail, Kommentar
     */
    public static function previewSchoolAides(string $filePath, string $format = 'xlsx'): array
    {
        $seenEmails = [];

        if ($format === 'csv') {
            $csvRows = self::parseCsv($filePath);
            $rows = [];
            $errors = [];
            foreach ($csvRows as $csvRow) {
                $data = array_pad($csvRow['data'], 4, '');
                [$firstname, $lastname, $email, $comment] = $data;
                $rows[] = self::buildAideRow($csvRow['lineNum'], $firstname, $lastname, $email, $comment, $errors, $seenEmails);
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
            foreach ($row->getCellIterator('A', 'D') as $cell) {
                $cells[] = trim((string) $cell->getValue());
            }
            [$firstname, $lastname, $email, $comment] = array_pad($cells, 4, '');

            if (empty($firstname) && empty($lastname)) {
                continue; // Leere Zeile
            }

            $rows[] = self::buildAideRow($rowIndex, $firstname, $lastname, $email, $comment, $errors, $seenEmails);
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Eine einzelne Schulbegleiter-Zeile validieren und aufbereiten.
     * Die E-Mail ist der Anmeldename (Pflicht, eindeutig, formatgeprueft).
     */
    private static function buildAideRow(int $rowIndex, string $firstname, string $lastname, string $email, string $comment, array &$errors, array &$seenEmails): array
    {
        $rowErrors = [];
        if (empty($firstname)) $rowErrors[] = 'Vorname fehlt';
        if (empty($lastname)) $rowErrors[] = 'Nachname fehlt';
        if (empty($email)) $rowErrors[] = 'E-Mail fehlt';

        if (!empty($email) && ($emailError = self::emailLoginError($email, $seenEmails)) !== null) {
            $rowErrors[] = $emailError;
        }

        if (!empty($rowErrors)) {
            $errors[] = "Zeile {$rowIndex}: " . implode(', ', $rowErrors);
        }

        return [
            'row' => $rowIndex,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'comment' => $comment,
            'errors' => $rowErrors,
        ];
    }

    /**
     * Schulbegleiter:innen tatsächlich importieren (inkl. Benutzerkonto).
     * Aktivierung erfolgt per Einladungslink (Anmeldename = E-Mail).
     */
    public static function importSchoolAides(string $filePath): array
    {
        $format = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $preview = self::previewSchoolAides($filePath, $format);
        $imported = 0;
        $skipped = 0;
        $invitations = [];

        foreach ($preview['rows'] as $row) {
            if (!empty($row['errors'])) {
                $skipped++;
                continue;
            }

            $email = strtolower(trim($row['email']));
            if ($email === '' || User::emailExists($email) || User::usernameExists($email)) {
                $skipped++;
                continue;
            }

            $created = AideService::createAideWithAccount([
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'email' => $email,
                'comment' => $row['comment'] ?: null,
            ]);

            $invitations[] = [
                'user_id' => $created['user_id'],
                'email' => $created['email'],
                'name' => $created['name'],
            ];
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $preview['errors'],
            'invitations' => $invitations,
        ];
    }

}
