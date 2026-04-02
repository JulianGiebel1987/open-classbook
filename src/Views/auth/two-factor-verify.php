<div class="auth-form">
    <h2>Zwei-Faktor-Authentifizierung</h2>

    <?php if ($method === 'email'): ?>
        <p>Ein 6-stelliger Code wurde an Ihre E-Mail-Adresse gesendet. Bitte geben Sie diesen Code ein.</p>
    <?php else: ?>
        <p>Bitte geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein.</p>
    <?php endif; ?>

    <form method="post" action="/two-factor/verify" id="verify-form">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="code">Verifizierungscode</label>
            <input type="text" id="code" name="code" class="form-control" required autofocus autocomplete="one-time-code" inputmode="numeric" pattern="[0-9a-fA-F\-]{4,12}" maxlength="12" placeholder="000000">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-login">Verifizieren</button>
        </div>
    </form>

    <?php if ($method === 'email'): ?>
        <div class="text-center" style="margin-top: var(--spacing-md);">
            <form method="post" action="/two-factor/resend" style="display: inline;">
                <?= \OpenClassbook\View::csrfField() ?>
                <button type="submit" class="btn-link">Code erneut senden</button>
            </form>
        </div>
    <?php endif; ?>

    <hr style="margin: var(--spacing-lg) 0;">

    <details>
        <summary style="cursor: pointer; color: var(--color-text-muted);">Recovery-Code verwenden</summary>
        <form method="post" action="/two-factor/verify" style="margin-top: var(--spacing-md);">
            <?= \OpenClassbook\View::csrfField() ?>
            <input type="hidden" name="use_recovery" value="1">

            <div class="form-group">
                <label for="recovery_code">Recovery-Code</label>
                <input type="text" id="recovery_code" name="code" class="form-control" placeholder="xxxx-xxxx" autocomplete="off">
            </div>

            <button type="submit" class="btn btn-secondary">Mit Recovery-Code anmelden</button>
        </form>
    </details>

    <div class="text-center" style="margin-top: var(--spacing-lg);">
        <a href="/logout">Abbrechen und abmelden</a>
    </div>
</div>
