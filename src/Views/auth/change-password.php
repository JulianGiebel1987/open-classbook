<div class="card">
    <div class="card-header">
        <h2>Passwort aendern</h2>
    </div>

    <?php if (!empty($forced)): ?>
        <div class="alert alert-warning">
            Sie muessen Ihr Passwort bei der ersten Anmeldung aendern.
        </div>
    <?php endif; ?>

    <form method="post" action="/change-password">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="current_password">Aktuelles Passwort</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="new_password">Neues Passwort (mind. 10 Zeichen, Gross-/Kleinbuchstaben, Ziffer)</label>
            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="10">
        </div>

        <div class="form-group">
            <label for="confirm_password">Neues Passwort bestaetigen</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>

        <div class="form-group">
            <button type="submit" class="btn">Passwort aendern</button>
        </div>
    </form>
</div>
