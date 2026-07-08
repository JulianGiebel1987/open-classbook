<div class="page-header">
    <h1>Schulbegleiter:in bearbeiten</h1>
</div>

<div class="card">
    <form method="post" action="/aides/<?= $aide['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="firstname" name="firstname" class="form-control" required maxlength="100" value="<?= htmlspecialchars($aide['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="lastname" name="lastname" class="form-control" required maxlength="100" value="<?= htmlspecialchars($aide['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="comment">Kommentar</label>
            <textarea id="comment" name="comment" class="form-control" rows="2"><?= htmlspecialchars($aide['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <fieldset class="form-group fieldset-clean">
            <legend>Begleitete Schüler:innen zuweisen</legend>
            <div class="checkbox-scroll">
                <?php foreach ($students ?? [] as $s): ?>
                <label>
                    <input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $assignedStudentIds ?? []) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'] . ' (' . $s['class_name'] . ')', ENT_QUOTES, 'UTF-8') ?>
                </label>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                    <p class="form-help">Noch keine Schüler:innen vorhanden.</p>
                <?php endif; ?>
            </div>
        </fieldset>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/aides" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
