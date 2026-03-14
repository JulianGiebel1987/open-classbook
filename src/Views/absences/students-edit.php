<div class="page-header">
    <h1>Fehlzeit bearbeiten</h1>
</div>
<p class="text-muted mb-1">
    <?= htmlspecialchars($absence['lastname'] . ', ' . $absence['firstname'], ENT_QUOTES, 'UTF-8') ?>
    (<?= htmlspecialchars($absence['class_name'], ENT_QUOTES, 'UTF-8') ?>)
</p>

<div class="card">
    <form method="post" action="/absences/students/<?= $absence['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="filter-form">
            <div class="form-group" style="flex:1;">
                <label for="date_from">Von <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="date" id="date_from" name="date_from" class="form-control" required value="<?= $absence['date_from'] ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label for="date_to">Bis <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="date" id="date_to" name="date_to" class="form-control" required value="<?= $absence['date_to'] ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="excused">Status <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="excused" id="excused" class="form-control" required>
                <option value="offen" <?= $absence['excused'] === 'offen' ? 'selected' : '' ?>>Offen</option>
                <option value="ja" <?= $absence['excused'] === 'ja' ? 'selected' : '' ?>>Entschuldigt</option>
                <option value="nein" <?= $absence['excused'] === 'nein' ? 'selected' : '' ?>>Unentschuldigt</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reason">Grund</label>
            <input type="text" id="reason" name="reason" class="form-control" maxlength="500" value="<?= htmlspecialchars($absence['reason'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="2"><?= htmlspecialchars($absence['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/absences/students" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
