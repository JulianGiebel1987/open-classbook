<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\StudentClassHistory;
use OpenClassbook\Tests\DatabaseTestCase;

class StudentClassHistoryTest extends DatabaseTestCase
{
    public function testRecordCreatesEntry(): void
    {
        $classA = $this->createTestClass(['name' => '4a']);
        $classB = $this->createTestClass(['name' => '5a']);
        $studentId = $this->createTestStudent($classA);

        StudentClassHistory::record($studentId, $classA, $classB, null);

        $entries = StudentClassHistory::findByClassId($classB);
        $this->assertCount(1, $entries);
        $this->assertEquals($studentId, $entries[0]['student_id']);
        $this->assertEquals($classA, $entries[0]['from_class_id']);
        $this->assertEquals($classB, $entries[0]['to_class_id']);
        $this->assertEquals('5a', $entries[0]['to_class_name']);
        $this->assertEquals('4a', $entries[0]['from_class_name']);
    }

    public function testFindByClassIdReturnsIncomingAndOutgoing(): void
    {
        $classA = $this->createTestClass(['name' => '4a']);
        $classB = $this->createTestClass(['name' => '5a']);
        $classC = $this->createTestClass(['name' => '6a']);
        $studentId = $this->createTestStudent($classA);

        // Nach classB (classB ist Ziel), dann von classB nach classC (classB ist Quelle)
        StudentClassHistory::record($studentId, $classA, $classB, null);
        StudentClassHistory::record($studentId, $classB, $classC, null);

        // classB kommt in beiden Vorgaengen vor
        $entriesB = StudentClassHistory::findByClassId($classB);
        $this->assertCount(2, $entriesB);

        // classA nur im ersten, classC nur im zweiten
        $this->assertCount(1, StudentClassHistory::findByClassId($classA));
        $this->assertCount(1, StudentClassHistory::findByClassId($classC));
    }

    public function testRecordWithNullFromClass(): void
    {
        $classB = $this->createTestClass(['name' => '5a']);
        $studentId = $this->createTestStudent($classB);

        StudentClassHistory::record($studentId, null, $classB, null);

        $entries = StudentClassHistory::findByClassId($classB);
        $this->assertCount(1, $entries);
        $this->assertNull($entries[0]['from_class_id']);
    }

    public function testRecordStoresChangedBy(): void
    {
        $userId = $this->createTestUser(['username' => 'sekretariat1']);
        $classA = $this->createTestClass(['name' => '4a']);
        $classB = $this->createTestClass(['name' => '5a']);
        $studentId = $this->createTestStudent($classA);

        StudentClassHistory::record($studentId, $classA, $classB, $userId);

        $entries = StudentClassHistory::findByClassId($classB);
        $this->assertEquals($userId, $entries[0]['changed_by']);
        $this->assertEquals('sekretariat1', $entries[0]['changed_by_username']);
    }
}
