<?php
/** @var array $templates */
/** @var int $userId */
/** @var string $role */

use OpenClassbook\Models\ContentTemplate;

// Nach Kategorie gruppieren (leere Kategorie zuletzt)
$grouped = [];
foreach ($templates as $t) {
    $key = ($t['category'] ?? '') !== '' ? $t['category'] : '__none__';
    $grouped[$key][] = $t;
}
?>
<div class="page-header">
    <h1>Unterrichtsinhalte</h1>
    <a href="/content-templates/create" class="btn">Neue Vorlage</a>
</div>

<p class="text-muted mb-1">
    Vorgefertigte Themen und Notizen, die du beim Anlegen eines Klassenbucheintrags mit einem Klick übernehmen kannst.
</p>

<?php if (empty($templates)): ?>
<div class="card mt-1">
    <p class="text-muted text-center">Noch keine Vorlagen vorhanden. Lege deine erste Vorlage an.</p>
</div>
<?php else: ?>
    <?php foreach ($grouped as $categoryKey => $items): ?>
    <div class="card mt-1">
        <div class="card-header">
            <h2><?= $categoryKey === '__none__' ? 'Ohne Kategorie' : htmlspecialchars($categoryKey, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
        <div class="table-responsive">
            <table aria-label="Vorlagen <?= $categoryKey === '__none__' ? 'ohne Kategorie' : htmlspecialchars($categoryKey, ENT_QUOTES, 'UTF-8') ?>">
                <thead>
                    <tr>
                        <th scope="col">Thema</th>
                        <th scope="col">Notizen</th>
                        <th scope="col">Sichtbarkeit</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $t): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($t['topic'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td>
                            <?php if (!empty($t['notes'])): ?>
                                <span class="text-muted"><?= htmlspecialchars(mb_strimwidth($t['notes'], 0, 100, '…'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['owner_user_id'] === null): ?>
                                <span class="badge badge-published">Geteilt</span>
                            <?php else: ?>
                                <span class="badge badge-draft">Persönlich</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <?php if (ContentTemplate::canManage($t, $userId, $role)): ?>
                            <div class="btn-group">
                                <a href="/content-templates/<?= (int) $t['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                                <form method="post" action="/content-templates/<?= (int) $t['id'] ?>/delete" class="d-inline">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Vorlage &quot;<?= htmlspecialchars($t['topic'], ENT_QUOTES, 'UTF-8') ?>&quot; wirklich löschen?">
                                        Löschen
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
