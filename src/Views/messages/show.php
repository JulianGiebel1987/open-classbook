<div class="page-header">
    <div>
        <a href="/messages" class="btn btn-sm btn-secondary mb-05">Zurück</a>
        <h1>Chat mit <?= htmlspecialchars($partner['username'] ?? 'Unbekannt', ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
</div>

<div class="card chat-card">
    <div class="chat-container" id="chatContainer" data-conversation-id="<?= (int) $conversationId ?>" data-current-user="<?= (int) $currentUserId ?>" data-message-count="<?= count($messages) ?>">
        <?php if (count($messages) >= 50): ?>
            <div class="text-center mb-1">
                <button type="button" class="btn btn-sm btn-secondary" id="loadMoreBtn" data-offset="50">Ältere Nachrichten laden</button>
            </div>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <p class="text-muted text-center" id="emptyChat">Noch keine Nachrichten. Schreiben Sie die erste!</p>
        <?php endif; ?>

        <?php foreach ($messages as $m): ?>
            <div class="chat-bubble <?= ((int) $m['sender_id'] === $currentUserId) ? 'chat-bubble--mine' : 'chat-bubble--theirs' ?>">
                <div class="chat-bubble-body"><?= nl2br(htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                <div class="chat-bubble-meta">
                    <?= date('d.m. H:i', strtotime($m['created_at'])) ?>
                    <?php if ((int) $m['sender_id'] === $currentUserId && $m['read_at']): ?>
                        <span class="chat-read-indicator" title="Gelesen am <?= date('d.m.Y H:i', strtotime($m['read_at'])) ?>">&#10003;&#10003;</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/messages/<?= (int) $conversationId ?>" class="chat-input-form" id="chatForm">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="chat-input-wrapper">
            <textarea name="body" id="chatInput" class="form-control" rows="2" placeholder="Nachricht schreiben..." required maxlength="5000"></textarea>
            <button type="submit" class="btn">Senden</button>
        </div>
    </form>
</div>
