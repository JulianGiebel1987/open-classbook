<div class="page-header">
    <h1>Schüler:in bearbeiten</h1>
</div>

<div class="card">
    <form method="post" action="/students/<?= (int) $student['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="firstname" name="firstname" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($student['firstname'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="lastname" name="lastname" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($student['lastname'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="class_id">Klasse <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="class_id" id="class_id" class="form-control" required>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ((int) $c['id'] === (int) $student['class_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'] . ' (' . $c['school_year'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="birthday">Geburtsdatum</label>
            <input type="date" id="birthday" name="birthday" class="form-control"
                   value="<?= htmlspecialchars($student['birthday'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="guardian_email">Erziehungsberechtigten-E-Mail</label>
            <input type="email" id="guardian_email" name="guardian_email" class="form-control" maxlength="255"
                   value="<?= htmlspecialchars($student['guardian_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="guardian_email_help">
            <span class="form-help" id="guardian_email_help">Änderungen werden auch am verknüpften Benutzerkonto übernommen.</span>
        </div>

        <div class="form-group">
            <label for="guardian_phone">Erziehungsberechtigten-Telefon</label>
            <input type="tel" id="guardian_phone" name="guardian_phone" class="form-control" maxlength="30"
                   value="<?= htmlspecialchars($student['guardian_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="guardian_phone_help">
            <span class="form-help" id="guardian_phone_help">Optionaler Telefonkontakt, wird im Klassenbuch als Kontaktmöglichkeit angezeigt.</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/classes/<?= (int) $student['class_id'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
