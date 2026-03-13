<h1>Klassenbucheintrag bearbeiten</h1>

<div class="card">
    <form method="post" action="/classbook/entry/<?= $entry['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="entry_date">Datum *</label>
            <input type="date" id="entry_date" name="entry_date" class="form-control" required value="<?= $entry['entry_date'] ?>">
        </div>

        <div class="form-group">
            <label for="lesson">Unterrichtsstunde (1-10) *</label>
            <input type="number" id="lesson" name="lesson" class="form-control" required min="1" max="10" value="<?= $entry['lesson'] ?>">
        </div>

        <div class="form-group">
            <label for="topic">Thema *</label>
            <input type="text" id="topic" name="topic" class="form-control" required maxlength="500" value="<?= htmlspecialchars($entry['topic'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($entry['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Speichern</button>
            <a href="/classbook/<?= $entry['class_id'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
