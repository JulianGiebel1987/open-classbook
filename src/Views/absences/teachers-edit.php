<h1>Abwesenheit bearbeiten</h1>
<p style="color:var(--color-text-light);">
    <?= htmlspecialchars($absence['lastname'] . ', ' . $absence['firstname'] . ' (' . $absence['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?>
</p>

<div class="card mt-1">
    <form method="post" action="/absences/teachers/<?= $absence['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div style="display:flex; gap:0.5rem;">
            <div class="form-group" style="flex:1;">
                <label for="date_from">Von *</label>
                <input type="date" id="date_from" name="date_from" class="form-control" required value="<?= $absence['date_from'] ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label for="date_to">Bis *</label>
                <input type="date" id="date_to" name="date_to" class="form-control" required value="<?= $absence['date_to'] ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="type">Typ *</label>
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

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Speichern</button>
            <a href="/absences/teachers" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
