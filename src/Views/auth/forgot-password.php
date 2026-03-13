<div class="auth-form">
    <h2>Passwort zuruecksetzen</h2>
    <p style="margin-bottom: 1rem; color: var(--color-text-light); font-size: 0.875rem;">
        Geben Sie Ihre E-Mail-Adresse ein. Sie erhalten einen Link zum Zuruecksetzen Ihres Passworts.
    </p>
    <form method="post" action="/forgot-password">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="email">E-Mail-Adresse</label>
            <input type="email" id="email" name="email" class="form-control" required autofocus>
        </div>

        <div class="form-group">
            <button type="submit" class="btn" style="width:100%">Link senden</button>
        </div>

        <div class="text-center">
            <a href="/login">Zurueck zur Anmeldung</a>
        </div>
    </form>
</div>
