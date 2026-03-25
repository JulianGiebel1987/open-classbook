<div class="page-header">
    <h1>Passwort zurueckgesetzt</h1>
</div>

<div class="card" style="max-width: 480px;">
    <div class="card-header">
        <h2>Temporaeres Passwort</h2>
    </div>
    <p>Das Passwort fuer <strong><?= htmlspecialchars($info['username'], ENT_QUOTES, 'UTF-8') ?></strong> wurde zurueckgesetzt.</p>
    <p class="text-muted" style="margin-top: var(--spacing-sm);">Bitte notieren Sie das temporaere Passwort und teilen Sie es dem Nutzer mit. Es wird nur einmal angezeigt.</p>

    <div style="margin-top: var(--spacing-md); padding: var(--spacing-md); background: var(--color-warning-light); border-left: 4px solid var(--color-warning); border-radius: var(--radius); font-family: monospace; font-size: 1.1rem; letter-spacing: 0.05em;">
        <?= htmlspecialchars($info['password'], ENT_QUOTES, 'UTF-8') ?>
    </div>

    <p class="text-muted" style="margin-top: var(--spacing-sm); font-size: var(--font-size-sm);">Der Nutzer wird beim naechsten Login aufgefordert, das Passwort zu aendern.</p>

    <div style="margin-top: var(--spacing-lg);">
        <a href="/users" class="btn">Zurueck zur Nutzerverwaltung</a>
    </div>
</div>
