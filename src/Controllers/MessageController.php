<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\Conversation;
use OpenClassbook\Models\Message;
use OpenClassbook\Models\GroupConversation;
use OpenClassbook\Models\GroupMessage;
use OpenClassbook\Models\MessageAttachment;
use OpenClassbook\Models\User;
use OpenClassbook\Services\ModuleSettings;

class MessageController
{
    private const MAX_ATTACHMENTS = 5;
    private const MAX_FILE_SIZE = 15 * 1024 * 1024; // 15 MB

    private const ALLOWED_MIME_TYPES = [
        // Bilder
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        // Dokumente
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Text
        'text/plain', 'text/csv',
        // Archiv
        'application/zip',
    ];

    private function requireModuleEnabled(): void
    {
        $role = App::currentUserRole();
        if (!ModuleSettings::canAccess('messages', $role)) {
            App::setFlash('error', 'Das Modul Nachrichten ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            exit;
        }
    }

    private function requireModuleEnabledJson(): void
    {
        $role = App::currentUserRole();
        if (!ModuleSettings::canAccess('messages', $role)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Modul deaktiviert']);
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
            'breadcrumbs'   => View::breadcrumbs([
                ['label' => 'Nachrichten'],
            ]),
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
        $messages = $this->withAttachments($messages, 'direct');

        $partner = Conversation::getPartner($conversationId, $userId);

        CsrfMiddleware::generateToken();
        View::render('messages/show', [
            'title'          => 'Chat mit ' . ($partner['username'] ?? 'Unbekannt'),
            'messages'       => $messages,
            'partner'        => $partner,
            'conversationId' => $conversationId,
            'currentUserId'  => $userId,
            'breadcrumbs'    => View::breadcrumbs([
                ['label' => 'Nachrichten', 'url' => '/messages'],
                ['label' => 'Chat mit ' . ($partner['username'] ?? 'Unbekannt')],
            ]),
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

        $uploads = $this->collectValidatedUploads();
        if ($uploads['error'] !== null) {
            App::setFlash('error', $uploads['error']);
            App::redirect('/messages/' . $conversationId);
            return;
        }
        $hasFiles = !empty($uploads['files']);

        if ($body === '' && !$hasFiles) {
            App::setFlash('error', 'Nachricht darf nicht leer sein.');
            App::redirect('/messages/' . $conversationId);
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/' . $conversationId);
            return;
        }

        $messageId = Message::create($conversationId, $userId, $body);
        $this->persistUploads($uploads['files'], 'direct', $messageId);
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
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Nachrichten', 'url' => '/messages'],
                ['label' => 'Neue Nachricht'],
            ]),
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
            App::setFlash('error', 'Bitte wählen Sie einen gültigen Empfänger.');
            App::redirect('/messages/new');
            return;
        }

        $recipient = User::findById($recipientId);
        if (!$recipient || !$recipient['active']) {
            App::setFlash('error', 'Empfänger nicht gefunden.');
            App::redirect('/messages/new');
            return;
        }

        $uploads = $this->collectValidatedUploads();
        if ($uploads['error'] !== null) {
            App::setFlash('error', $uploads['error']);
            App::redirect('/messages/new');
            return;
        }
        $hasFiles = !empty($uploads['files']);

        if ($body === '' && !$hasFiles) {
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
        $messageId = Message::create($conversation['id'], $userId, $body);
        $this->persistUploads($uploads['files'], 'direct', $messageId);
        Conversation::updateLastMessageAt($conversation['id']);

        App::redirect('/messages/' . $conversation['id']);
    }

    /**
     * Ältere 1:1-Nachrichten nachladen (JSON-Antwort)
     */
    public function loadMore(string $id): void
    {
        $this->requireModuleEnabledJson();
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
        $messages = $this->withAttachments($messages, 'direct');

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
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Nachrichten', 'url' => '/messages'],
                ['label' => 'Neue Gruppe'],
            ]),
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
            App::setFlash('error', 'Bitte mindestens eine weitere Person auswählen.');
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
            App::setFlash('error', 'Keine gültigen Mitglieder ausgewählt.');
            App::redirect('/messages/groups/new');
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/groups/new');
            return;
        }

        $uploads = $this->collectValidatedUploads();
        if ($uploads['error'] !== null) {
            App::setFlash('error', $uploads['error']);
            App::redirect('/messages/groups/new');
            return;
        }
        $hasFiles = !empty($uploads['files']);

        $group = GroupConversation::create($name, $userId, $validMemberIds);
        if ($body !== '' || $hasFiles) {
            $messageId = GroupMessage::create($group['id'], $userId, $body);
            $this->persistUploads($uploads['files'], 'group', $messageId);
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
        $messages = $this->withAttachments($messages, 'group');

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
            'breadcrumbs'   => View::breadcrumbs([
                ['label' => 'Nachrichten', 'url' => '/messages'],
                ['label' => $group['name']],
            ]),
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

        $uploads = $this->collectValidatedUploads();
        if ($uploads['error'] !== null) {
            App::setFlash('error', $uploads['error']);
            App::redirect('/messages/groups/' . $groupId);
            return;
        }
        $hasFiles = !empty($uploads['files']);

        if ($body === '' && !$hasFiles) {
            App::setFlash('error', 'Nachricht darf nicht leer sein.');
            App::redirect('/messages/groups/' . $groupId);
            return;
        }

        if (mb_strlen($body) > 5000) {
            App::setFlash('error', 'Nachricht darf maximal 5000 Zeichen lang sein.');
            App::redirect('/messages/groups/' . $groupId);
            return;
        }

        $messageId = GroupMessage::create($groupId, $userId, $body);
        $this->persistUploads($uploads['files'], 'group', $messageId);
        GroupConversation::updateLastMessageAt($groupId);

        App::redirect('/messages/groups/' . $groupId);
    }

