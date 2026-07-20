<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\Services\AideService;
use OpenClassbook\Models\SchoolAide;
use OpenClassbook\Models\User;
use OpenClassbook\Tests\DatabaseTestCase;

class AideServiceTest extends DatabaseTestCase
{
    public function testCreateAideWithAccountCreatesLinkedUser(): void
    {
        $result = AideService::createAideWithAccount([
            'firstname' => 'Erika',
            'lastname' => 'Beispiel',
            'comment' => 'Vormittags',
            'email' => 'erika@mail.de',
        ]);

        $aide = SchoolAide::findById($result['aide_id']);
        $this->assertNotNull($aide);
        $this->assertEquals('Erika', $aide['firstname']);
        $this->assertEquals('Vormittags', $aide['comment']);
        $this->assertEquals($result['user_id'], (int) $aide['user_id']);

        $user = User::findById($result['user_id']);
        $this->assertEquals('schulbegleiter', $user['role']);
        $this->assertEquals(1, (int) $user['must_change_password']);
        // Anmeldename = E-Mail
        $this->assertEquals('erika@mail.de', $user['email']);
        $this->assertEquals('erika@mail.de', $user['username']);

        // Rueckgabe liefert Einladungs-Info (kein Klartext-Passwort mehr).
        $this->assertEquals('Erika Beispiel', $result['name']);
        $this->assertEquals('erika@mail.de', $result['email']);
    }

    public function testEmailIsNormalisedToLowercase(): void
    {
        $result = AideService::createAideWithAccount([
            'firstname' => 'Tom',
            'lastname' => 'Schmidt',
            'email' => 'Tom.Schmidt@Mail.DE',
        ]);

        $user = User::findById($result['user_id']);
        $this->assertEquals('tom.schmidt@mail.de', $user['username']);
        $this->assertEquals('tom.schmidt@mail.de', $user['email']);
    }
}
