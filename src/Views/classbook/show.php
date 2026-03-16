<div class="page-header">
    <h1>Klassenbuch: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <?php $role = \OpenClassbook\App::currentUserRole(); ?>
        <?php if ($role === 'admin' || $role === 'lehrer'): ?>
            <a href="/classbook/<?= $class['id'] ?>/create" class="btn">Neuer Eintrag</a>
        <?php endif; ?>
        <a href="/classbook/<?= $class['id'] ?>/export-csv?date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>" class="btn btn-secondary">CSV Export</a>
        <a href="/classbook/<?= $class['id'] ?>/export-pdf?date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>" class="btn btn-secondary">PDF Export</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
    </div>
    <form method="get" action="/classbook/<?= $class['id'] ?>" class="filter-form">
        <div class="form-group">
            <label for="date_from">Von</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">Bis</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="teacher_id">Lehrkraft</label>
            <select name="teacher_id" id="teacher_id" class="form-control">
                <option value="">Alle</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($filters['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['abbreviation'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Klassenbucheintraege">
            <thead>
                <tr>
                    <th scope="col">Datum</th>
                    <th scope="col">Std.</th>
                    <th scope="col">Lehrkraft</th>
                    <th scope="col">Thema</th>
                    <th scope="col">Notizen</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="6" class="text-center">Keine Eintraege gefunden.</td></tr>
                <?php endif; ?>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td><?= date('d.m.Y', strtotime($e['entry_date'])) ?></td>
                    <td><?= $e['lesson'] ?></td>
                    <td><?= htmlspecialchars($e['abbreviation'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($e['topic'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($e['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (\OpenClassbook\Models\ClassbookEntry::canEdit($e, $_SESSION['user_id'], $role)): ?>
                            <a href="/classbook/entry/<?= $e['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-1">
    <a href="/classbook" class="btn btn-secondary">Zurueck</a>
</div>
