<div class="page-header">
    <h1>Schüler-Fehlzeiten</h1>
    <a href="/absences/students/create" class="btn">Fehlzeit eintragen</a>
</div>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
    </div>
    <form method="get" action="/absences/students" class="filter-form">
        <div class="form-group">
            <label for="class_id">Klasse</label>
            <select name="class_id" id="class_id" class="form-control">
                <option value="">Alle</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="excused">Status</label>
            <select name="excused" id="excused" class="form-control">
                <option value="">Alle</option>
                <option value="ja" <?= ($filters['excused'] ?? '') === 'ja' ? 'selected' : '' ?>>Entschuldigt</option>
                <option value="nein" <?= ($filters['excused'] ?? '') === 'nein' ? 'selected' : '' ?>>Unentschuldigt</option>
                <option value="offen" <?= ($filters['excused'] ?? '') === 'offen' ? 'selected' : '' ?>>Offen</option>
            </select>
        </div>
        <div class="form-group">
            <label for="date_from">Von</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">Bis</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Schüler-Fehlzeiten">
            <thead>
                <tr>
                    <th scope="col">Schüler:in</th>
                    <th scope="col">Klasse</th>
                    <th scope="col">Von</th>
                    <th scope="col">Bis</th>
                    <th scope="col">Status</th>
                    <?php if ($canViewReason ?? false): ?><th scope="col">Grund</th><?php endif; ?>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absences)): ?>
                    <tr><td colspan="<?= ($canViewReason ?? false) ? 7 : 6 ?>" class="text-center">Keine Fehlzeiten gefunden.</td></tr>
                <?php endif; ?>
                <?php foreach ($absences as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['lastname'] . ', ' . $a['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['class_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('d.m.Y', strtotime($a['date_from'])) ?></td>
                    <td><?= date('d.m.Y', strtotime($a['date_to'])) ?></td>
                    <td>
                        <?php if ($a['excused'] === 'ja'): ?>
                            <span class="badge badge-success">Entschuldigt</span>
                        <?php elseif ($a['excused'] === 'nein'): ?>
                            <span class="badge badge-danger">Unentschuldigt</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Offen</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($canViewReason ?? false): ?>
                    <td><?= htmlspecialchars($a['reason'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endif; ?>
                    <td>
                        <div class="btn-group">
                            <a href="/absences/students/<?= $a['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <form method="post" action="/absences/students/<?= $a['id'] ?>/delete" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Fehlzeit wirklich löschen?">Löschen</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($filters['class_id'])): ?>
<div class="mt-1">
    <a href="/classbook/<?= (int) $filters['class_id'] ?>" class="btn btn-secondary">Zurück zum Klassenbuch</a>
</div>
<?php endif; ?>
