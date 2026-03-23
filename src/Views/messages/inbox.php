<div class="page-header">
    <h1>Nachrichten</h1>
    <a href="/messages/new" class="btn">Neue Nachricht</a>
</div>

<?php
$roleLabels = [
    'admin' => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat' => 'Sekretariat',
    'lehrer' => 'Lehrer/in',
    'schueler' => 'Schueler/in',
];
?>

<?php if (empty($conversations)): ?>
<div class="card">
    <p class="text-muted text-center">Noch keine Nachrichten vorhanden.</p>
</div>
<?php else: ?>
<div class="conversation-list">
    <?php foreach ($conversations as $c): ?>
        <a href="/messages/<?= (int) $c['id'] ?>" class="conversation-item<?= ((int) $c['unread_count'] > 0) ? ' conversation-unread' : '' ?>">
            <div class="conversation-info">
                <div class="conversation-partner">
                    <?= htmlspecialchars($c['partner_username'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge badge-muted"><?= $roleLabels[$c['partner_role']] ?? $c['partner_role'] ?></span>
                    <?php if ((int) $c['unread_count'] > 0): ?>
                        <span class="unread-badge"><?= (int) $c['unread_count'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="conversation-preview">
                    <?php if ($c['last_message_body']): ?>
                        <?php if ((int) $c['last_message_sender_id'] === $currentUserId): ?>
                            <span class="text-muted">Sie: </span>
                        <?php endif; ?>
                        <?= htmlspecialchars(mb_strimwidth($c['last_message_body'], 0, 80, '...'), ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <span class="text-muted">Noch keine Nachrichten</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="conversation-time">
                <?php if ($c['last_message_created_at']): ?>
                    <?= date('d.m.Y H:i', strtotime($c['last_message_created_at'])) ?>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
