<div class="page-header">
    <h1>Zwei-Faktor-Authentifizierung</h1>
</div>

<div class="card">
    <?php if ($isConfirmed): ?>
        <div class="alert alert-success" role="alert">
            <span>2FA ist aktiv (Methode: <?= $currentMethod === 'totp' ? 'Authenticator-App' : 'E-Mail' ?>).</span>
        </div>

        <div class="btn-group">
            <a href="/two-factor/recovery-codes" class="btn btn-secondary">Recovery-Codes anzeigen</a>
            <form method="post" action="/two-factor/disable" style="display: inline;">
                <?= \OpenClassbook\View::csrfField() ?>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Moechten Sie die Zwei-Faktor-Authentifizierung wirklich deaktivieren?')">2FA deaktivieren</button>
            </form>
        </div>
    <?php else: ?>
        <p>Schuetzen Sie Ihr Konto mit einer zusaetzlichen Sicherheitsebene. Waehlen Sie eine Methode:</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg); margin-top: var(--spacing-lg);">
            <div class="card" style="border: 1px solid var(--color-border);">
                <h3>Authenticator-App</h3>
                <p>Verwenden Sie eine App wie Google Authenticator oder Authy, um zeitbasierte Codes zu generieren.</p>
                <form method="post" action="/two-factor/setup">
                    <?= \OpenClassbook\View::csrfField() ?>
                    <input type="hidden" name="method" value="totp">
                    <button type="submit" class="btn">Authenticator-App einrichten</button>
                </form>
            </div>

            <div class="card" style="border: 1px solid var(--color-border);">
                <h3>E-Mail</h3>
                <p>Erhalten Sie bei jeder Anmeldung einen Verifizierungscode per E-Mail.</p>
                <?php if (!empty($user['email'])): ?>
                    <form method="post" action="/two-factor/setup">
                        <?= \OpenClassbook\View::csrfField() ?>
                        <input type="hidden" name="method" value="email">
                        <button type="submit" class="btn">E-Mail-2FA aktivieren</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Sie benoetigen eine hinterlegte E-Mail-Adresse. Bitte aktualisieren Sie Ihr Profil.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
