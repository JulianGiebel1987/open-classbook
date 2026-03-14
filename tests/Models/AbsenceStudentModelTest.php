<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\AbsenceStudent;
use OpenClassbook\Tests\DatabaseTestCase;

class AbsenceStudentModelTest extends DatabaseTestCase
{
    private int $classId;
    private int $studentId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createTestUser();
        $this->classId = $this->createTestClass();
        $this->studentId = $this->createTestStudent($this->classId);
    }

    public function testCreateAndFindAll(): void
    {
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-12',
            'excused' => 'ja',
            'reason' => 'Krank',
            'created_by' => $this->userId,
        ]);

        $absences = AbsenceStudent::findAll();
        $this->assertCount(1, $absences);
        $this->assertEquals('ja', $absences[0]['excused']);
        $this->assertEquals('Krank', $absences[0]['reason']);
    }

    public function testFindAllFilterByClassId(): void
    {
        $classId2 = $this->createTestClass(['name' => '6b']);
        $studentId2 = $this->createTestStudent($classId2, ['firstname' => 'Ben']);

        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
        ]);
        AbsenceStudent::create([
            'student_id' => $studentId2,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
        ]);

        $absences = AbsenceStudent::findAll(['class_id' => $this->classId]);
        $this->assertCount(1, $absences);
    }

    public function testFindAllFilterByExcused(): void
    {
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'excused' => 'ja',
        ]);
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-11',
            'date_to' => '2026-03-11',
            'excused' => 'nein',
        ]);

        $absences = AbsenceStudent::findAll(['excused' => 'ja']);
        $this->assertCount(1, $absences);
    }

    public function testUpdate(): void
    {
        $id = AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'excused' => 'offen',
        ]);

        AbsenceStudent::update($id, [
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-11',
            'excused' => 'ja',
            'reason' => 'Arztbesuch',
        ]);

        $absence = self::$pdo->query("SELECT * FROM absences_students WHERE id = $id")->fetch();
        $this->assertEquals('ja', $absence['excused']);
        $this->assertEquals('2026-03-11', $absence['date_to']);
    }

    public function testDelete(): void
    {
        $id = AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
        ]);

        AbsenceStudent::delete($id);

        $result = self::$pdo->query("SELECT COUNT(*) as cnt FROM absences_students WHERE id = $id")->fetch();
        $this->assertEquals(0, $result['cnt']);
    }

    public function testCountUnexcused(): void
    {
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'excused' => 'offen',
        ]);
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-11',
            'date_to' => '2026-03-11',
            'excused' => 'ja',
        ]);

        // SQLite uses "offen" as string, should match
        $count = AbsenceStudent::countUnexcused();
        $this->assertEquals(1, $count);
    }

    public function testDefaultExcusedIsOffen(): void
    {
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
        ]);

        $absences = AbsenceStudent::findAll();
        $this->assertEquals('offen', $absences[0]['excused']);
    }
}
