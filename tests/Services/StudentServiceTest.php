<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\Services\StudentService;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\User;
use OpenClassbook\Tests\DatabaseTestCase;

class StudentServiceTest extends DatabaseTestCase
{
    public function testCreateStudentWithAccountCreatesLinkedUser(): void
    {
        $classId = $this->createTestClass();

        $result = StudentService::createStudentWithAccount([
            'firstname' => 'Lisa',
            'lastname' => 'Weber',
            'class_id' => $classId,
            'birthday' => '2015-03-15',
            'guardian_email' => 'eltern@mail.de',
        ]);

        // Schueler-Datensatz korrekt angelegt
        $student = Student::findById($result['student_id']);
        $this->assertNotNull($student);
        $this->assertEquals('Lisa', $student['firstname']);
        $this->assertEquals('2015-03-15', $student['birthday']);
        $this->assertEquals('eltern@mail.de', $student['guardian_email']);
        $this->assertEquals($result['user_id'], $student['user_id']);

        // Verknuepftes Benutzerkonto korrekt angelegt
        $user = User::findById($result['user_id']);
        $this->assertNotNull($user);
        $this->assertEquals('schueler', $user['role']);
        $this->assertEquals(1, (int) $user['must_change_password']);
        $this->assertEquals('eltern@mail.de', $user['email']);
        $this->assertEquals('l.weber', $user['username']);

        // Zugangsdaten werden einmalig zurueckgegeben
        $this->assertEquals('Lisa Weber', $result['credentials']['name']);
        $this->assertEquals('l.weber', $result['credentials']['username']);
        $this->assertNotEmpty($result['credentials']['password']);
    }

    public function testUsernameCollisionGetsNumericSuffix(): void
    {
        $classId = $this->createTestClass();

        $first = StudentService::createStudentWithAccount([
            'firstname' => 'Tom',
            'lastname' => 'Schmidt',
            'class_id' => $classId,
        ]);
        $second = StudentService::createStudentWithAccount([
            'firstname' => 'Tim',
            'lastname' => 'Schmidt',
            'class_id' => $classId,
        ]);

        $this->assertEquals('t.schmidt', $first['credentials']['username']);
        $this->assertEquals('t.schmidt1', $second['credentials']['username']);
    }

    public function testUsernameSanitizesUmlauts(): void
    {
        $classId = $this->createTestClass();

        $result = StudentService::createStudentWithAccount([
            'firstname' => 'Ömer',
            'lastname' => 'Müller',
            'class_id' => $classId,
        ]);

        $this->assertEquals('oe.mueller', $result['credentials']['username']);
    }

    public function testEmptyGuardianEmailStoredAsNull(): void
    {
        $classId = $this->createTestClass();

        $result = StudentService::createStudentWithAccount([
            'firstname' => 'Nina',
            'lastname' => 'Klein',
            'class_id' => $classId,
            'guardian_email' => '',
        ]);

        $user = User::findById($result['user_id']);
        $this->assertNull($user['email']);
        $student = Student::findById($result['student_id']);
        $this->assertNull($student['guardian_email']);
    }

    public function testCreateAccountForExistingStudentLinksAccount(): void
    {
        $classId = $this->createTestClass();
        $studentId = $this->createTestStudent($classId, [
            'user_id' => null,
            'firstname' => 'Paul',
            'lastname' => 'Fischer',
            'guardian_email' => 'fischer@mail.de',
        ]);

        $result = StudentService::createAccountForExistingStudent([
            'id' => $studentId,
            'firstname' => 'Paul',
            'lastname' => 'Fischer',
            'guardian_email' => 'fischer@mail.de',
        ]);

        $student = Student::findById($studentId);
        $this->assertEquals($result['user_id'], $student['user_id']);

        $user = User::findById($result['user_id']);
        $this->assertEquals('schueler', $user['role']);
        $this->assertEquals('p.fischer', $user['username']);
        $this->assertEquals('fischer@mail.de', $user['email']);
    }
}
