<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\AbsenceTeacher;
use OpenClassbook\Tests\DatabaseTestCase;

class AbsenceTeacherModelTest extends DatabaseTestCase
{
    private int $teacherId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createTestUser();
        $this->teacherId = $this->createTestTeacher($this->userId);
    }

    public function testCreateAndFindAll(): void
    {
        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-12',
            'type' => 'krank',
            'reason' => 'Grippe',
            'created_by' => $this->userId,
        ]);

        $absences = AbsenceTeacher::findAll();
        $this->assertCount(1, $absences);
        $this->assertEquals('krank', $absences[0]['type']);
        $this->assertEquals('Grippe', $absences[0]['reason']);
    }

    public function testFindAllFilterByTeacherId(): void
    {
        $userId2 = $this->createTestUser(['username' => 'teacher2', 'email' => 't2@example.com']);
        $teacherId2 = $this->createTestTeacher($userId2, ['abbreviation' => 'T2']);

        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'type' => 'krank',
        ]);
        AbsenceTeacher::create([
            'teacher_id' => $teacherId2,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'type' => 'fortbildung',
        ]);

        $absences = AbsenceTeacher::findAll(['teacher_id' => $this->teacherId]);
        $this->assertCount(1, $absences);
    }

    public function testFindAllFilterByType(): void
    {
        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'type' => 'krank',
        ]);
        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-11',
            'date_to' => '2026-03-11',
            'type' => 'fortbildung',
        ]);

        $absences = AbsenceTeacher::findAll(['type' => 'fortbildung']);
        $this->assertCount(1, $absences);
        $this->assertEquals('fortbildung', $absences[0]['type']);
    }

    public function testFindAllFilterByDateRange(): void
    {
        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-05',
            'type' => 'krank',
        ]);
        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-20',
            'date_to' => '2026-03-22',
            'type' => 'fortbildung',
        ]);

        $absences = AbsenceTeacher::findAll([
            'date_from' => '2026-03-15',
            'date_to' => '2026-03-25',
        ]);
        $this->assertCount(1, $absences);
    }

    public function testUpdate(): void
    {
        $id = AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'type' => 'krank',
        ]);

        AbsenceTeacher::update($id, [
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-12',
            'type' => 'fortbildung',
            'reason' => 'Seminar',
        ]);

        $absence = self::$pdo->query("SELECT * FROM absences_teachers WHERE id = $id")->fetch();
        $this->assertEquals('fortbildung', $absence['type']);
        $this->assertEquals('2026-03-12', $absence['date_to']);
    }

    public function testDelete(): void
    {
        $id = AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'type' => 'krank',
        ]);

        AbsenceTeacher::delete($id);

        $result = self::$pdo->query("SELECT COUNT(*) as cnt FROM absences_teachers WHERE id = $id")->fetch();
        $this->assertEquals(0, $result['cnt']);
    }

    public function testDefaultTypeIsKrank(): void
    {
        AbsenceTeacher::create([
            'teacher_id' => $this->teacherId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
        ]);

        $absences = AbsenceTeacher::findAll();
        $this->assertEquals('krank', $absences[0]['type']);
    }
}
