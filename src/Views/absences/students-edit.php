<h1>Fehlzeit bearbeiten</h1>
<p style="color:var(--color-text-light);">
    <?= htmlspecialchars($absence['lastname'] . ', ' . $absence['firstname'], ENT_QUOTES, 'UTF-8') ?>
    (<?= htmlspecialchars($absence['class_name'], ENT_QUOTES, 'UTF-8') ?>)
</p>

<div class="card mt-1">
    <form method="post" action="/absences/students/<?= $absence['id'] ?>">
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
            <label for="excused">Status *</label>
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

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Speichern</button>
            <a href="/absences/students" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
