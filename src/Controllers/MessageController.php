<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\Conversation;
use OpenClassbook\Models\Message;
use OpenClassbook\Models\User;

class MessageController
{
    /**
     * Inbox: alle Konversationen des eingeloggten Nutzers
     */
    public function inbox(): void
    {
        $userId = $_SESSION['user_id'];
        $conversations = Conversation::findByUserId($userId);

        View::render('messages/inbox', [
            'title' => 'Nachrichten',
            'conversations' => $conversations,
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Chat-Ansicht einer Konversation
     */
    public function show(string $id): void
    {
        $userId = $_SESSION['user_id'];
        $conversationId = (int) $id;

        if (!Conversation::hasAccess($conversationId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/messages');
            return;
        }

        Message::markAsRead($conversationId, $userId);

        $messages = Message::findByConversation($conversationId, 50, 0);
        $messages = array_reverse($messages);

        $partner = Conversation::getPartner($conversationId, $userId);

        CsrfMiddleware::generateToken();
        View::render('messages/show', [
            'title' => 'Chat mit ' . ($partner['username'] ?? 'Unbekannt'),
            'messages' => $messages,
            'partner' => $partner,
            'conversationId' => $conversationId,
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Nachricht senden (POST)
     */
    public function send(string $id): void
    {
        $userId = $_SESSION['user_id'];
        $conversationId = (int) $id;

        if (!Conversation::hasAccess($conversationId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/messages');
            return;
        }

        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            App::setFlash('error', 'Nachricht darf nicht leer sein.');
            App::redirect('/messages/' . $conversationId);
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/' . $conversationId);
            return;
        }

        Message::create($conversationId, $userId, $body);
        Conversation::updateLastMessageAt($conversationId);

        App::redirect('/messages/' . $conversationId);
    }

    /**
     * Formular: neuen Chat starten
     */
    public function newConversation(): void
    {
        $userId = $_SESSION['user_id'];
        $users = User::findAll(['active' => 1]);

        // Eigenen Nutzer herausfiltern
        $users = array_filter($users, fn($u) => (int) $u['id'] !== $userId);

        CsrfMiddleware::generateToken();
        View::render('messages/new', [
            'title' => 'Neue Nachricht',
            'users' => array_values($users),
        ]);
    }

    /**
     * Neuen Chat starten (POST)
     */
    public function createConversation(): void
    {
        $userId = $_SESSION['user_id'];
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        if ($recipientId === 0 || $recipientId === $userId) {
            App::setFlash('error', 'Bitte waehlen Sie einen gueltigen Empfaenger.');
            App::redirect('/messages/new');
            return;
        }

        $recipient = User::findById($recipientId);
        if (!$recipient || !$recipient['active']) {
            App::setFlash('error', 'Empfaenger nicht gefunden.');
            App::redirect('/messages/new');
            return;
        }

        if ($body === '') {
            App::setFlash('error', 'Nachricht darf nicht leer sein.');
            App::redirect('/messages/new');
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/new');
            return;
        }

        $conversation = Conversation::findOrCreate($userId, $recipientId);
        Message::create($conversation['id'], $userId, $body);
        Conversation::updateLastMessageAt($conversation['id']);

        App::redirect('/messages/' . $conversation['id']);
    }

    /**
     * Aeltere Nachrichten nachladen (JSON-Antwort)
     */
    public function loadMore(string $id): void
    {
        $userId = $_SESSION['user_id'];
        $conversationId = (int) $id;

        if (!Conversation::hasAccess($conversationId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Zugriff verweigert']);
            return;
        }

        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $messages = Message::findByConversation($conversationId, 50, $offset);

        header('Content-Type: application/json');
        echo json_encode([
            'messages' => $messages,
            'currentUserId' => $userId,
        ]);
    }
}
