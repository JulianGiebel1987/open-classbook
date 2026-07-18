<?php

namespace OpenClassbook\Services;

use OpenClassbook\Models\User;
use OpenClassbook\Models\SchoolAide;

/**
 * Zentrale Geschaeftslogik fuer das Anlegen von Schulbegleiter:innen inklusive
 * verknuepftem Benutzerkonto (role=schulbegleiter). Schulbegleiter:innen melden
 * sich – wie Lehrkraefte und die Verwaltungsrollen – mit ihrer E-Mail-Adresse an
 * (Anmeldename = E-Mail). Die Aktivierung erfolgt ueber einen Einladungslink,
 * den der Aufrufer versendet bzw. anzeigt.
 */
class AideService
{
    /**
     * Legt einen Schulbegleiter-Datensatz zusammen mit einem verknuepften
     * Benutzer-Account (Anmeldename = E-Mail) an. Es wird ein unbrauchbares
     * Zufallspasswort gesetzt; die Aktivierung erfolgt per Einladungslink.
     *
     * @param array $data Erwartet: firstname, lastname, email; optional: comment
     * @return array{aide_id:int, user_id:int, email:string, name:string}
     */
    public static function createAideWithAccount(array $data): array
    {
        $email = strtolower(trim($data['email'] ?? ''));

        $userId = User::create([
            'username' => $email,
            'email' => $email,
            'password' => AuthService::generateRandomPassword(),
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
            'email' => $email,
            'name' => trim($data['firstname'] . ' ' . $data['lastname']),
        ];
    }
}
