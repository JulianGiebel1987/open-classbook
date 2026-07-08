<div class="page-header">
    <div>
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
                <?php if ($m['body'] !== ''): ?>
                    <div class="chat-bubble-body"><?= nl2br(htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                <?php endif; ?>
                <?php if (!empty($m['attachments'])): ?>
                    <div class="chat-attachments">
                        <?php foreach ($m['attachments'] as $att): ?>
                            <a class="chat-attachment" href="/messages/attachments/<?= (int) $att['id'] ?>/download">
                                <span class="chat-attachment-icon" aria-hidden="true">📎</span>
                                <span class="chat-attachment-name"><?= htmlspecialchars($att['original_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="chat-attachment-size"><?= htmlspecialchars(\OpenClassbook\Models\FileEntry::formatSize((int) $att['file_size']), ENT_QUOTES, 'UTF-8') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="chat-bubble-meta">
                    <?= date('d.m. H:i', strtotime($m['created_at'])) ?>
                    <?php if ((int) $m['sender_id'] === $currentUserId && $m['read_at']): ?>
                        <span class="chat-read-indicator" title="Gelesen am <?= date('d.m.Y H:i', strtotime($m['read_at'])) ?>">&#10003;&#10003;</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/messages/<?= (int) $conversationId ?>" class="chat-input-form" id="chatForm" enctype="multipart/form-data">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="chat-input-wrapper">
            <textarea name="body" id="chatInput" class="form-control" rows="2" placeholder="Nachricht schreiben..." maxlength="5000"></textarea>
            <button type="submit" class="btn">Senden</button>
        </div>
        <div class="chat-attachment-bar">
            <label for="chatAttachments" class="btn btn-sm btn-secondary">📎 Anhang</label>
            <input type="file" id="chatAttachments" name="attachments[]" multiple class="chat-file-input"
                   aria-label="Dateien anhängen">
            <span class="chat-attachment-selected text-muted" id="attachmentSelected"></span>
        </div>
    </form>
</div>
