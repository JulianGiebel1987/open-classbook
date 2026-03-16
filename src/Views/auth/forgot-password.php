<div class="auth-form">
    <h2>Passwort zuruecksetzen</h2>
    <p class="text-muted auth-hint">
        Geben Sie Ihre E-Mail-Adresse ein. Sie erhalten einen Link zum Zuruecksetzen Ihres Passworts.
    </p>
    <form method="post" action="/forgot-password">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="email">E-Mail-Adresse</label>
            <input type="email" id="email" name="email" class="form-control" required autofocus autocomplete="email">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-login">Link senden</button>
        </div>

        <div class="text-center">
            <a href="/login">Zurueck zur Anmeldung</a>
        </div>
    </form>
</div>
