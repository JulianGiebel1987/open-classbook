<div class="page-header">
    <h1>Klassenbuch</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2>Klasse wählen</h2>
    </div>
    <?php if (empty($classes)): ?>
        <p class="text-muted">Keine Klassen verfügbar.</p>
    <?php else: ?>
        <div class="dashboard-grid">
            <?php foreach ($classes as $c): ?>
            <a href="/classbook/<?= $c['id'] ?>" class="widget" aria-label="Klassenbuch <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> öffnen">
                <div class="widget-value"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="widget-label"><?= htmlspecialchars($c['school_year'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
