<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\Teacher;
use OpenClassbook\Tests\DatabaseTestCase;

class TeacherModelTest extends DatabaseTestCase
{
    public function testCreateAndFindById(): void
    {
        $userId = $this->createTestUser();
        $teacherId = Teacher::create([
            'user_id' => $userId,
            'firstname' => 'Max',
            'lastname' => 'Mustermann',
            'abbreviation' => 'MU',
            'subjects' => 'Mathematik, Physik',
        ]);

        $teacher = Teacher::findById($teacherId);
        $this->assertNotNull($teacher);
        $this->assertEquals('Max', $teacher['firstname']);
        $this->assertEquals('Mustermann', $teacher['lastname']);
        $this->assertEquals('MU', $teacher['abbreviation']);
    }

    public function testFindByUserId(): void
    {
        $userId = $this->createTestUser();
        $teacherId = $this->createTestTeacher($userId);

        $teacher = Teacher::findByUserId($userId);
        $this->assertNotNull($teacher);
        $this->assertEquals($teacherId, $teacher['id']);
    }

    public function testFindAll(): void
    {
        $userId1 = $this->createTestUser(['username' => 'teacher1']);
        $userId2 = $this->createTestUser(['username' => 'teacher2', 'email' => 't2@example.com']);
        $this->createTestTeacher($userId1, ['abbreviation' => 'T1', 'lastname' => 'Alpha']);
        $this->createTestTeacher($userId2, ['abbreviation' => 'T2', 'lastname' => 'Beta']);

        $teachers = Teacher::findAll();
        $this->assertCount(2, $teachers);
        // Ordered by lastname
        $this->assertEquals('Alpha', $teachers[0]['lastname']);
    }

    public function testUpdate(): void
    {
        $userId = $this->createTestUser();
        $teacherId = $this->createTestTeacher($userId);

        Teacher::update($teacherId, [
            'firstname' => 'Updated',
            'lastname' => 'Name',
            'abbreviation' => 'UN',
            'subjects' => 'Deutsch',
        ]);

        $teacher = Teacher::findById($teacherId);
        $this->assertEquals('Updated', $teacher['firstname']);
        $this->assertEquals('UN', $teacher['abbreviation']);
    }

    public function testAbbreviationExists(): void
    {
        $userId = $this->createTestUser();
        $this->createTestTeacher($userId, ['abbreviation' => 'MU']);

        $this->assertTrue(Teacher::abbreviationExists('MU'));
        $this->assertFalse(Teacher::abbreviationExists('XX'));
    }

    public function testAbbreviationExistsWithExclude(): void
    {
        $userId = $this->createTestUser();
        $teacherId = $this->createTestTeacher($userId, ['abbreviation' => 'MU']);

        $this->assertFalse(Teacher::abbreviationExists('MU', $teacherId));
    }

    public function testGetClassesForTeacher(): void
    {
        $userId = $this->createTestUser();
        $teacherId = $this->createTestTeacher($userId);
        $classId = $this->createTestClass();

        self::$pdo->prepare('INSERT INTO class_teacher (class_id, teacher_id) VALUES (?, ?)')
            ->execute([$classId, $teacherId]);

        $classes = Teacher::getClassesForTeacher($teacherId);
        $this->assertCount(1, $classes);
        $this->assertEquals('5a', $classes[0]['name']);
    }

    public function testGetTeacherIdByUserId(): void
    {
        $userId = $this->createTestUser();
        $teacherId = $this->createTestTeacher($userId);

        $this->assertEquals($teacherId, Teacher::getTeacherIdByUserId($userId));
        $this->assertNull(Teacher::getTeacherIdByUserId(9999));
    }
}
