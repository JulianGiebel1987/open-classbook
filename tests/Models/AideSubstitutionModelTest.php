<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\AideSubstitution;
use OpenClassbook\Models\AbsenceSchoolAide;
use OpenClassbook\Tests\DatabaseTestCase;

class AideSubstitutionModelTest extends DatabaseTestCase
{
    private int $creator;
    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = $this->createTestUser(['username' => 'planner', 'role' => 'admin']);
        $this->classId = $this->createTestClass();
    }

    private function makeAide(string $lastname): int
    {
        $userId = $this->createTestUser(['username' => 'a' . uniqid(), 'role' => 'schulbegleiter']);
        return $this->createTestAide($userId, ['lastname' => $lastname]);
    }

    public function testCreateAndFindByIdJoinsNames(): void
    {
        $absent = $this->makeAide('Abwesend');
        $sub = $this->makeAide('Ersatz');
        $studentId = $this->createTestStudent($this->classId, ['lastname' => 'Kind']);

        $id = AideSubstitution::create([
            'date_from' => '2026-07-01', 'date_to' => '2026-07-03',
            'absent_aide_id' => $absent, 'student_id' => $studentId,
            'substitute_aide_id' => $sub, 'priority' => 1, 'status' => 'geplant',
            'created_by' => $this->creator,
        ]);

        $row = AideSubstitution::findById($id);
        $this->assertEquals('Abwesend', $row['absent_lastname']);
        $this->assertEquals('Ersatz', $row['substitute_lastname']);
        $this->assertEquals('Kind', $row['student_lastname']);
    }

    public function testFindByDateRangeSortsByPriority(): void
    {
        $absent = $this->makeAide('Abwesend');
        $s1 = $this->createTestStudent($this->classId, ['firstname' => 'A']);
        $s2 = $this->createTestStudent($this->classId, ['firstname' => 'B']);

        AideSubstitution::create(['date_from' => '2026-07-01', 'date_to' => '2026-07-01', 'absent_aide_id' => $absent, 'student_id' => $s1, 'priority' => 4, 'created_by' => $this->creator]);
        AideSubstitution::create(['date_from' => '2026-07-01', 'date_to' => '2026-07-01', 'absent_aide_id' => $absent, 'student_id' => $s2, 'priority' => 1, 'created_by' => $this->creator]);

        $needs = AideSubstitution::findByDateRange('2026-07-01', '2026-07-31');
        $this->assertCount(2, $needs);
        // Dringlichste (Prioritaet 1) zuerst
        $this->assertEquals(1, (int) $needs[0]['priority']);
    }

    public function testFindByDateRangeRespectsOverlap(): void
    {
        $absent = $this->makeAide('Abwesend');
        $studentId = $this->createTestStudent($this->classId);
        AideSubstitution::create(['date_from' => '2026-07-01', 'date_to' => '2026-07-03', 'absent_aide_id' => $absent, 'student_id' => $studentId, 'priority' => 2, 'created_by' => $this->creator]);

        $this->assertCount(1, AideSubstitution::findByDateRange('2026-07-03', '2026-07-10'));
        $this->assertCount(0, AideSubstitution::findByDateRange('2026-07-04', '2026-07-10'));
    }

    public function testFindForAbsenceAndStudent(): void
    {
        $absent = $this->makeAide('Abwesend');
        $studentId = $this->createTestStudent($this->classId);
        $absenceId = AbsenceSchoolAide::create(['aide_id' => $absent, 'date_from' => '2026-07-01', 'date_to' => '2026-07-02', 'type' => 'krank']);

        AideSubstitution::create([
            'date_from' => '2026-07-01', 'date_to' => '2026-07-02',
            'absent_aide_id' => $absent, 'student_id' => $studentId,
            'absence_aide_id' => $absenceId, 'priority' => 3, 'created_by' => $this->creator,
        ]);

        $found = AideSubstitution::findForAbsenceAndStudent($absenceId, $studentId);
        $this->assertNotNull($found);
        $this->assertNull(AideSubstitution::findForAbsenceAndStudent($absenceId, 9999));
    }

    public function testGetAbsentAidesForDateRange(): void
    {
        $aide = $this->makeAide('Krank');
        AbsenceSchoolAide::create(['aide_id' => $aide, 'date_from' => '2026-07-01', 'date_to' => '2026-07-05', 'type' => 'krank']);

        $absent = AideSubstitution::getAbsentAidesForDateRange('2026-07-03', '2026-07-03');
        $this->assertCount(1, $absent);
        $this->assertEquals('Krank', $absent[0]['lastname']);

        $this->assertCount(0, AideSubstitution::getAbsentAidesForDateRange('2026-08-01', '2026-08-02'));
    }

    public function testGetAvailableAidesExcludesAbsentAndSelf(): void
    {
        $absent = $this->makeAide('Abwesend');
        $free = $this->makeAide('Frei');
        $alsoAbsent = $this->makeAide('AuchKrank');
        AbsenceSchoolAide::create(['aide_id' => $alsoAbsent, 'date_from' => '2026-07-01', 'date_to' => '2026-07-05', 'type' => 'krank']);

        $available = AideSubstitution::getAvailableAides('2026-07-02', '2026-07-02', $absent);
        $ids = array_map('intval', array_column($available, 'id'));
        $this->assertContains($free, $ids);
        $this->assertNotContains($absent, $ids);       // ausgeschlossen (excludeAideId)
        $this->assertNotContains($alsoAbsent, $ids);   // ausgeschlossen (abwesend)
    }

    public function testUpdateAndDelete(): void
    {
        $absent = $this->makeAide('Abwesend');
        $sub = $this->makeAide('Ersatz');
        $studentId = $this->createTestStudent($this->classId);
        $id = AideSubstitution::create(['date_from' => '2026-07-01', 'date_to' => '2026-07-02', 'absent_aide_id' => $absent, 'student_id' => $studentId, 'priority' => 3, 'created_by' => $this->creator]);

        AideSubstitution::update($id, ['substitute_aide_id' => $sub, 'priority' => 1, 'status' => 'geplant', 'notes' => 'ok']);
        $row = AideSubstitution::findById($id);
        $this->assertEquals(1, (int) $row['priority']);
        $this->assertEquals($sub, (int) $row['substitute_aide_id']);

        AideSubstitution::delete($id);
        $this->assertNull(AideSubstitution::findById($id));
    }

    public function testPriorityLabel(): void
    {
        $this->assertEquals('Sehr hoch', AideSubstitution::priorityLabel(1));
        $this->assertEquals('Niedrig', AideSubstitution::priorityLabel(4));
    }
}
