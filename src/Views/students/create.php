<div class="page-header">
    <h1>Neue:n Schüler:in anlegen</h1>
</div>

<p class="text-muted mb-1">
    Klasse: <strong><?= htmlspecialchars($class['name'] . ' (' . $class['school_year'] . ')', ENT_QUOTES, 'UTF-8') ?></strong>
</p>

<div class="alert alert-info" role="alert">
    Für die Schüler:in wird automatisch ein Benutzerkonto in der Benutzerverwaltung angelegt.
    Die Zugangsdaten werden nach dem Speichern <strong>einmalig</strong> angezeigt.
</div>

<div class="card">
    <form method="post" action="/classes/<?= (int) $class['id'] ?>/students">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="firstname" name="firstname" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($old['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="lastname" name="lastname" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($old['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="birthday">Geburtsdatum</label>
            <input type="date" id="birthday" name="birthday" class="form-control"
                   value="<?= htmlspecialchars($old['birthday'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="guardian_email">Erziehungsberechtigten-E-Mail</label>
            <input type="email" id="guardian_email" name="guardian_email" class="form-control" maxlength="255"
                   value="<?= htmlspecialchars($old['guardian_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="guardian_email_help">
            <span class="form-help" id="guardian_email_help">Wird als Kontakt-Adresse des Kontos verwendet (z.&nbsp;B. für die Zustellung der Zugangsdaten).</span>
        </div>

        <div class="form-group">
            <label for="guardian_phone">Erziehungsberechtigten-Telefon</label>
            <input type="tel" id="guardian_phone" name="guardian_phone" class="form-control" maxlength="30"
                   value="<?= htmlspecialchars($old['guardian_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="guardian_phone_help">
            <span class="form-help" id="guardian_phone_help">Optionaler Telefonkontakt, wird im Klassenbuch als Kontaktmöglichkeit angezeigt.</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Anlegen</button>
            <a href="/classes/<?= (int) $class['id'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
