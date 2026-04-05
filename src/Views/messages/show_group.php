<div class="page-header">
    <div>
        <a href="/messages" class="btn btn-sm btn-secondary mb-05">Zurueck</a>
        <h1>
            <?= htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8') ?>
            <span class="badge badge-muted" style="font-size: 0.75rem; vertical-align: middle;">Gruppe</span>
        </h1>
    </div>
    <div class="group-members-bar">
        <?php
        $maxShow = 5;
        $shown = array_slice($members, 0, $maxShow);
        $remaining = count($members) - $maxShow;
        foreach ($shown as $m): ?>
            <span class="group-member-avatar-sm" title="<?= htmlspecialchars($m['username'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($m['username'], 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endforeach; ?>
        <?php if ($remaining > 0): ?>
            <span class="text-muted" style="font-size:0.8rem;">+<?= $remaining ?></span>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-secondary" id="toggleMembersBtn" aria-expanded="false" aria-controls="groupMembersPanel">
            Alle Mitglieder (<?= count($members) ?>)
        </button>
    </div>
</div>

<div id="groupMembersPanel" class="card" style="display: none; margin-bottom: 1rem;">
    <strong>Mitglieder dieser Gruppe:</strong>
    <ul style="margin: 0.5rem 0 0 0; padding-left: 1.25rem;">
        <?php
        $roleLabels = [
            'admin'        => 'Admin',
            'schulleitung' => 'Schulleitung',
            'sekretariat'  => 'Sekretariat',
            'lehrer'       => 'Lehrer/in',
            'schueler'     => 'Schueler/in',
        ];
        foreach ($members as $m): ?>
            <li>
                <?= htmlspecialchars($m['username'], ENT_QUOTES, 'UTF-8') ?>
                <span class="badge badge-muted"><?= $roleLabels[$m['role']] ?? $m['role'] ?></span>
                <?php if ((int) $m['id'] === $currentUserId): ?>
                    <span class="text-muted">(Sie)</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card chat-card">
    <div class="chat-container" id="chatContainer"
         data-group-id="<?= (int) $groupId ?>"
         data-current-user="<?= (int) $currentUserId ?>"
         data-message-count="<?= count($messages) ?>">

        <?php if (count($messages) >= 50): ?>
            <div class="text-center mb-1">
                <button type="button" class="btn btn-sm btn-secondary" id="loadMoreBtn" data-offset="50">
                    Aeltere Nachrichten laden
                </button>
            </div>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <p class="text-muted text-center" id="emptyChat">Noch keine Nachrichten. Schreiben Sie die erste!</p>
        <?php endif; ?>

        <?php foreach ($messages as $m): ?>
            <div class="chat-bubble <?= ((int) $m['sender_id'] === $currentUserId) ? 'chat-bubble--mine' : 'chat-bubble--theirs' ?>">
                <?php if ((int) $m['sender_id'] !== $currentUserId): ?>
                    <div class="chat-bubble-sender"><?= htmlspecialchars($m['sender_username'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div class="chat-bubble-body"><?= nl2br(htmlspecialchars($m['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                <div class="chat-bubble-meta">
                    <?= date('d.m. H:i', strtotime($m['created_at'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/messages/groups/<?= (int) $groupId ?>" class="chat-input-form" id="chatForm">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="chat-input-wrapper">
            <textarea name="body" id="chatInput" class="form-control" rows="2"
                      placeholder="Nachricht an Gruppe schreiben..." required maxlength="5000"></textarea>
            <button type="submit" class="btn">Senden</button>
        </div>
    </form>
</div>

<script>
(function () {
    var toggleBtn = document.getElementById('toggleMembersBtn');
    var panel = document.getElementById('groupMembersPanel');
    if (toggleBtn && panel) {
        toggleBtn.addEventListener('click', function () {
            var isHidden = panel.style.display === 'none';
            panel.style.display = isHidden ? '' : 'none';
            toggleBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    }
})();
</script>
