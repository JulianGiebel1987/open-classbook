<div class="page-header">
    <h1>Schulbegleiter:innen-Vertretung</h1>
    <div class="page-header-actions">
        <a href="/aide-substitution/pdf?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="btn btn-secondary">PDF-Export</a>
        <a href="/aide-substitution/plan?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="btn">Vertretung planen</a>
    </div>
</div>

<div class="card">
    <form method="get" action="/aide-substitution" class="filter-form">
        <div class="form-group">
            <label for="date_from">Von</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">Bis</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<?php
    $total = (int) ($publishStatus['total'] ?? 0);
    $published = (int) ($publishStatus['published'] ?? 0);
    $allPublished = $total > 0 && $published >= $total;
?>
<div class="card mt-1">
    <div class="card-header">
        <h2>Veröffentlichung</h2>
    </div>
    <p>
        <?php if ($total === 0): ?>
            <span class="text-muted">Keine Vertretungen im gewählten Zeitraum.</span>
        <?php elseif ($published === 0): ?>
            <span class="badge badge-warning">Entwurf</span>
            Noch nicht veröffentlicht – die eingeteilten Begleitungen sehen die Vertretung noch nicht.
        <?php elseif ($allPublished): ?>
            <span class="badge badge-success">Veröffentlicht</span>
            Alle <?= $total ?> Vertretung(en) im Zeitraum sind veröffentlicht.
        <?php else: ?>
            <span class="badge badge-info">Teilweise veröffentlicht</span>
            <?= $published ?> von <?= $total ?> Vertretung(en) im Zeitraum veröffentlicht.
        <?php endif; ?>
    </p>
    <p class="text-muted">
        Beim Veröffentlichen werden alle Vertretungen im gewählten Zeitraum für die eingeteilten
        Schulbegleiter:innen unter „Meine Vertretungen“ sichtbar.
    </p>
    <div class="btn-group">
        <form method="post" action="/aide-substitution/publish" class="d-inline">
            <?= \OpenClassbook\View::csrfField() ?>
            <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn" <?= $total === 0 ? 'disabled' : '' ?>>Veröffentlichen</button>
        </form>
        <?php if ($published > 0): ?>
        <form method="post" action="/aide-substitution/unpublish" class="d-inline">
            <?= \OpenClassbook\View::csrfField() ?>
            <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-secondary" data-confirm="Veröffentlichung für diesen Zeitraum zurückziehen?">Zurückziehen</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Vertretungsbedarfe">
            <thead>
                <tr>
                    <th scope="col">Priorität</th>
                    <th scope="col">Zeitraum</th>
                    <th scope="col">Kind</th>
                    <th scope="col">Abwesende Begleitung</th>
                    <th scope="col">Ersatz</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($needs)): ?>
                    <tr><td colspan="7" class="text-center">Keine Vertretungsbedarfe im gewählten Zeitraum.</td></tr>
                <?php endif; ?>
                <?php foreach ($needs as $n): ?>
                <tr>
                    <td>
                        <span class="badge prio-<?= (int) $n['priority'] ?>">
                            <?= (int) $n['priority'] ?> – <?= htmlspecialchars($priorities[(int) $n['priority']] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y', strtotime($n['date_from'])) ?> – <?= date('d.m.Y', strtotime($n['date_to'])) ?></td>
                    <td><?= htmlspecialchars($n['student_lastname'] . ', ' . $n['student_firstname'] . ' (' . $n['class_name'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($n['absent_lastname'] . ', ' . $n['absent_firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (!empty($n['substitute_aide_id'])): ?>
                            <?= htmlspecialchars($n['substitute_lastname'] . ', ' . $n['substitute_firstname'], ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>
                            <span class="text-muted">Offen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($n['status'] === 'erledigt'): ?>
                            <span class="badge badge-success">Erledigt</span>
                        <?php elseif ($n['status'] === 'geplant'): ?>
                            <span class="badge badge-info">Geplant</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Offen</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="/aide-substitution/<?= $n['id'] ?>/delete" class="d-inline">
                            <?= \OpenClassbook\View::csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Vertretungsbedarf löschen?">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
