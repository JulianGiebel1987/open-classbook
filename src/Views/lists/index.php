<div class="page-header">
    <h1>Listen</h1>
    <a href="/lists/create" class="btn">Neue Liste</a>
</div>

<?php
$visibilityLabels = ['private' => 'Privat', 'global' => 'Global', 'shared' => 'Freigegeben'];
$visibilityBadges = ['private' => 'badge-muted', 'global' => 'badge-success', 'shared' => 'badge-info'];
?>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Listen">
            <thead>
                <tr>
                    <th scope="col">Titel</th>
                    <th scope="col">Beschreibung</th>
                    <th scope="col">Sichtbarkeit</th>
                    <th scope="col">Klasse</th>
                    <th scope="col">Ersteller:in</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lists)): ?>
                    <tr><td colspan="6" class="text-center">Keine Listen vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ($lists as $l): ?>
                <tr>
                    <td><a href="/lists/<?= (int) $l['id'] ?>"><?= htmlspecialchars($l['title'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td class="text-muted"><?= htmlspecialchars(mb_strimwidth($l['description'] ?? '', 0, 60, '...'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= $visibilityBadges[$l['visibility']] ?? '' ?>"><?= $visibilityLabels[$l['visibility']] ?? $l['visibility'] ?></span></td>
                    <td><?= htmlspecialchars($l['class_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($l['owner_username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="/lists/<?= (int) $l['id'] ?>" class="btn btn-sm btn-secondary">Oeffnen</a>
                            <?php if ((int) $l['owner_id'] === $currentUserId): ?>
                                <form method="post" action="/lists/<?= (int) $l['id'] ?>/delete" class="d-inline">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Liste wirklich löschen?">Löschen</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
