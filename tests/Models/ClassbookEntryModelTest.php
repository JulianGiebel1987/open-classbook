<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\ClassbookEntry;
use OpenClassbook\Tests\DatabaseTestCase;

class ClassbookEntryModelTest extends DatabaseTestCase
{
    private int $classId;
    private int $teacherId;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createTestUser();
        $this->teacherId = $this->createTestTeacher($this->userId);
        $this->classId = $this->createTestClass();
    }

    public function testCreateAndFindByClass(): void
    {
        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-14',
            'lesson' => 1,
            'topic' => 'Bruchrechnung',
            'notes' => 'Hausaufgaben S. 42',
        ]);

        $entries = ClassbookEntry::findByClass($this->classId);
        $this->assertCount(1, $entries);
        $this->assertEquals('Bruchrechnung', $entries[0]['topic']);
        $this->assertEquals(1, $entries[0]['lesson']);
    }

    public function testFindByClassWithDateFilter(): void
    {
        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-10',
            'lesson' => 1,
            'topic' => 'Thema 1',
        ]);
        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-14',
            'lesson' => 2,
            'topic' => 'Thema 2',
        ]);

        $entries = ClassbookEntry::findByClass($this->classId, [
            'date_from' => '2026-03-12',
        ]);
        $this->assertCount(1, $entries);
        $this->assertEquals('Thema 2', $entries[0]['topic']);
    }

    public function testFindByClassWithTeacherFilter(): void
    {
        $userId2 = $this->createTestUser(['username' => 'teacher2', 'email' => 't2@example.com']);
        $teacherId2 = $this->createTestTeacher($userId2, ['abbreviation' => 'T2']);

        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-14',
            'lesson' => 1,
            'topic' => 'Mathe',
        ]);
        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $teacherId2,
            'entry_date' => '2026-03-14',
            'lesson' => 2,
            'topic' => 'Deutsch',
        ]);

        $entries = ClassbookEntry::findByClass($this->classId, [
            'teacher_id' => $teacherId2,
        ]);
        $this->assertCount(1, $entries);
        $this->assertEquals('Deutsch', $entries[0]['topic']);
    }

    public function testUpdate(): void
    {
        $id = ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-14',
            'lesson' => 1,
            'topic' => 'Original',
        ]);

        ClassbookEntry::update($id, [
            'entry_date' => '2026-03-14',
            'lesson' => 2,
            'topic' => 'Aktualisiert',
            'notes' => 'Neue Notizen',
        ]);

        // Verify via direct query since findById requires JOIN
        $entry = self::$pdo->query("SELECT * FROM classbook_entries WHERE id = $id")->fetch();
        $this->assertEquals('Aktualisiert', $entry['topic']);
        $this->assertEquals(2, $entry['lesson']);
    }

    public function testCanEditAdminAlwaysTrue(): void
    {
        $entry = ['teacher_id' => $this->teacherId, 'created_at' => date('Y-m-d H:i:s')];
        $this->assertTrue(ClassbookEntry::canEdit($entry, 9999, 'admin'));
    }

    public function testCanEditTeacherOwnEntryWithin24h(): void
    {
        $entry = [
            'teacher_id' => $this->teacherId,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->assertTrue(ClassbookEntry::canEdit($entry, $this->userId, 'lehrer'));
    }

    public function testCanEditTeacherOwnEntryAfter24h(): void
    {
        $entry = [
            'teacher_id' => $this->teacherId,
            'created_at' => date('Y-m-d H:i:s', strtotime('-25 hours')),
        ];
        $this->assertFalse(ClassbookEntry::canEdit($entry, $this->userId, 'lehrer'));
    }

    public function testCanEditTeacherOtherEntry(): void
    {
        $userId2 = $this->createTestUser(['username' => 'other', 'email' => 'other@example.com']);
        $this->createTestTeacher($userId2, ['abbreviation' => 'OT']);

        $entry = [
            'teacher_id' => $this->teacherId,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->assertFalse(ClassbookEntry::canEdit($entry, $userId2, 'lehrer'));
    }

    public function testCanEditOtherRolesFalse(): void
    {
        $entry = ['teacher_id' => $this->teacherId, 'created_at' => date('Y-m-d H:i:s')];
        $this->assertFalse(ClassbookEntry::canEdit($entry, $this->userId, 'sekretariat'));
        $this->assertFalse(ClassbookEntry::canEdit($entry, $this->userId, 'schulleitung'));
    }

    public function testMultipleEntriesOrderByDateAndLesson(): void
    {
        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-14',
            'lesson' => 3,
            'topic' => 'Stunde 3',
        ]);
        ClassbookEntry::create([
            'class_id' => $this->classId,
            'teacher_id' => $this->teacherId,
            'entry_date' => '2026-03-14',
            'lesson' => 1,
            'topic' => 'Stunde 1',
        ]);

        $entries = ClassbookEntry::findByClass($this->classId);
        $this->assertEquals(1, $entries[0]['lesson']);
        $this->assertEquals(3, $entries[1]['lesson']);
    }
}
