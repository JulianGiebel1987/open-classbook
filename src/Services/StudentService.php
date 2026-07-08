<?php

namespace OpenClassbook\Services;

use OpenClassbook\Models\User;
use OpenClassbook\Models\Student;

/**
 * Zentrale Geschaeftslogik fuer das Anlegen von Schueler:innen inklusive
 * verknuepftem Benutzerkonto (role=schueler). Wird sowohl vom Massen-Import
 * (ImportService) als auch von der klassenzentrierten Einzelanlage
 * (StudentController) verwendet, damit beide Wege identische Konten erzeugen.
 */
class StudentService
{
    /**
     * Legt einen Schueler-Datensatz zusammen mit einem verknuepften
     * Benutzer-Account an und gibt die einmalig anzeigbaren Zugangsdaten zurueck.
     *
     * @param array $data Erwartet: firstname, lastname, class_id; optional: birthday, guardian_email
     * @return array{student_id:int, user_id:int, credentials:array{name:string,username:string,password:string}}
     */
    public static function createStudentWithAccount(array $data): array
    {
        $guardianEmail = !empty($data['guardian_email']) ? $data['guardian_email'] : null;

        [$userId, $credentials] = self::createAccount($data['firstname'], $data['lastname'], $guardianEmail);

        $studentId = Student::create([
            'user_id' => $userId,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'class_id' => $data['class_id'],
            'birthday' => $data['birthday'] ?? null,
            'guardian_email' => $guardianEmail,
        ]);

        return [
            'student_id' => $studentId,
            'user_id' => $userId,
            'credentials' => $credentials,
        ];
    }

    /**
     * Erzeugt fuer einen bereits existierenden Schueler-Datensatz (ohne Konto)
     * ein Benutzerkonto und verknuepft es. Fuer den Nachzug von Bestandsdaten.
     *
     * @param array $student Datensatz aus der students-Tabelle (id, firstname, lastname, guardian_email)
     * @return array{user_id:int, credentials:array{name:string,username:string,password:string}}
     */
    public static function createAccountForExistingStudent(array $student): array
    {
        $guardianEmail = !empty($student['guardian_email']) ? $student['guardian_email'] : null;

        [$userId, $credentials] = self::createAccount($student['firstname'], $student['lastname'], $guardianEmail);

        Student::setUserId((int) $student['id'], $userId);

        return [
            'user_id' => $userId,
            'credentials' => $credentials,
        ];
    }

    /**
     * Benutzerkonto (role=schueler) mit eindeutigem Usernamen und starkem
     * Zufallspasswort anlegen.
     *
     * @return array{0:int,1:array{name:string,username:string,password:string}}
     */
    private static function createAccount(string $firstname, string $lastname, ?string $guardianEmail): array
    {
        $username = self::generateUniqueUsername($firstname, $lastname);
        $password = AuthService::generateRandomPassword();

        $userId = User::create([
            'username' => $username,
            'email' => $guardianEmail,
            'password' => $password,
            'role' => 'schueler',
            'must_change_password' => 1,
        ]);

        return [
            $userId,
            [
                'name' => trim($firstname . ' ' . $lastname),
                'username' => $username,
                'password' => $password,
            ],
        ];
    }

    /**
     * Eindeutigen Usernamen aus Vor- und Nachname erzeugen
     * (erster Buchstabe + '.' + Nachname), Kollisionen mit numerischem Suffix.
     */
    public static function generateUniqueUsername(string $firstname, string $lastname): string
    {
        $first = self::sanitizeUsername(mb_substr($firstname, 0, 1));
        $last = self::sanitizeUsername($lastname);

        $base = $first . '.' . $last;
        if (trim($base, '.') === '') {
            $base = 'schueler';
        }

        $username = $base;
        $counter = 1;
        while (User::usernameExists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Username-sicheren String aus Namen erzeugen (Umlaute ersetzen, Sonderzeichen entfernen).
     */
    public static function sanitizeUsername(string $name): string
    {
        $replacements = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
        ];
        $name = str_replace(array_keys($replacements), array_values($replacements), $name);
        $name = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        return $name;
    }
}
