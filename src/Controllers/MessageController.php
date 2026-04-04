<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\Conversation;
use OpenClassbook\Models\Message;
use OpenClassbook\Models\GroupConversation;
use OpenClassbook\Models\GroupMessage;
use OpenClassbook\Models\User;
use OpenClassbook\Services\ModuleSettings;

class MessageController
{
    private function requireModuleEnabled(): void
    {
        $role = App::currentUserRole();
        if (!ModuleSettings::canAccess('messages', $role)) {
            App::setFlash('error', 'Das Modul Nachrichten ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            exit;
        }
    }

    /**
     * Inbox: alle Konversationen (1:1 und Gruppen) des eingeloggten Nutzers
     */
    public function inbox(): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];

        $conversations = Conversation::findByUserId($userId);
        $groups = GroupConversation::findByUserId($userId);

        // Einheitliche Liste zusammenführen und nach letzter Aktivität sortieren
        $items = [];

        foreach ($conversations as $c) {
            $items[] = [
                'type'                    => 'direct',
                'id'                      => $c['id'],
                'display_name'            => $c['partner_username'],
                'sub_label'               => $c['partner_role'],
                'last_message_body'       => $c['last_message_body'],
                'last_message_sender_id'  => $c['last_message_sender_id'],
                'last_message_created_at' => $c['last_message_created_at'],
                'unread_count'            => (int) $c['unread_count'],
                'sort_ts'                 => $c['last_message_at'] ?? $c['created_at'],
            ];
        }

        foreach ($groups as $g) {
            $items[] = [
                'type'                    => 'group',
                'id'                      => $g['id'],
                'display_name'            => $g['name'],
                'sub_label'               => (int) $g['member_count'] . ' Mitglieder',
                'last_message_body'       => $g['last_message_body'],
                'last_message_sender_id'  => $g['last_message_sender_id'],
                'last_message_created_at' => $g['last_message_created_at'],
                'unread_count'            => (int) $g['unread_count'],
                'sort_ts'                 => $g['last_message_at'] ?? $g['created_at'],
            ];
        }

        usort($items, function ($a, $b) {
            return strcmp($b['sort_ts'] ?? '', $a['sort_ts'] ?? '');
        });

        View::render('messages/inbox', [
            'title'         => 'Nachrichten',
            'items'         => $items,
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Chat-Ansicht einer 1:1-Konversation
     */
    public function show(string $id): void
    {
        $this->requireModuleEnabled();
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
            'title'          => 'Chat mit ' . ($partner['username'] ?? 'Unbekannt'),
            'messages'       => $messages,
            'partner'        => $partner,
            'conversationId' => $conversationId,
            'currentUserId'  => $userId,
        ]);
    }

    /**
     * Nachricht in 1:1-Konversation senden (POST)
     */
    public function send(string $id): void
    {
        $this->requireModuleEnabled();
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
     * Formular: neuen 1:1-Chat starten
     */
    public function newConversation(): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];
        $users = User::findAll(['active' => 1]);
        $users = array_filter($users, fn($u) => (int) $u['id'] !== $userId);

        CsrfMiddleware::generateToken();
        View::render('messages/new', [
            'title' => 'Neue Nachricht',
            'users' => array_values($users),
        ]);
    }

    /**
     * Neuen 1:1-Chat starten (POST)
     */
    public function createConversation(): void
    {
        $this->requireModuleEnabled();
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
     * Aeltere 1:1-Nachrichten nachladen (JSON-Antwort)
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
            'messages'      => $messages,
            'currentUserId' => $userId,
        ]);
    }

    // =========================================================================
    // Gruppen-Nachrichten
    // =========================================================================

    /**
     * Formular: neue Gruppe erstellen
     */
    public function newGroup(): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];
        $users = User::findAll(['active' => 1]);
        $users = array_filter($users, fn($u) => (int) $u['id'] !== $userId);

        CsrfMiddleware::generateToken();
        View::render('messages/new_group', [
            'title' => 'Neue Gruppe erstellen',
            'users' => array_values($users),
        ]);
    }

    /**
     * Neue Gruppe erstellen (POST)
     */
    public function createGroup(): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];
        $name = trim($_POST['group_name'] ?? '');
        $memberIds = $_POST['member_ids'] ?? [];
        $body = trim($_POST['body'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            App::setFlash('error', 'Gruppenname muss zwischen 1 und 100 Zeichen lang sein.');
            App::redirect('/messages/groups/new');
            return;
        }

        if (!is_array($memberIds) || count($memberIds) < 1) {
            App::setFlash('error', 'Bitte mindestens eine weitere Person auswaehlen.');
            App::redirect('/messages/groups/new');
            return;
        }

        // Mitglieder-IDs validieren (nur aktive Nutzer, nicht der Ersteller selbst)
        $validMemberIds = [];
        foreach ($memberIds as $mid) {
            $mid = (int) $mid;
            if ($mid <= 0 || $mid === $userId) {
                continue;
            }
            $member = User::findById($mid);
            if ($member && $member['active']) {
                $validMemberIds[] = $mid;
            }
        }

        if (count($validMemberIds) < 1) {
            App::setFlash('error', 'Keine gueltigen Mitglieder ausgewaehlt.');
            App::redirect('/messages/groups/new');
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/groups/new');
            return;
        }

        $group = GroupConversation::create($name, $userId, $validMemberIds);
        if ($body !== '') {
            GroupMessage::create($group['id'], $userId, $body);
            GroupConversation::updateLastMessageAt($group['id']);
        }

        App::redirect('/messages/groups/' . $group['id']);
    }

    /**
     * Gruppen-Chat-Ansicht
     */
    public function showGroup(string $id): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];
        $groupId = (int) $id;

        if (!GroupConversation::hasAccess($groupId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/messages');
            return;
        }

        GroupMessage::markAsRead($groupId, $userId);

        $messages = GroupMessage::findByGroup($groupId, 50, 0);
        $messages = array_reverse($messages);

        $group = GroupConversation::findById($groupId);
        $members = GroupConversation::getMembers($groupId);

        CsrfMiddleware::generateToken();
        View::render('messages/show_group', [
            'title'         => $group['name'],
            'messages'      => $messages,
            'group'         => $group,
            'members'       => $members,
            'groupId'       => $groupId,
            'currentUserId' => $userId,
        ]);
    }

    /**
     * Nachricht in Gruppe senden (POST)
     */
    public function sendGroup(string $id): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];
        $groupId = (int) $id;

        if (!GroupConversation::hasAccess($groupId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/messages');
            return;
        }

        $body = trim($_POST['body'] ?? '');
        if ($body === '') {
            App::setFlash('error', 'Nachricht darf nicht leer sein.');
            App::redirect('/messages/groups/' . $groupId);
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/groups/' . $groupId);
            return;
        }

        GroupMessage::create($groupId, $userId, $body);
        GroupConversation::updateLastMessageAt($groupId);

        App::redirect('/messages/groups/' . $groupId);
    }

    /**
     * Aeltere Gruppen-Nachrichten nachladen (JSON-Antwort)
     */
    public function loadMoreGroup(string $id): void
    {
        $userId = $_SESSION['user_id'];
        $groupId = (int) $id;

        if (!GroupConversation::hasAccess($groupId, $userId)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Zugriff verweigert']);
            return;
        }

        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $messages = GroupMessage::findByGroup($groupId, 50, $offset);

        header('Content-Type: application/json');
        echo json_encode([
            'messages'      => $messages,
            'currentUserId' => $userId,
        ]);
    }
}
