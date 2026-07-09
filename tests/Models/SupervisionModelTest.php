<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\SupervisionPlan;
use OpenClassbook\Models\SupervisionBreak;
use OpenClassbook\Models\SupervisionLocation;
use OpenClassbook\Models\SupervisionAssignment;
use OpenClassbook\Tests\DatabaseTestCase;

class SupervisionModelTest extends DatabaseTestCase
{
    private int $creator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator = $this->createTestUser(['username' => 'planner', 'role' => 'admin']);
    }

    private function makeTeacher(string $lastname, string $abbr): int
    {
        $userId = $this->createTestUser(['username' => 't' . uniqid(), 'role' => 'lehrer']);
        return $this->createTestTeacher($userId, ['lastname' => $lastname, 'abbreviation' => $abbr]);
    }

    private function makePlan(): int
    {
        return SupervisionPlan::create([
            'name' => 'Schulhof',
            'school_year' => '2025/2026',
            'days_of_week' => [1, 2, 3, 4, 5],
            'created_by' => $this->creator,
        ]);
    }

    public function testCreatePlanEncodesDaysAndFindBySchoolYear(): void
    {
        $planId = $this->makePlan();

        $plan = SupervisionPlan::findById($planId);
        $this->assertSame('Schulhof', $plan['name']);
        $this->assertSame([1, 2, 3, 4, 5], json_decode($plan['days_of_week'], true));

        $this->assertNotNull(SupervisionPlan::findBySchoolYear('2025/2026'));
        $this->assertNull(SupervisionPlan::findBySchoolYear('2099/2100'));
    }

    public function testPublishAndUnpublish(): void
    {
        $planId = $this->makePlan();

        SupervisionPlan::publish($planId, $this->creator);
        $this->assertEquals(1, (int) SupervisionPlan::findById($planId)['is_published']);

        SupervisionPlan::unpublish($planId);
        $this->assertEquals(0, (int) SupervisionPlan::findById($planId)['is_published']);
    }

    public function testBreaksAndLocationsByPlanAreOrdered(): void
    {
        $planId = $this->makePlan();
        SupervisionBreak::create(['plan_id' => $planId, 'label' => '2. Pause', 'sort_order' => 1]);
        SupervisionBreak::create(['plan_id' => $planId, 'label' => '1. Pause', 'sort_order' => 0]);

        $breaks = SupervisionBreak::findByPlan($planId);
        $this->assertCount(2, $breaks);
        $this->assertSame('1. Pause', $breaks[0]['label']);

        SupervisionLocation::create(['plan_id' => $planId, 'name' => 'Tor', 'sort_order' => 0]);
        $this->assertCount(1, SupervisionLocation::findByPlan($planId));
    }

    public function testAssignmentJoinsTeacherAndCounts(): void
    {
        $planId = $this->makePlan();
        $breakId = SupervisionBreak::create(['plan_id' => $planId, 'label' => '1. Pause', 'sort_order' => 0]);
        $locId = SupervisionLocation::create(['plan_id' => $planId, 'name' => 'Tor', 'sort_order' => 0]);
        $teacherId = $this->makeTeacher('Aufsicht', 'AU');

        $id = SupervisionAssignment::create([
            'plan_id' => $planId, 'break_id' => $breakId, 'location_id' => $locId,
            'day_of_week' => 1, 'teacher_id' => $teacherId,
        ]);

        $row = SupervisionAssignment::findById($id);
        $this->assertSame('Aufsicht', $row['lastname']);
        $this->assertSame('AU', $row['abbreviation']);

        $this->assertTrue(SupervisionAssignment::exists($breakId, $locId, 1, $teacherId));
        $counts = SupervisionAssignment::getTeacherCounts($planId);
        $this->assertSame(1, $counts[$teacherId]);
    }

    public function testCheckTeacherConflictDetectsSameTimeOtherLocation(): void
    {
        $planId = $this->makePlan();
        $breakId = SupervisionBreak::create(['plan_id' => $planId, 'label' => '1. Pause', 'sort_order' => 0]);
        $tor = SupervisionLocation::create(['plan_id' => $planId, 'name' => 'Tor', 'sort_order' => 0]);
        $sand = SupervisionLocation::create(['plan_id' => $planId, 'name' => 'Sandkasten', 'sort_order' => 1]);
        $teacherId = $this->makeTeacher('Aufsicht', 'AU');

        SupervisionAssignment::create([
            'plan_id' => $planId, 'break_id' => $breakId, 'location_id' => $tor,
            'day_of_week' => 1, 'teacher_id' => $teacherId,
        ]);

        // Gleiche Zeit, anderer Ort -> Konflikt
        $conflict = SupervisionAssignment::checkTeacherConflict($planId, $teacherId, 1, $breakId, $sand);
        $this->assertNotNull($conflict);
        $this->assertSame('Tor', $conflict['location_name']);

        // Anderer Tag -> kein Konflikt
        $this->assertNull(SupervisionAssignment::checkTeacherConflict($planId, $teacherId, 2, $breakId, $sand));
    }

    public function testFindByPlanAndTeacherReturnsLabels(): void
    {
        $planId = $this->makePlan();
        $breakId = SupervisionBreak::create(['plan_id' => $planId, 'label' => '1. Pause', 'start_time' => '09:30:00', 'sort_order' => 0]);
        $locId = SupervisionLocation::create(['plan_id' => $planId, 'name' => 'Rutsche', 'sort_order' => 0]);
        $teacherId = $this->makeTeacher('Aufsicht', 'AU');

        SupervisionAssignment::create([
            'plan_id' => $planId, 'break_id' => $breakId, 'location_id' => $locId,
            'day_of_week' => 3, 'teacher_id' => $teacherId,
        ]);

        $rows = SupervisionAssignment::findByPlanAndTeacher($planId, $teacherId);
        $this->assertCount(1, $rows);
        $this->assertSame('1. Pause', $rows[0]['break_label']);
        $this->assertSame('Rutsche', $rows[0]['location_name']);
    }

    public function testDeleteAssignment(): void
    {
        $planId = $this->makePlan();
        $breakId = SupervisionBreak::create(['plan_id' => $planId, 'label' => '1. Pause', 'sort_order' => 0]);
        $locId = SupervisionLocation::create(['plan_id' => $planId, 'name' => 'Tor', 'sort_order' => 0]);
        $teacherId = $this->makeTeacher('Aufsicht', 'AU');
        $id = SupervisionAssignment::create([
            'plan_id' => $planId, 'break_id' => $breakId, 'location_id' => $locId,
            'day_of_week' => 1, 'teacher_id' => $teacherId,
        ]);

        SupervisionAssignment::delete($id);
        $this->assertNull(SupervisionAssignment::findById($id));
    }
}
