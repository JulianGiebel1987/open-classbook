<div class="page-header">
    <h1>Passwort zurückgesetzt</h1>
</div>

<div class="card" style="max-width: 480px;">
    <div class="card-header">
        <h2>Temporaeres Passwort</h2>
    </div>
    <p>Das Passwort für <strong><?= htmlspecialchars($info['username'], ENT_QUOTES, 'UTF-8') ?></strong> wurde zurückgesetzt.</p>
    <p class="text-muted" style="margin-top: var(--spacing-sm);">Bitte notieren Sie das temporaere Passwort und teilen Sie es dem Nutzer mit. Es wird nur einmal angezeigt.</p>

    <div style="margin-top: var(--spacing-md); padding: var(--spacing-md); background: var(--color-warning-light); border-left: 4px solid var(--color-warning); border-radius: var(--radius); font-family: monospace; font-size: 1.1rem; letter-spacing: 0.05em;">
        <?= htmlspecialchars($info['password'], ENT_QUOTES, 'UTF-8') ?>
    </div>

    <p class="text-muted" style="margin-top: var(--spacing-sm); font-size: var(--font-size-sm);">Der Nutzer wird beim naechsten Login aufgefordert, das Passwort zu ändern.</p>

    <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-sm); flex-wrap: wrap; align-items: center;">
        <a href="/users" class="btn">Zurück zur Nutzerverwaltung</a>

        <?php if ($mailEnabled): ?>
            <form method="post" action="/users/<?= (int) $info['user_id'] ?>/send-temp-password" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">
                    Zugangsdaten per E-Mail senden an <?= htmlspecialchars($info['email'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>
        <?php else: ?>
            <span class="text-muted" style="font-size: var(--font-size-sm);">
                E-Mail-Versand nicht konfiguriert &ndash; Passwort bitte manuell weitergeben.
            </span>
        <?php endif; ?>
    </div>
</div>
