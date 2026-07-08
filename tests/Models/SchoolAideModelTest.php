<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\SchoolAide;
use OpenClassbook\Models\Student;
use OpenClassbook\Tests\DatabaseTestCase;

class SchoolAideModelTest extends DatabaseTestCase
{
    public function testCreateAndFindById(): void
    {
        $userId = $this->createTestUser(['role' => 'schulbegleiter']);
        $aideId = SchoolAide::create([
            'user_id' => $userId,
            'firstname' => 'Erika',
            'lastname' => 'Beispiel',
            'comment' => 'Vormittags',
        ]);

        $aide = SchoolAide::findById($aideId);
        $this->assertNotNull($aide);
        $this->assertEquals('Erika', $aide['firstname']);
        $this->assertEquals('Vormittags', $aide['comment']);
    }

    public function testFindByUserIdAndGetAideId(): void
    {
        $userId = $this->createTestUser(['role' => 'schulbegleiter']);
        $aideId = $this->createTestAide($userId);

        $this->assertEquals($aideId, SchoolAide::findByUserId($userId)['id']);
        $this->assertEquals($aideId, SchoolAide::getAideIdByUserId($userId));
        $this->assertNull(SchoolAide::getAideIdByUserId(9999));
    }

    public function testFindAllOrdersByName(): void
    {
        $u1 = $this->createTestUser(['username' => 'a1', 'role' => 'schulbegleiter']);
        $u2 = $this->createTestUser(['username' => 'a2', 'role' => 'schulbegleiter']);
        $this->createTestAide($u1, ['lastname' => 'Zulu']);
        $this->createTestAide($u2, ['lastname' => 'Alpha']);

        $aides = SchoolAide::findAll();
        $this->assertCount(2, $aides);
        $this->assertEquals('Alpha', $aides[0]['lastname']);
        // JOIN liefert Benutzername mit
        $this->assertArrayHasKey('username', $aides[0]);
    }

    public function testUpdate(): void
    {
        $userId = $this->createTestUser(['role' => 'schulbegleiter']);
        $aideId = $this->createTestAide($userId);

        SchoolAide::update($aideId, [
            'firstname' => 'Neu',
            'lastname' => 'Name',
            'comment' => 'Geändert',
        ]);

        $aide = SchoolAide::findById($aideId);
        $this->assertEquals('Neu', $aide['firstname']);
        $this->assertEquals('Geändert', $aide['comment']);
    }

    public function testSetAndGetStudentsAssignsMultiple(): void
    {
        $userId = $this->createTestUser(['role' => 'schulbegleiter']);
        $aideId = $this->createTestAide($userId);
        $classId = $this->createTestClass();
        $s1 = $this->createTestStudent($classId, ['firstname' => 'Anna', 'lastname' => 'Alpha']);
        $s2 = $this->createTestStudent($classId, ['firstname' => 'Ben', 'lastname' => 'Beta']);

        SchoolAide::setStudents($aideId, [$s1, $s2]);

        $students = SchoolAide::getStudents($aideId);
        $this->assertCount(2, $students);
        $this->assertArrayHasKey('class_name', $students[0]);
    }

    public function testSetStudentsIsIdempotentAndDeduplicates(): void
    {
        $userId = $this->createTestUser(['role' => 'schulbegleiter']);
        $aideId = $this->createTestAide($userId);
        $classId = $this->createTestClass();
        $s1 = $this->createTestStudent($classId);

        // Doppelte IDs duerfen nicht zu doppelten Zeilen fuehren
        SchoolAide::setStudents($aideId, [$s1, $s1]);
        $this->assertCount(1, SchoolAide::getStudents($aideId));

        // Erneutes Setzen ersetzt die Zuweisung vollstaendig
        $s2 = $this->createTestStudent($classId, ['firstname' => 'Ben']);
        SchoolAide::setStudents($aideId, [$s2]);
        $students = SchoolAide::getStudents($aideId);
        $this->assertCount(1, $students);
        $this->assertEquals($s2, (int) $students[0]['id']);
    }

    public function testStudentGetAidesReverseDirection(): void
    {
        $userId = $this->createTestUser(['role' => 'schulbegleiter']);
        $aideId = $this->createTestAide($userId, ['lastname' => 'Helfer']);
        $classId = $this->createTestClass();
        $studentId = $this->createTestStudent($classId);

        SchoolAide::setStudents($aideId, [$studentId]);

        $aides = Student::getAides($studentId);
        $this->assertCount(1, $aides);
        $this->assertEquals('Helfer', $aides[0]['lastname']);
    }
}
