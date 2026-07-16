<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\StudentRemark;
use OpenClassbook\Tests\DatabaseTestCase;

class StudentRemarkModelTest extends DatabaseTestCase
{
    private int $classId;
    private int $teacherId;

    protected function setUp(): void
    {
        parent::setUp();
        $userId = $this->createTestUser();
        $this->teacherId = $this->createTestTeacher($userId);
        $this->classId = $this->createTestClass();
    }

    public function testCountByClassGroupsPerStudent(): void
    {
        $studentA = $this->createTestStudent($this->classId, ['firstname' => 'Anna']);
        $studentB = $this->createTestStudent($this->classId, ['firstname' => 'Ben']);

        StudentRemark::create([
            'student_id' => $studentA,
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'remark' => 'Erste Bemerkung',
            'remark_date' => '2026-03-10',
        ]);
        StudentRemark::create([
            'student_id' => $studentA,
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'remark' => 'Zweite Bemerkung',
            'remark_date' => '2026-03-11',
        ]);
        StudentRemark::create([
            'student_id' => $studentB,
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'remark' => 'Bemerkung für Ben',
            'remark_date' => '2026-03-12',
        ]);

        $counts = StudentRemark::countByClass($this->classId);

        $this->assertSame(2, $counts[$studentA]);
        $this->assertSame(1, $counts[$studentB]);
    }

    public function testCountByClassEmptyReturnsEmptyArray(): void
    {
        $this->assertSame([], StudentRemark::countByClass($this->classId));
    }
}
