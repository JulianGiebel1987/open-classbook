<div class="page-header">
    <h1>E-Mail-Adresse ändern</h1>
</div>

<div class="card">
    <p>
        Ihre aktuelle E-Mail-Adresse (zugleich Ihr Anmeldename):
        <strong><code><?= htmlspecialchars($user['email'] ?? '–', ENT_QUOTES, 'UTF-8') ?></code></strong>
    </p>

    <?php if (empty($canChange)): ?>
        <div class="alert alert-info" role="alert">
            Für Ihr Konto steht die Selbstbedienungs-Änderung der E-Mail-Adresse nicht zur Verfügung.
            Bitte wenden Sie sich an die Administration.
        </div>
    <?php else: ?>
        <form method="post" action="/account/email">
            <?= \OpenClassbook\View::csrfField() ?>

            <div class="form-group">
                <label for="new_email">Neue E-Mail-Adresse <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="email" id="new_email" name="new_email" class="form-control" required maxlength="255" autocomplete="email" aria-describedby="new_email_help">
                <span class="form-help" id="new_email_help">
                    An diese Adresse senden wir einen Bestätigungslink. Erst nach der Bestätigung
                    wird die neue Adresse aktiv und ersetzt Ihren Anmeldenamen. Aktive Sitzungen
                    werden dabei beendet.
                </span>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn">Bestätigungslink senden</button>
                <a href="/dashboard" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    <?php endif; ?>
</div>
