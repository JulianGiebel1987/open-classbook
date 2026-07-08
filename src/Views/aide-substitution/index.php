<div class="page-header">
    <h1>Schulbegleiter:innen-Vertretung</h1>
    <div class="page-header-actions">
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
