<div class="auth-form">
    <h2>Anmelden</h2>
    <form method="post" action="/login">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="username">Benutzername oder E-Mail</label>
            <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-login">Anmelden</button>
        </div>

        <div class="text-center">
            <a href="/forgot-password">Passwort vergessen?</a>
        </div>
    </form>

    <p class="text-muted text-center" style="margin-top: var(--spacing-lg); font-size: var(--font-size-xs);">
        Mit der Anmeldung bestaetigen Sie, dass Sie die
        <a href="/datenschutz" style="color: inherit; text-decoration: underline;">Datenschutzhinweise</a>
        zur Kenntnis genommen haben. Login-Versuche werden zur Sicherheit pseudonymisiert protokolliert.
    </p>
</div>
