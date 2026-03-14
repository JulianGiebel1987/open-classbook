<div class="page-header">
    <h1>Neuer Klassenbucheintrag</h1>
</div>
<p class="text-muted mb-1">Klasse: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <form method="post" action="/classbook/<?= $class['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="entry_date">Datum <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="date" id="entry_date" name="entry_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="lesson">Unterrichtsstunde <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="number" id="lesson" name="lesson" class="form-control" required min="1" max="10" value="1" aria-describedby="lesson_help">
            <span class="form-help" id="lesson_help">Stunde 1 bis 10</span>
        </div>

        <div class="form-group">
            <label for="topic">Thema <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="topic" name="topic" class="form-control" required maxlength="500">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Eintragen</button>
            <a href="/classbook/<?= $class['id'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
