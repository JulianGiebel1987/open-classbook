<?php $old = $old ?? []; ?>
<div class="page-header">
    <h1>Neue:n Schulbegleiter:in anlegen</h1>
</div>

<div class="card">
    <p class="text-muted mb-1">Ein Benutzerkonto (Rolle: Schulbegleiter:in) wird automatisch erstellt. Die Zugangsdaten werden nach dem Speichern einmalig angezeigt.</p>
    <form method="post" action="/aides">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="firstname" name="firstname" class="form-control" required maxlength="100" value="<?= htmlspecialchars($old['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="lastname" name="lastname" class="form-control" required maxlength="100" value="<?= htmlspecialchars($old['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" class="form-control" maxlength="255" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="comment">Kommentar</label>
            <textarea id="comment" name="comment" class="form-control" rows="2" aria-describedby="comment_help"><?= htmlspecialchars($old['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <span class="form-help" id="comment_help">Freitext, z.B. Einsatzzeiten oder Hinweise.</span>
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
            <button type="submit" class="btn">Anlegen</button>
            <a href="/aides" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
