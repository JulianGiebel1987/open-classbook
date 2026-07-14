<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\App;
use OpenClassbook\Services\DataExportService;
use OpenClassbook\Services\EncryptionService;
use OpenClassbook\Tests\DatabaseTestCase;

class DataExportServiceTest extends DatabaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$pdo->exec('
            CREATE TABLE messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                sender_id INTEGER NOT NULL,
                body TEXT NOT NULL,
                read_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
        self::$pdo->exec('
            CREATE TABLE group_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                sender_id INTEGER NOT NULL,
                body TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $config = App::config();
        $config['security']['app_encryption_key'] = bin2hex(random_bytes(32));
        App::setConfig($config);
    }

    public function testReturnsNullForUnknownUser(): void
    {
        $this->assertNull(DataExportService::exportUser(999999));
    }

    public function testExportOmitsSensitiveSecurityFields(): void
    {
        $userId = $this->createTestUser();
        self::$pdo->prepare('UPDATE users SET two_factor_secret = ?, password_reset_token = ? WHERE id = ?')
            ->execute(['secret-xyz', 'reset-token', $userId]);

        $export = DataExportService::exportUser($userId);

        $this->assertArrayHasKey('konto', $export);
        $this->assertArrayNotHasKey('password_hash', $export['konto']);
        $this->assertArrayNotHasKey('two_factor_secret', $export['konto']);
        $this->assertArrayNotHasKey('password_reset_token', $export['konto']);
        // Nicht-sensible Felder bleiben enthalten.
        $this->assertSame('testuser', $export['konto']['username']);
    }

    public function testExportIncludesTeacherAbsencesAndClassbookEntries(): void
    {
        $userId = $this->createTestUser(['username' => 'lehrer1', 'role' => 'lehrer']);
        $teacherId = $this->createTestTeacher($userId);
        $classId = $this->createTestClass();
        $this->createTestClassbookEntry($classId, $teacherId, ['topic' => 'Bruchrechnung']);

        self::$pdo->prepare(
            'INSERT INTO absences_teachers (teacher_id, date_from, date_to, type, reason) VALUES (?, ?, ?, ?, ?)'
        )->execute([$teacherId, '2026-01-10', '2026-01-12', 'krank', 'Grippe']);

        $export = DataExportService::exportUser($userId);

        $this->assertNotNull($export['lehrerdaten']);
        $this->assertCount(1, $export['lehrerdaten']['abwesenheiten']);
        $this->assertSame('Grippe', $export['lehrerdaten']['abwesenheiten'][0]['reason']);
        $this->assertCount(1, $export['lehrerdaten']['klassenbucheintraege']);
        $this->assertSame('Bruchrechnung', $export['lehrerdaten']['klassenbucheintraege'][0]['topic']);
    }

    public function testExportDecryptsSentMessages(): void
    {
        $userId = $this->createTestUser();

        self::$pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)')
            ->execute([1, $userId, EncryptionService::encrypt('Direkte Nachricht')]);
        self::$pdo->prepare('INSERT INTO group_messages (group_id, sender_id, body) VALUES (?, ?, ?)')
            ->execute([1, $userId, EncryptionService::encrypt('Gruppen-Nachricht')]);

        $export = DataExportService::exportUser($userId);

        $this->assertSame('Direkte Nachricht', $export['gesendete_nachrichten']['einzelnachrichten'][0]['body']);
        $this->assertSame('Gruppen-Nachricht', $export['gesendete_nachrichten']['gruppennachrichten'][0]['body']);
    }

    public function testExportIncludesAuditEntries(): void
    {
        $userId = $this->createTestUser();
        self::$pdo->prepare('INSERT INTO audit_log (user_id, action, ip_address) VALUES (?, ?, ?)')
            ->execute([$userId, 'login', '1.2.3.xxx']);

        $export = DataExportService::exportUser($userId);

        $this->assertNotEmpty($export['audit_protokoll']);
        $this->assertSame('login', $export['audit_protokoll'][0]['action']);
    }
}
