<div class="page-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <?php if (in_array(\OpenClassbook\App::currentUserRole(), ['admin', 'schulleitung', 'sekretariat'])): ?>
        <a href="/zeugnis/templates/<?= (int) $template['id'] ?>/edit" class="btn btn-secondary">Bearbeiten</a>
        <?php endif; ?>
        <a href="/zeugnis/browse" class="btn btn-muted">← Zurück</a>
    </div>
</div>

<div class="card mb-3">
    <div class="form-row">
        <div class="form-group">
            <strong>Format:</strong>
            <?= htmlspecialchars($template['page_format'], ENT_QUOTES, 'UTF-8') ?>
            <?= $template['page_orientation'] === 'L' ? 'Querformat' : 'Hochformat' ?>
        </div>
        <?php if ($template['school_year']): ?>
        <div class="form-group">
            <strong>Schuljahr:</strong> <?= htmlspecialchars($template['school_year'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
        <?php if ($template['grade_levels']): ?>
        <div class="form-group">
            <strong>Klassenstufen:</strong> <?= htmlspecialchars($template['grade_levels'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <strong>Status:</strong>
            <?php if ($template['status'] === 'published'): ?>
                <span class="badge badge-published">Veröffentlicht</span>
            <?php else: ?>
                <span class="badge badge-draft">Entwurf</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="zeugnis-canvas-wrapper" id="canvas-wrapper">
    <div class="zeugnis-canvas" id="zeugnis-canvas" style="pointer-events:none"></div>
</div>

<?php if ($template['status'] === 'published'): ?>
<div style="margin-top:var(--spacing-lg);text-align:center">
    <a href="/zeugnis/create/<?= (int) $template['id'] ?>" class="btn btn-primary">Dokument erstellen</a>
    <a href="/zeugnis/batch/<?= (int) $template['id'] ?>" class="btn btn-secondary">Für Klasse erstellen</a>
</div>
<?php endif; ?>

<script type="application/json" id="zeugnis-canvas-data">
<?= json_encode(
    json_decode($template['template_canvas'] ?? '{"pages":[]}', true) ?? ['pages' => []],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
) ?: '{"pages":[]}' ?>
</script>
<script type="application/json" id="zeugnis-meta">
<?= json_encode([
    'templateId'     => (int) $template['id'],
    'csrfToken'      => null,
    'imageUploadUrl' => null,
    'previewMode'    => true,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
</script>
<script src="/js/zeugnis-editor.js"></script>
