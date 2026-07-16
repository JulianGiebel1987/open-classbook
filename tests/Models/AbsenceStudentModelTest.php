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

        // Mehrtages-Eintraege werden in Einzeltage aufgesplittet (10., 11., 12. Maerz = 3 Zeilen)
        $absences = AbsenceStudent::findAll();
        $this->assertCount(3, $absences);
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

    public function testGetClassSummaryAggregatesDaysPerStudentAndStatus(): void
    {
        $studentId2 = $this->createTestStudent($this->classId, ['firstname' => 'Ben']);

        // Schüler 1: 3 entschuldigte Tage (10.–12.) + 1 unentschuldigter Tag (13.)
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-12',
            'excused' => 'ja',
        ]);
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-13',
            'date_to' => '2026-03-13',
            'excused' => 'nein',
        ]);
        // Schüler 2: 1 offener Tag
        AbsenceStudent::create([
            'student_id' => $studentId2,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'excused' => 'offen',
        ]);

        $rows = AbsenceStudent::getClassSummary($this->classId);

        // Summen je Schüler:in aus den Rohzeilen zusammenfassen
        $byStudent = [];
        foreach ($rows as $row) {
            $byStudent[(int) $row['student_id']][$row['excused']] = (int) $row['total_days'];
        }

        $this->assertSame(3, $byStudent[$this->studentId]['ja']);
        $this->assertSame(1, $byStudent[$this->studentId]['nein']);
        $this->assertSame(1, $byStudent[$studentId2]['offen']);
    }

    public function testGetClassSummaryRespectsDateRange(): void
    {
        // Innerhalb des Schuljahres 2025/2026 (Aug–Jul)
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'excused' => 'ja',
        ]);
        // Außerhalb (nach dem 31.07.2026)
        AbsenceStudent::create([
            'student_id' => $this->studentId,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-01',
            'excused' => 'ja',
        ]);

        $rows = AbsenceStudent::getClassSummary($this->classId, '2025-08-01', '2026-07-31');

        $total = 0;
        foreach ($rows as $row) {
            $total += (int) $row['total_days'];
        }
        $this->assertSame(1, $total, 'Nur die Fehlzeit im Schuljahr wird gezählt.');
    }

    public function testGetClassSummaryOnlyCountsRequestedClass(): void
    {
        $classId2 = $this->createTestClass(['name' => '6b']);
        $studentId2 = $this->createTestStudent($classId2, ['firstname' => 'Ben']);

        AbsenceStudent::create([
            'student_id' => $studentId2,
            'date_from' => '2026-03-10',
            'date_to' => '2026-03-10',
            'excused' => 'ja',
        ]);

        $rows = AbsenceStudent::getClassSummary($this->classId);
        $this->assertCount(0, $rows);
    }
}
