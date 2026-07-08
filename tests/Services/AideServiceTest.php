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
        $this->assertEquals('erika@mail.de', $user['email']);
        $this->assertEquals('e.beispiel', $user['username']);

        $this->assertEquals('Erika Beispiel', $result['credentials']['name']);
        $this->assertNotEmpty($result['credentials']['password']);
    }

    public function testUsernameCollisionGetsNumericSuffix(): void
    {
        $first = AideService::createAideWithAccount(['firstname' => 'Tom', 'lastname' => 'Schmidt']);
        $second = AideService::createAideWithAccount(['firstname' => 'Tim', 'lastname' => 'Schmidt']);

        $this->assertEquals('t.schmidt', $first['credentials']['username']);
        $this->assertEquals('t.schmidt1', $second['credentials']['username']);
    }

    public function testEmptyEmailStoredAsNull(): void
    {
        $result = AideService::createAideWithAccount(['firstname' => 'Nina', 'lastname' => 'Klein']);
        $this->assertNull(User::findById($result['user_id'])['email']);
    }
}
