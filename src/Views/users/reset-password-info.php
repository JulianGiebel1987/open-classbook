<div class="page-header">
    <h1>Passwort-Reset eingeleitet</h1>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-header">
        <h2>Reset-Link für <?= htmlspecialchars($info['username'], ENT_QUOTES, 'UTF-8') ?></h2>
    </div>

    <p>Für <strong><?= htmlspecialchars($info['username'], ENT_QUOTES, 'UTF-8') ?></strong> wurde ein einmaliger Passwort-Reset-Link erzeugt.</p>

    <p class="text-muted" style="margin-top: var(--spacing-sm);">
        Aus Sicherheitsgründen werden keine Klartext-Passwörter mehr vergeben. Der Link ist zeitlich begrenzt gültig und nur einmal verwendbar.
    </p>

    <div style="margin-top: var(--spacing-md); padding: var(--spacing-md); background: var(--color-info-light, #eef6ff); border-left: 4px solid var(--color-info, #3b82f6); border-radius: var(--radius); font-family: monospace; font-size: 0.95rem; word-break: break-all;">
        <?= htmlspecialchars($info['reset_url'], ENT_QUOTES, 'UTF-8') ?>
    </div>

    <p class="text-muted" style="margin-top: var(--spacing-sm); font-size: var(--font-size-sm);">
        Leiten Sie diesen Link ausschließlich über einen sicheren Kanal (persönlich, dienstliche E-Mail) an die/den Nutzer:in weiter. Nach dem Setzen eines neuen Passworts wird der Link automatisch entwertet.
    </p>

    <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-sm); flex-wrap: wrap; align-items: center;">
        <a href="/users" class="btn">Zurück zur Nutzerverwaltung</a>

        <?php if ($mailEnabled): ?>
            <form method="post" action="/users/<?= (int) $info['user_id'] ?>/send-temp-password" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">
                    Reset-Link per E-Mail senden an <?= htmlspecialchars($info['email'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            </form>
        <?php else: ?>
            <span class="text-muted" style="font-size: var(--font-size-sm);">
                E-Mail-Versand nicht konfiguriert &ndash; Link bitte manuell weitergeben.
            </span>
        <?php endif; ?>
    </div>
</div>
