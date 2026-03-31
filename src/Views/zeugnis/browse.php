<div class="page-header">
    <h1>Vorlagen</h1>
    <a href="/zeugnis" class="btn btn-muted">← Meine Dokumente</a>
</div>

<?php if (empty($templates)): ?>
<div class="card">
    <p class="text-center text-muted">
        Noch keine Vorlagen veröffentlicht.<br>
        Bitte wenden Sie sich an die Schulleitung oder das Sekretariat.
    </p>
</div>
<?php else: ?>
<div class="card-grid">
    <?php foreach ($templates as $t): ?>
    <div class="card card--template">
        <div class="card-body">
            <h3><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></h3>
            <?php if (!empty($t['description'])): ?>
                <p class="text-muted"><?= htmlspecialchars($t['description'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <dl class="meta-list">
                <dt>Format</dt>
                <dd><?= htmlspecialchars($t['page_format'], ENT_QUOTES, 'UTF-8') ?>
                    <?= $t['page_orientation'] === 'L' ? 'Querformat' : 'Hochformat' ?>
                </dd>
                <?php if ($t['school_year']): ?>
                <dt>Schuljahr</dt>
                <dd><?= htmlspecialchars($t['school_year'], ENT_QUOTES, 'UTF-8') ?></dd>
                <?php endif; ?>
                <?php if ($t['grade_levels']): ?>
                <dt>Klassenstufen</dt>
                <dd><?= htmlspecialchars($t['grade_levels'], ENT_QUOTES, 'UTF-8') ?></dd>
                <?php endif; ?>
            </dl>
        </div>
        <div class="card-footer btn-group">
            <a href="/zeugnis/templates/<?= (int) $t['id'] ?>/preview" class="btn btn-sm btn-muted">Vorschau</a>
            <a href="/zeugnis/create/<?= (int) $t['id'] ?>" class="btn btn-sm btn-secondary">Einzeln</a>
            <a href="/zeugnis/batch/<?= (int) $t['id'] ?>" class="btn btn-sm btn-primary">Für Klasse</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
