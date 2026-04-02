<div class="page-header">
    <h1>Authenticator-App einrichten</h1>
</div>

<div class="card">
    <p>Scannen Sie den QR-Code mit Ihrer Authenticator-App (z.B. Google Authenticator, Authy, Microsoft Authenticator).</p>

    <div style="text-align: center; margin: var(--spacing-lg) 0;">
        <?= $qr_svg ?>
    </div>

    <details style="margin-bottom: var(--spacing-lg);">
        <summary style="cursor: pointer;">Manueller Schluessel (falls QR-Code nicht funktioniert)</summary>
        <div style="margin-top: var(--spacing-sm);">
            <code style="font-size: var(--font-size-lg); letter-spacing: 2px; word-break: break-all;"><?= htmlspecialchars($manual_key, ENT_QUOTES, 'UTF-8') ?></code>
        </div>
    </details>

    <form method="post" action="/two-factor/confirm-totp">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="code">Bestaetigungscode <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="code" name="code" class="form-control" required autofocus autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-describedby="code_help">
            <span class="form-help" id="code_help">Geben Sie den 6-stelligen Code ein, der in Ihrer Authenticator-App angezeigt wird.</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Bestaetigen und aktivieren</button>
            <a href="/two-factor/setup" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
