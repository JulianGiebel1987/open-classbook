<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Tests\DatabaseTestCase;

class SchoolClassModelTest extends DatabaseTestCase
{
    public function testCreateAndFindById(): void
    {
        $id = SchoolClass::create([
            'name' => '7b',
            'school_year' => '2025/2026',
        ]);

        $class = SchoolClass::findById($id);
        $this->assertNotNull($class);
        $this->assertEquals('7b', $class['name']);
        $this->assertEquals('2025/2026', $class['school_year']);
    }

    public function testFindByIdReturnsNull(): void
    {
        $this->assertNull(SchoolClass::findById(9999));
    }

    public function testFindAll(): void
    {
        SchoolClass::create(['name' => '5a', 'school_year' => '2025/2026']);
        SchoolClass::create(['name' => '6b', 'school_year' => '2025/2026']);

        $classes = SchoolClass::findAll();
        $this->assertCount(2, $classes);
    }

    public function testFindAllFilterBySchoolYear(): void
    {
        SchoolClass::create(['name' => '5a', 'school_year' => '2025/2026']);
        SchoolClass::create(['name' => '6b', 'school_year' => '2024/2025']);

        $classes = SchoolClass::findAll(['school_year' => '2025/2026']);
        $this->assertCount(1, $classes);
        $this->assertEquals('5a', $classes[0]['name']);
    }

    public function testUpdate(): void
    {
        $id = SchoolClass::create(['name' => '5a', 'school_year' => '2025/2026']);

        SchoolClass::update($id, [
            'name' => '5b',
            'school_year' => '2025/2026',
        ]);

        $class = SchoolClass::findById($id);
        $this->assertEquals('5b', $class['name']);
    }

    public function testFindByName(): void
    {
        SchoolClass::create(['name' => '5a', 'school_year' => '2025/2026']);

        $class = SchoolClass::findByName('5a', '2025/2026');
        $this->assertNotNull($class);
        $this->assertEquals('5a', $class['name']);

        $this->assertNull(SchoolClass::findByName('nonexistent', '2025/2026'));
    }

    public function testGetSchoolYears(): void
    {
        SchoolClass::create(['name' => '5a', 'school_year' => '2025/2026']);
        SchoolClass::create(['name' => '6b', 'school_year' => '2024/2025']);

        $years = SchoolClass::getSchoolYears();
        $this->assertCount(2, $years);
    }

    public function testSetAndGetTeachers(): void
    {
        $userId1 = $this->createTestUser(['username' => 'teacher1']);
        $userId2 = $this->createTestUser(['username' => 'teacher2', 'email' => 't2@example.com']);
        $teacherId1 = $this->createTestTeacher($userId1, ['abbreviation' => 'T1']);
        $teacherId2 = $this->createTestTeacher($userId2, ['abbreviation' => 'T2']);
        $classId = $this->createTestClass();

        SchoolClass::setTeachers($classId, [$teacherId1, $teacherId2]);

        $teachers = SchoolClass::getTeachers($classId);
        $this->assertCount(2, $teachers);
    }

    public function testSetTeachersReplacesExisting(): void
    {
        $userId1 = $this->createTestUser(['username' => 'teacher1']);
        $userId2 = $this->createTestUser(['username' => 'teacher2', 'email' => 't2@example.com']);
        $teacherId1 = $this->createTestTeacher($userId1, ['abbreviation' => 'T1']);
        $teacherId2 = $this->createTestTeacher($userId2, ['abbreviation' => 'T2']);
        $classId = $this->createTestClass();

        SchoolClass::setTeachers($classId, [$teacherId1, $teacherId2]);
        SchoolClass::setTeachers($classId, [$teacherId1]);

        $teachers = SchoolClass::getTeachers($classId);
        $this->assertCount(1, $teachers);
    }

    public function testCreateWithHeadTeacher(): void
    {
        $userId = $this->createTestUser();
        $teacherId = $this->createTestTeacher($userId);

        $classId = SchoolClass::create([
            'name' => '8c',
            'school_year' => '2025/2026',
            'head_teacher_id' => $teacherId,
        ]);

        $class = SchoolClass::findById($classId);
        $this->assertEquals($teacherId, $class['head_teacher_id']);
    }
}
