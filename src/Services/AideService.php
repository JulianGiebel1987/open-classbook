<?php

namespace OpenClassbook\Services;

use OpenClassbook\Models\User;
use OpenClassbook\Models\SchoolAide;

/**
 * Zentrale Geschaeftslogik fuer das Anlegen von Schulbegleiter:innen inklusive
 * verknuepftem Benutzerkonto (role=schulbegleiter). Wird vom Massen-Import
 * (ImportService) verwendet, damit importierte Konten identisch zu ueber die
 * Benutzerverwaltung angelegten Konten erzeugt werden.
 */
class AideService
{
    /**
     * Legt einen Schulbegleiter-Datensatz zusammen mit einem verknuepften
     * Benutzer-Account an und gibt die einmalig anzeigbaren Zugangsdaten zurueck.
     *
     * @param array $data Erwartet: firstname, lastname; optional: comment, email
     * @return array{aide_id:int, user_id:int, credentials:array{name:string,username:string,password:string}}
     */
    public static function createAideWithAccount(array $data): array
    {
        $email = !empty($data['email']) ? $data['email'] : null;

        $username = self::generateUniqueUsername($data['firstname'], $data['lastname']);
        $password = AuthService::generateRandomPassword();

        $userId = User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => 'schulbegleiter',
            'must_change_password' => 1,
        ]);

        $aideId = SchoolAide::create([
            'user_id' => $userId,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'comment' => $data['comment'] ?? null,
        ]);

        return [
            'aide_id' => $aideId,
            'user_id' => $userId,
            'credentials' => [
                'name' => trim($data['firstname'] . ' ' . $data['lastname']),
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
        $first = StudentService::sanitizeUsername(mb_substr($firstname, 0, 1));
        $last = StudentService::sanitizeUsername($lastname);

        $base = $first . '.' . $last;
        if (trim($base, '.') === '') {
            $base = 'begleitung';
        }

        $username = $base;
        $counter = 1;
        while (User::usernameExists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