    /**
     * Ältere Gruppen-Nachrichten nachladen (JSON-Antwort)
     */
    public function loadMoreGroup(string $id): void
    {
        $this->requireModuleEnabledJson();
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
        $messages = $this->withAttachments($messages, 'group');

        header('Content-Type: application/json');
        echo json_encode([
            'messages'      => $messages,
            'currentUserId' => $userId,
        ]);
    }

    // =========================================================================
    // Anhänge
    // =========================================================================

    /**
     * Anhang herunterladen (mit Zugriffsprüfung über die zugehörige Konversation/Gruppe).
     */
    public function downloadAttachment(string $id): void
    {
        $this->requireModuleEnabled();
        $userId = $_SESSION['user_id'];

        $attachment = MessageAttachment::findById((int) $id);
        if (!$attachment) {
            App::setFlash('error', 'Anhang nicht gefunden.');
            App::redirect('/messages');
            return;
        }

        if (!MessageAttachment::userCanAccess($attachment, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/messages');
            return;
        }

        $path = MessageAttachment::getStoragePath($attachment['stored_name']);
        if (!file_exists($path)) {
            App::setFlash('error', 'Datei nicht auf dem Server gefunden.');
            App::redirect('/messages');
            return;
        }

        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . addcslashes($attachment['original_name'], '"') . '"');
        header('Content-Length: ' . $attachment['file_size']);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-cache');
        readfile($path);
        exit;
    }

    /**
     * Hochgeladene Anhänge validieren (Anzahl, Größe, MIME-Typ).
     * Gibt ['files' => [...], 'error' => null|string] zurück. 'files' enthält
     * validierte Metadaten inkl. sicherem Speichernamen; noch nichts wird gespeichert.
     */
    private function collectValidatedUploads(): array
    {
        if (empty($_FILES['attachments']) || !is_array($_FILES['attachments']['name'] ?? null)) {
            return ['files' => [], 'error' => null];
        }

        $names = $_FILES['attachments']['name'];
        $tmp   = $_FILES['attachments']['tmp_name'];
        $errs  = $_FILES['attachments']['error'];
        $sizes = $_FILES['attachments']['size'];

        // Nur tatsächlich befüllte Datei-Felder betrachten
        $indices = [];
        foreach ($names as $i => $n) {
            if ((int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $indices[] = $i;
        }

        if (empty($indices)) {
            return ['files' => [], 'error' => null];
        }

        if (count($indices) > self::MAX_ATTACHMENTS) {
            return ['files' => [], 'error' => 'Es sind maximal ' . self::MAX_ATTACHMENTS . ' Anhänge pro Nachricht erlaubt.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $valid = [];
        foreach ($indices as $i) {
            if ((int) $errs[$i] !== UPLOAD_ERR_OK) {
                return ['files' => [], 'error' => 'Fehler beim Hochladen eines Anhangs.'];
            }
            if ((int) $sizes[$i] > self::MAX_FILE_SIZE) {
                return ['files' => [], 'error' => 'Ein Anhang ist zu groß (max. 15 MB).'];
            }
            $mimeType = $finfo->file($tmp[$i]);
            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                return ['files' => [], 'error' => 'Ein Anhang hat einen nicht erlaubten Dateityp.'];
            }

            $originalName = basename((string) $names[$i]);
            $originalName = preg_replace('/[^\w\.\-\(\) ]/', '_', $originalName);
            if ($originalName === '' || $originalName === null) {
                $originalName = 'anhang';
            }
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $storedName = bin2hex(random_bytes(16)) . ($extension ? '.' . strtolower($extension) : '');

            $valid[] = [
                'tmp_name'      => $tmp[$i],
                'original_name' => $originalName,
                'stored_name'   => $storedName,
                'mime_type'     => $mimeType,
                'file_size'     => (int) $sizes[$i],
            ];
        }

        return ['files' => $valid, 'error' => null];
    }

    /**
     * Validierte Anhänge physisch speichern und mit einer Nachricht verknüpfen.
     * $type: 'direct' (1:1) oder 'group'.
     */
    private function persistUploads(array $files, string $type, int $messageId): void
    {
        if (empty($files)) {
            return;
        }
        MessageAttachment::ensureStorageDir();
        foreach ($files as $f) {
            $dest = MessageAttachment::getStoragePath($f['stored_name']);
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                continue; // fehlgeschlagenen Anhang überspringen
            }
            MessageAttachment::create([
                'message_id'       => $type === 'group' ? null : $messageId,
                'group_message_id' => $type === 'group' ? $messageId : null,
                'original_name'    => $f['original_name'],
                'stored_name'      => $f['stored_name'],
                'mime_type'        => $f['mime_type'],
                'file_size'        => $f['file_size'],
            ]);
        }
    }

    /**
     * Nachrichtenliste um ihre Anhänge anreichern (batch, ohne N+1).
     * $type: 'direct' oder 'group'.
     */
    private function withAttachments(array $messages, string $type): array
    {
        if (empty($messages)) {
            return $messages;
        }
        $ids = array_map(static fn($m) => (int) $m['id'], $messages);
        $map = $type === 'group'
            ? MessageAttachment::findByGroupMessageIds($ids)
            : MessageAttachment::findByMessageIds($ids);

        foreach ($messages as &$m) {
            $list = $map[(int) $m['id']] ?? [];
            $m['attachments'] = array_map(static fn($a) => [
                'id'            => (int) $a['id'],
                'original_name' => $a['original_name'],
                'file_size'     => (int) $a['file_size'],
                'mime_type'     => $a['mime_type'],
            ], $list);
        }
        unset($m);

        return $messages;
    }
}
