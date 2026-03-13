<h1>Lehrer-Abwesenheiten</h1>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
        <a href="/absences/teachers/create" class="btn">Abwesenheit eintragen</a>
    </div>
    <form method="get" action="/absences/teachers" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="type">Typ</label>
            <select name="type" id="type" class="form-control">
                <option value="">Alle</option>
                <option value="krank" <?= ($filters['type'] ?? '') === 'krank' ? 'selected' : '' ?>>Krank</option>
                <option value="fortbildung" <?= ($filters['type'] ?? '') === 'fortbildung' ? 'selected' : '' ?>>Fortbildung</option>
                <option value="sonstiges" <?= ($filters['type'] ?? '') === 'sonstiges' ? 'selected' : '' ?>>Sonstiges</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="date_from">Von</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="date_to">Bis</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Lehrkraft</th>
                    <th>Kuerzel</th>
                    <th>Von</th>
                    <th>Bis</th>
                    <th>Typ</th>
                    <th>Grund</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absences)): ?>
                    <tr><td colspan="7" class="text-center">Keine Abwesenheiten gefunden.</td></tr>
                <?php endif; ?>
                <?php foreach ($absences as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['lastname'] . ', ' . $a['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['abbreviation'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('d.m.Y', strtotime($a['date_from'])) ?></td>
                    <td><?= date('d.m.Y', strtotime($a['date_to'])) ?></td>
                    <td>
                        <?php if ($a['type'] === 'krank'): ?>
                            <span class="badge badge-danger">Krank</span>
                        <?php elseif ($a['type'] === 'fortbildung'): ?>
                            <span class="badge badge-info">Fortbildung</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Sonstiges</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['reason'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="white-space:nowrap;">
                        <a href="/absences/teachers/<?= $a['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                        <form method="post" action="/absences/teachers/<?= $a['id'] ?>/delete" style="display:inline;">
                            <?= \OpenClassbook\View::csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Abwesenheit wirklich loeschen?">Loeschen</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
