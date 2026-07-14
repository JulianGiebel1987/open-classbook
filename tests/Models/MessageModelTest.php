<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\App;
use OpenClassbook\Models\Conversation;
use OpenClassbook\Models\Message;
use OpenClassbook\Services\EncryptionService;
use OpenClassbook\Tests\DatabaseTestCase;

/**
 * Prueft die Ende-zu-Ende-Integration der Verschluesselung ruhender Daten
 * im Message-/Conversation-Modell: verschluesselt beim Schreiben,
 * entschluesselt beim Lesen, sowohl im Verlauf als auch in der Inbox-Vorschau.
 */
class MessageModelTest extends DatabaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$pdo->exec('
            CREATE TABLE conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_one_id INTEGER NOT NULL,
                user_two_id INTEGER NOT NULL,
                last_message_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
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
    }

    protected function setUp(): void
    {
        parent::setUp();
        $config = App::config();
        $config['security']['app_encryption_key'] = bin2hex(random_bytes(32));
        App::setConfig($config);
    }

    public function testBodyIsStoredEncryptedButReadBackAsPlaintext(): void
    {
        $sender = $this->createTestUser(['username' => 'a', 'email' => 'a@example.com']);
        self::$pdo->prepare('INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)')
            ->execute([$sender, 999]);
        $conversationId = (int) self::$pdo->lastInsertId();

        $plain = 'Vertrauliche Testnachricht';
        Message::create($conversationId, $sender, $plain);

        // Rohwert in der DB ist verschluesselt ...
        $raw = self::$pdo->query('SELECT body FROM messages LIMIT 1')->fetchColumn();
        $this->assertTrue(EncryptionService::isEncrypted($raw));
        $this->assertStringNotContainsString($plain, $raw);

        // ... wird beim Lesen aber wieder als Klartext geliefert.
        $rows = Message::findByConversation($conversationId);
        $this->assertSame($plain, $rows[0]['body']);
    }

    public function testInboxPreviewDecryptsLastMessage(): void
    {
        $userOne = $this->createTestUser(['username' => 'u1', 'email' => 'u1@example.com']);
        $userTwo = $this->createTestUser(['username' => 'u2', 'email' => 'u2@example.com']);
        self::$pdo->prepare('INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)')
            ->execute([$userOne, $userTwo]);
        $conversationId = (int) self::$pdo->lastInsertId();

        Message::create($conversationId, $userTwo, 'Letzte Nachricht im Verlauf');

        $conversations = Conversation::findByUserId($userOne);
        $this->assertSame('Letzte Nachricht im Verlauf', $conversations[0]['last_message_body']);
    }
}
