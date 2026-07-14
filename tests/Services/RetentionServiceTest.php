<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\Models\Setting;
use OpenClassbook\Services\RetentionService;
use OpenClassbook\Tests\DatabaseTestCase;

class RetentionServiceTest extends DatabaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Nachrichtentabellen ergaenzen (nicht Teil des Basis-Schemas).
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

    private function insert(string $table, array $data): void
    {
        $cols = implode(', ', array_keys($data));
        $ph = implode(', ', array_fill(0, count($data), '?'));
        self::$pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$ph})")
            ->execute(array_values($data));
    }

    private function rowCount(string $table): int
    {
        return (int) self::$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    public function testPurgeDeletesOnlyRowsOlderThanConfiguredRetention(): void
    {
        $old = date('Y-m-d H:i:s', strtotime('-800 days'));
        $recent = date('Y-m-d H:i:s', strtotime('-10 days'));

        $this->insert('messages', ['conversation_id' => 1, 'sender_id' => 1, 'body' => 'alt', 'created_at' => $old]);
        $this->insert('messages', ['conversation_id' => 1, 'sender_id' => 1, 'body' => 'neu', 'created_at' => $recent]);
        $this->insert('group_messages', ['group_id' => 1, 'sender_id' => 1, 'body' => 'alt', 'created_at' => $old]);
        $this->insert('group_messages', ['group_id' => 1, 'sender_id' => 1, 'body' => 'neu', 'created_at' => $recent]);

        Setting::set('retention_messages_days', '730');
        // Andere Kategorien fuer diesen Test deaktivieren.
        Setting::set('retention_audit_days', '0');
        Setting::set('retention_login_attempts_days', '0');

        $result = RetentionService::purge();

        $this->assertSame(1, $result['messages']);
        $this->assertSame(1, $result['group_messages']);
        $this->assertSame(1, $this->rowCount('messages'), 'Die aktuelle Nachricht muss erhalten bleiben.');
        $this->assertSame(1, $this->rowCount('group_messages'));
    }

    public function testRetentionZeroDisablesDeletion(): void
    {
        $old = date('Y-m-d H:i:s', strtotime('-800 days'));
        $this->insert('messages', ['conversation_id' => 1, 'sender_id' => 1, 'body' => 'alt', 'created_at' => $old]);

        Setting::set('retention_messages_days', '0');
        Setting::set('retention_audit_days', '0');
        Setting::set('retention_login_attempts_days', '0');

        $result = RetentionService::purge();

        $this->assertSame(0, $result['messages']);
        $this->assertSame(1, $this->rowCount('messages'), 'Bei Frist 0 darf nichts geloescht werden.');
    }

    public function testPurgeCleansAuditLogAndLoginAttempts(): void
    {
        $old = date('Y-m-d H:i:s', strtotime('-200 days'));
        $recent = date('Y-m-d H:i:s', strtotime('-5 days'));

        $this->insert('audit_log', ['action' => 'login', 'created_at' => $old]);
        $this->insert('audit_log', ['action' => 'login', 'created_at' => $recent]);
        $this->insert('login_attempts', ['username' => 'a', 'ip_address' => '1.2.3.xxx', 'successful' => 0, 'attempted_at' => $old]);
        $this->insert('login_attempts', ['username' => 'b', 'ip_address' => '1.2.3.xxx', 'successful' => 0, 'attempted_at' => $recent]);

        Setting::set('retention_messages_days', '0');
        Setting::set('retention_audit_days', '90');
        Setting::set('retention_login_attempts_days', '30');

        $result = RetentionService::purge();

        $this->assertSame(1, $result['audit_log']);
        $this->assertSame(1, $result['login_attempts']);
        // Nach purge() bleibt der aktuelle Audit-Eintrag plus der von purge()
        // selbst geschriebene "retention_purge"-Eintrag uebrig.
        $this->assertSame(2, $this->rowCount('audit_log'));
        $this->assertSame(1, $this->rowCount('login_attempts'));
    }

    public function testExpiredResetTokensAreCleared(): void
    {
        $userId = $this->createTestUser();
        self::$pdo->prepare(
            'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?'
        )->execute(['tok', date('Y-m-d H:i:s', strtotime('-1 hour')), $userId]);

        Setting::set('retention_messages_days', '0');
        Setting::set('retention_audit_days', '0');
        Setting::set('retention_login_attempts_days', '0');

        $result = RetentionService::purge();

        $this->assertSame(1, $result['reset_tokens']);
        $row = self::$pdo->query("SELECT password_reset_token FROM users WHERE id = {$userId}")->fetch();
        $this->assertNull($row['password_reset_token']);
    }
}
