<h1>Neuer Klassenbucheintrag</h1>
<p style="color:var(--color-text-light);">Klasse: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card mt-1">
    <form method="post" action="/classbook/<?= $class['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="entry_date">Datum *</label>
            <input type="date" id="entry_date" name="entry_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="lesson">Unterrichtsstunde (1-10) *</label>
            <input type="number" id="lesson" name="lesson" class="form-control" required min="1" max="10" value="1">
        </div>

        <div class="form-group">
            <label for="topic">Thema *</label>
            <input type="text" id="topic" name="topic" class="form-control" required maxlength="500">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Eintragen</button>
            <a href="/classbook/<?= $class['id'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
