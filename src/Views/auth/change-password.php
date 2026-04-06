<div class="page-header">
    <h1>Passwort ändern</h1>
</div>

<div class="card">
    <?php if (!empty($forced)): ?>
        <div class="alert alert-warning" role="alert">
            <span>Sie müssen Ihr Passwort bei der ersten Anmeldung ändern.</span>
        </div>
    <?php endif; ?>

    <form method="post" action="/change-password">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="current_password">Aktuelles Passwort <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="new_password">Neues Passwort <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="10" autocomplete="new-password" aria-describedby="new_password_help">
            <span class="form-help" id="new_password_help">Min. 10 Zeichen, Gross- und Kleinbuchstaben, mindestens eine Ziffer.</span>
        </div>

        <div class="form-group">
            <label for="confirm_password">Neues Passwort bestaetigen <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn">Passwort ändern</button>
    </form>
</div>
