<div class="page-header">
    <h1>Abwesenheit bearbeiten</h1>
</div>
<p class="text-muted mb-1">
    <?= htmlspecialchars($absence['lastname'] . ', ' . $absence['firstname'] . ' (' . $absence['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?>
</p>

<div class="card">
    <form method="post" action="/absences/teachers/<?= $absence['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="filter-form">
            <div class="form-group flex-1">
                <label for="date_from">Von <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="date" id="date_from" name="date_from" class="form-control" required value="<?= $absence['date_from'] ?>">
            </div>
            <div class="form-group flex-1">
                <label for="date_to">Bis <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="date" id="date_to" name="date_to" class="form-control" required value="<?= $absence['date_to'] ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="type">Typ <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="type" id="type" class="form-control" required>
                <option value="krank" <?= $absence['type'] === 'krank' ? 'selected' : '' ?>>Krank</option>
                <option value="fortbildung" <?= $absence['type'] === 'fortbildung' ? 'selected' : '' ?>>Fortbildung</option>
                <option value="sonstiges" <?= $absence['type'] === 'sonstiges' ? 'selected' : '' ?>>Sonstiges</option>
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
            <a href="/absences/teachers" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
