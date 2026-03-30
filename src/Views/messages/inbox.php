<div class="page-header">
    <h1>Nachrichten</h1>
    <div class="btn-group">
        <a href="/messages/new" class="btn">Neue Nachricht</a>
        <a href="/messages/groups/new" class="btn btn-secondary">Neue Gruppe</a>
    </div>
</div>

<?php
$roleLabels = [
    'admin'        => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat'  => 'Sekretariat',
    'lehrer'       => 'Lehrer/in',
    'schueler'     => 'Schueler/in',
];
?>

<?php if (empty($items)): ?>
<div class="card">
    <p class="text-muted text-center">Noch keine Nachrichten vorhanden.</p>
</div>
<?php else: ?>
<div class="conversation-list">
    <?php foreach ($items as $item): ?>
        <?php
        $isGroup  = $item['type'] === 'group';
        $href     = $isGroup ? '/messages/groups/' . (int) $item['id'] : '/messages/' . (int) $item['id'];
        $unread   = (int) $item['unread_count'];
        $initials = mb_strtoupper(mb_substr($item['display_name'], 0, 1, 'UTF-8'), 'UTF-8');
        ?>
        <a href="<?= $href ?>" class="conversation-item<?= ($unread > 0) ? ' conversation-unread' : '' ?>">
            <div class="conversation-avatar<?= $isGroup ? ' conversation-avatar--group' : '' ?>" aria-hidden="true">
                <?php if ($isGroup): ?>
                    <span class="group-icon">&#128101;</span>
                <?php else: ?>
                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
            <div class="conversation-info">
                <div class="conversation-partner">
                    <?= htmlspecialchars($item['display_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($isGroup): ?>
                        <span class="badge badge-muted">Gruppe &bull; <?= htmlspecialchars($item['sub_label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted"><?= $roleLabels[$item['sub_label']] ?? htmlspecialchars($item['sub_label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div class="conversation-preview">
                    <?php if ($item['last_message_body']): ?>
                        <?php if ((int) $item['last_message_sender_id'] === $currentUserId): ?>
                            <span class="text-muted">Sie: </span>
                        <?php endif; ?>
                        <?= htmlspecialchars(mb_strimwidth($item['last_message_body'], 0, 80, '...'), ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        <span class="text-muted">Noch keine Nachrichten</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="conversation-meta">
                <?php if ($unread > 0): ?>
                    <span class="unread-badge"><?= $unread ?></span>
                <?php endif; ?>
                <div class="conversation-time">
                    <?php if ($item['last_message_created_at']): ?>
                        <?= date('d.m.Y H:i', strtotime($item['last_message_created_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
