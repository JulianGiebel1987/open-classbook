<div class="page-header">
    <h1>Klassenbuch: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <?php $role = \OpenClassbook\App::currentUserRole(); ?>
        <?php if ($role === 'admin' || $role === 'lehrer'): ?>
            <a href="/classbook/<?= $class['id'] ?>/create" class="btn">Neuer Eintrag</a>
        <?php endif; ?>
        <a href="/absences/students?class_id=<?= $class['id'] ?>" class="btn btn-secondary">Schüler:innen-Fehlzeiten</a>
        <a href="/classbook/<?= $class['id'] ?>/remarks" class="btn btn-secondary">Schülerbemerkungen</a>
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

<?php if (empty($entries)): ?>
<div class="card mt-1">
    <p class="text-muted text-center">Keine Einträge gefunden.</p>
</div>
<?php else: ?>

<?php
$weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
$grouped = [];
foreach ($entries as $e) {
    $grouped[$e['entry_date']][] = $e;
}
?>

<div class="classbook-day-list mt-1">
    <?php foreach ($grouped as $date => $dayEntries): ?>
    <?php
        $ts = strtotime($date);
        $dayName = $weekdays[(int) date('w', $ts)];
        $dateFormatted = date('d.m.Y', $ts);
        $count = count($dayEntries);
    ?>
    <div class="classbook-day">
        <div class="classbook-day-header">
            <span class="classbook-day-weekday"><?= $dayName ?></span>
            <span class="classbook-day-date"><?= $dateFormatted ?></span>
            <span class="classbook-day-count"><?= $count ?> <?= $count === 1 ? 'Stunde' : 'Stunden' ?></span>
        </div>
        <div class="table-responsive">
            <table aria-label="Einträge vom <?= $dateFormatted ?>">
                <thead>
                    <tr>
                        <th scope="col">Std.</th>
                        <th scope="col">Lehrkraft</th>
                        <th scope="col">Thema / Notizen</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dayEntries as $e): ?>
                    <tr>
                        <td style="width: 3.5rem;">
                            <span class="classbook-lesson-number"><?= (int) $e['lesson'] ?></span>
                        </td>
                        <td style="width: 7rem; white-space: nowrap;">
                            <?= htmlspecialchars($e['abbreviation'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div class="classbook-topic"><?= htmlspecialchars($e['topic'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($e['notes'])): ?>
                                <div class="classbook-notes"><?= htmlspecialchars($e['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="width: 6rem; text-align: right;">
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
    <?php endforeach; ?>
</div>

<?php endif; ?>

<div class="mt-1">
    <a href="/classbook" class="btn btn-secondary">Zurück</a>
</div>
