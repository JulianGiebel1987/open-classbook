<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\Student;
use OpenClassbook\Tests\DatabaseTestCase;

class StudentModelTest extends DatabaseTestCase
{
    public function testCreateAndFindById(): void
    {
        $classId = $this->createTestClass();
        $studentId = Student::create([
            'firstname' => 'Lisa',
            'lastname' => 'Mueller',
            'class_id' => $classId,
            'birthday' => '2012-03-15',
            'guardian_email' => 'eltern@example.com',
        ]);

        $student = Student::findById($studentId);
        $this->assertNotNull($student);
        $this->assertEquals('Lisa', $student['firstname']);
        $this->assertEquals('Mueller', $student['lastname']);
        $this->assertEquals($classId, $student['class_id']);
    }

    public function testFindByIdReturnsNull(): void
    {
        $this->assertNull(Student::findById(9999));
    }

    public function testFindByClassId(): void
    {
        $classId = $this->createTestClass();
        $this->createTestStudent($classId, ['firstname' => 'Anna', 'lastname' => 'Alpha']);
        $this->createTestStudent($classId, ['firstname' => 'Ben', 'lastname' => 'Beta']);

        $students = Student::findByClassId($classId);
        $this->assertCount(2, $students);
        // Ordered by lastname
        $this->assertEquals('Alpha', $students[0]['lastname']);
    }

    public function testFindAllWithClassFilter(): void
    {
        $classId1 = $this->createTestClass(['name' => '5a']);
        $classId2 = $this->createTestClass(['name' => '6b']);
        $this->createTestStudent($classId1, ['firstname' => 'Anna']);
        $this->createTestStudent($classId2, ['firstname' => 'Ben']);

        $students = Student::findAll(['class_id' => $classId1]);
        $this->assertCount(1, $students);
        $this->assertEquals('Anna', $students[0]['firstname']);
    }

    public function testFindAllWithSearchFilter(): void
    {
        $classId = $this->createTestClass();
        $this->createTestStudent($classId, ['firstname' => 'Anna', 'lastname' => 'Schmidt']);
        $this->createTestStudent($classId, ['firstname' => 'Ben', 'lastname' => 'Mueller']);

        $students = Student::findAll(['search' => 'Schm']);
        $this->assertCount(1, $students);
        $this->assertEquals('Anna', $students[0]['firstname']);
    }

    public function testUpdate(): void
    {
        $classId = $this->createTestClass();
        $studentId = $this->createTestStudent($classId);

        Student::update($studentId, [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'class_id' => $classId,
            'birthday' => '2011-01-01',
            'guardian_email' => 'new@example.com',
        ]);

        $student = Student::findById($studentId);
        $this->assertEquals('Updated', $student['firstname']);
        $this->assertEquals('new@example.com', $student['guardian_email']);
    }

    public function testDelete(): void
    {
        $classId = $this->createTestClass();
        $studentId = $this->createTestStudent($classId);

        Student::delete($studentId);

        $this->assertNull(Student::findById($studentId));
    }

    public function testCountByClassId(): void
    {
        $classId = $this->createTestClass();
        $this->createTestStudent($classId, ['firstname' => 'Anna']);
        $this->createTestStudent($classId, ['firstname' => 'Ben']);

        $this->assertEquals(2, Student::countByClassId($classId));
    }

    public function testCountByClassIdEmpty(): void
    {
        $classId = $this->createTestClass();
        $this->assertEquals(0, Student::countByClassId($classId));
    }

    public function testArchiveHidesStudentFromClassRoster(): void
    {
        $classId = $this->createTestClass();
        $keptId = $this->createTestStudent($classId, ['firstname' => 'Anna', 'lastname' => 'Alpha']);
        $archivedId = $this->createTestStudent($classId, ['firstname' => 'Ben', 'lastname' => 'Beta']);

        Student::archive($archivedId);

        $roster = Student::findByClassId($classId);
        $this->assertCount(1, $roster);
        $this->assertEquals($keptId, $roster[0]['id']);

        // Archivierte werden aus Zaehlung und findAll ausgeblendet ...
        $this->assertEquals(1, Student::countByClassId($classId));
        $this->assertCount(1, Student::findAll(['class_id' => $classId]));

        // ... aber ueber die Archiv-Abfrage bzw. include_archived weiterhin sichtbar.
        $archived = Student::findArchivedByClassId($classId);
        $this->assertCount(1, $archived);
        $this->assertEquals($archivedId, $archived[0]['id']);
        $this->assertCount(2, Student::findByClassId($classId, true));
        $this->assertEquals(2, Student::countByClassId($classId, true));
        $this->assertCount(2, Student::findAll(['class_id' => $classId, 'include_archived' => true]));
    }

    public function testRestoreReturnsStudentToRoster(): void
    {
        $classId = $this->createTestClass();
        $studentId = $this->createTestStudent($classId);

        Student::archive($studentId);
        $this->assertCount(0, Student::findByClassId($classId));

        Student::restore($studentId);
        $roster = Student::findByClassId($classId);
        $this->assertCount(1, $roster);
        $this->assertNull($roster[0]['archived_at']);
    }

    public function testSetUserIdLinksAccount(): void
    {
        $classId = $this->createTestClass();
        $studentId = $this->createTestStudent($classId, ['user_id' => null]);
        $userId = $this->createTestUser(['username' => 'linkme', 'role' => 'schueler']);

        Student::setUserId($studentId, $userId);

        $student = Student::findById($studentId);
        $this->assertEquals($userId, $student['user_id']);
    }
}
