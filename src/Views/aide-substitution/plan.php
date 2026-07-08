<div class="page-header">
    <h1>Vertretung planen</h1>
    <div class="page-header-actions">
        <a href="/aide-substitution?date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="btn btn-secondary">Bedarfsübersicht</a>
    </div>
</div>

<div class="card">
    <form method="get" action="/aide-substitution/plan" class="filter-form">
        <div class="form-group">
            <label for="date_from">Von</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">Bis</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Anzeigen</button>
    </form>
</div>

<?php if (empty($absences)): ?>
    <div class="card mt-1"><p class="text-center">Keine abwesenden Schulbegleiter:innen im gewählten Zeitraum.</p></div>
<?php endif; ?>

<?php foreach ($absences as $absence): ?>
<div class="card mt-1">
    <div class="card-header">
        <h2>
            <?= htmlspecialchars($absence['lastname'] . ', ' . $absence['firstname'], ENT_QUOTES, 'UTF-8') ?>
            <span class="badge badge-danger"><?= htmlspecialchars(ucfirst($absence['absence_type']), ENT_QUOTES, 'UTF-8') ?></span>
        </h2>
        <p class="text-muted">
            <?= date('d.m.Y', strtotime($absence['date_from'])) ?> – <?= date('d.m.Y', strtotime($absence['date_to'])) ?>
        </p>
    </div>

    <?php if (empty($absence['students'])): ?>
        <p class="text-muted">Dieser Begleitung sind keine Schüler:innen zugewiesen.</p>
    <?php else: ?>
        <?php foreach ($absence['students'] as $student): ?>
        <?php $sub = $student['substitution'] ?? null; ?>
        <form method="post" action="/aide-substitution/assign" class="aide-sub-row">
            <?= \OpenClassbook\View::csrfField() ?>
            <input type="hidden" name="absent_aide_id" value="<?= (int) $absence['aide_id'] ?>">
            <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
            <input type="hidden" name="absence_aide_id" value="<?= (int) $absence['absence_id'] ?>">
            <input type="hidden" name="date_from" value="<?= htmlspecialchars($absence['date_from'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="date_to" value="<?= htmlspecialchars($absence['date_to'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group flex-1">
                <label>Kind</label>
                <div class="form-static"><?= htmlspecialchars($student['lastname'] . ', ' . $student['firstname'] . ' (' . $student['class_name'] . ')', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="form-group">
                <label>Priorität</label>
                <select name="priority" class="form-control">
                    <?php foreach ($priorities as $level => $label): ?>
                        <option value="<?= $level ?>" <?= (int) ($sub['priority'] ?? 3) === $level ? 'selected' : '' ?>>
                            <?= $level ?> – <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group flex-1">
                <label>Ersatz-Begleitung</label>
                <select name="substitute_aide_id" class="form-control">
                    <option value="0">- Offen -</option>
                    <?php foreach ($absence['available_aides'] as $cand): ?>
                        <option value="<?= $cand['id'] ?>" <?= (int) ($sub['substitute_aide_id'] ?? 0) === (int) $cand['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cand['lastname'] . ', ' . $cand['firstname'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group flex-1">
                <label>Notiz</label>
                <input type="text" name="notes" class="form-control" maxlength="255" value="<?= htmlspecialchars($sub['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-sm">Speichern</button>
            </div>
        </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>
