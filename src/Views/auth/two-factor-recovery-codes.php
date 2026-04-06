<div class="page-header">
    <h1>Recovery-Codes</h1>
</div>

<div class="card">
    <?php if (!empty($codes)): ?>
        <div class="alert alert-warning" role="alert">
            <span><strong>Wichtig:</strong> Speichern Sie diese Codes an einem sicheren Ort. Jeder Code kann nur einmal verwendet werden. Wenn Sie keinen Zugriff auf Ihre 2FA-Methode haben, können Sie sich mit einem Recovery-Code anmelden.</span>
        </div>

        <div style="background: var(--color-bg-secondary, #f5f5f5); padding: var(--spacing-lg); border-radius: var(--border-radius, 4px); margin: var(--spacing-lg) 0; font-family: monospace; font-size: var(--font-size-lg);">
            <?php foreach ($codes as $code): ?>
                <div style="padding: var(--spacing-xs) 0;"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>

        <p class="text-muted">Diese Codes werden nur einmal angezeigt. Nach dem Verlassen dieser Seite können Sie die Codes nicht erneut einsehen.</p>
    <?php else: ?>
        <p>Sie haben bereits Recovery-Codes generiert. Aus Sicherheitsgruenden werden diese nur einmal angezeigt.</p>
    <?php endif; ?>

    <div class="btn-group" style="margin-top: var(--spacing-lg);">
        <form method="post" action="/two-factor/regenerate-codes" style="display: inline;">
            <?= \OpenClassbook\View::csrfField() ?>
            <button type="submit" class="btn btn-secondary" onclick="return confirm('Achtung: Alle bisherigen Recovery-Codes werden ungültig. Möchten Sie fortfahren?')">Neue Codes generieren</button>
        </form>
        <a href="/two-factor/setup" class="btn btn-secondary">Zurück zur 2FA-Einrichtung</a>
        <a href="/dashboard" class="btn">Zum Dashboard</a>
    </div>
</div>
