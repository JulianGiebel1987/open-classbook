<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\ContentTemplate;
use OpenClassbook\Tests\DatabaseTestCase;

class ContentTemplateModelTest extends DatabaseTestCase
{
    private int $userId;
    private int $otherUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = $this->createTestUser();
        $this->otherUserId = $this->createTestUser(['username' => 'other', 'email' => 'other@example.com']);
    }

    public function testCreateAndFindById(): void
    {
        $id = ContentTemplate::create([
            'owner_user_id' => $this->userId,
            'category'      => 'Mathematik',
            'topic'         => 'Addieren im Hunderterraum',
            'notes'         => 'Arbeitsblatt 3',
        ]);

        $template = ContentTemplate::findById($id);
        $this->assertNotNull($template);
        $this->assertEquals('Mathematik', $template['category']);
        $this->assertEquals('Addieren im Hunderterraum', $template['topic']);
        $this->assertEquals('Arbeitsblatt 3', $template['notes']);
        $this->assertEquals($this->userId, $template['owner_user_id']);
    }

    public function testFindForUserReturnsOwnAndShared(): void
    {
        // Eigene persoenliche Vorlage
        ContentTemplate::create([
            'owner_user_id' => $this->userId,
            'category'      => 'Deutsch',
            'topic'         => 'Diktat',
        ]);
        // Geteilte Vorlage (owner NULL)
        ContentTemplate::create([
            'owner_user_id' => null,
            'category'      => 'Mathematik',
            'topic'         => 'Einmaleins',
        ]);
        // Fremde persoenliche Vorlage – darf NICHT erscheinen
        ContentTemplate::create([
            'owner_user_id' => $this->otherUserId,
            'category'      => 'Sport',
            'topic'         => 'Weitsprung',
        ]);

        $topics = array_column(ContentTemplate::findForUser($this->userId, 'lehrer'), 'topic');
        $this->assertContains('Diktat', $topics);
        $this->assertContains('Einmaleins', $topics);
        $this->assertNotContains('Weitsprung', $topics);
        $this->assertCount(2, $topics);
    }

    public function testFindForUserOrdersPersonalBeforeShared(): void
    {
        ContentTemplate::create(['owner_user_id' => null, 'category' => 'AAA', 'topic' => 'Geteilt']);
        ContentTemplate::create(['owner_user_id' => $this->userId, 'category' => 'ZZZ', 'topic' => 'Persoenlich']);

        $result = ContentTemplate::findForUser($this->userId, 'lehrer');
        // Persoenliche zuerst, obwohl Kategorie alphabetisch spaeter liegt
        $this->assertEquals('Persoenlich', $result[0]['topic']);
        $this->assertEquals('Geteilt', $result[1]['topic']);
    }

    public function testUpdate(): void
    {
        $id = ContentTemplate::create([
            'owner_user_id' => $this->userId,
            'category'      => 'Alt',
            'topic'         => 'Original',
        ]);

        ContentTemplate::update($id, [
            'owner_user_id' => null,
            'category'      => 'Neu',
            'topic'         => 'Geaendert',
            'notes'         => 'Notiz',
        ]);

        $template = ContentTemplate::findById($id);
        $this->assertNull($template['owner_user_id']);
        $this->assertEquals('Neu', $template['category']);
        $this->assertEquals('Geaendert', $template['topic']);
        $this->assertEquals('Notiz', $template['notes']);
    }

    public function testDelete(): void
    {
        $id = ContentTemplate::create(['owner_user_id' => $this->userId, 'topic' => 'Weg damit']);
        ContentTemplate::delete($id);
        $this->assertNull(ContentTemplate::findById($id));
    }

    public function testGetCategoriesDistinctAndVisibleOnly(): void
    {
        ContentTemplate::create(['owner_user_id' => $this->userId, 'category' => 'Mathematik', 'topic' => 'A']);
        ContentTemplate::create(['owner_user_id' => $this->userId, 'category' => 'Mathematik', 'topic' => 'B']);
        ContentTemplate::create(['owner_user_id' => null, 'category' => 'Deutsch', 'topic' => 'C']);
        ContentTemplate::create(['owner_user_id' => $this->userId, 'category' => null, 'topic' => 'Ohne Kategorie']);
        ContentTemplate::create(['owner_user_id' => $this->otherUserId, 'category' => 'Sport', 'topic' => 'Fremd']);

        $categories = ContentTemplate::getCategories($this->userId, 'lehrer');
        $this->assertEquals(['Deutsch', 'Mathematik'], $categories);
        $this->assertNotContains('Sport', $categories);
    }

    public function testCanManagePersonalOwnerOnly(): void
    {
        $template = ['owner_user_id' => $this->userId];
        $this->assertTrue(ContentTemplate::canManage($template, $this->userId, 'lehrer'));
        $this->assertFalse(ContentTemplate::canManage($template, $this->otherUserId, 'lehrer'));
    }

    public function testCanManageSharedRequiresStaffRole(): void
    {
        $shared = ['owner_user_id' => null];
        $this->assertTrue(ContentTemplate::canManage($shared, $this->userId, 'admin'));
        $this->assertTrue(ContentTemplate::canManage($shared, $this->userId, 'schulleitung'));
        $this->assertTrue(ContentTemplate::canManage($shared, $this->userId, 'sekretariat'));
        $this->assertFalse(ContentTemplate::canManage($shared, $this->userId, 'lehrer'));
    }

    public function testCanManageAdminAlways(): void
    {
        $foreign = ['owner_user_id' => $this->otherUserId];
        $this->assertTrue(ContentTemplate::canManage($foreign, $this->userId, 'admin'));
    }
}
