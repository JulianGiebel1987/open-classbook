<div class="page-header">
    <h1>Klasse waehlen – <?= htmlspecialchars($setting['school_year'], ENT_QUOTES, 'UTF-8') ?></h1>
</div>

<div class="card">
    <div class="card-header">
        <h2>Fuer welche Klasse moechten Sie den Stundenplan bearbeiten?</h2>
    </div>
    <?php if (empty($classes)): ?>
        <p class="text-muted">Keine Klassen vorhanden.</p>
    <?php else: ?>
        <div class="dashboard-grid">
            <?php foreach ($classes as $c): ?>
            <a href="/timetable/<?= (int) $setting['id'] ?>/class/<?= (int) $c['id'] ?>"
               class="widget"
               aria-label="Stundenplan fuer <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> bearbeiten">
                <div class="widget-value"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="widget-label"><?= htmlspecialchars($c['school_year'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
