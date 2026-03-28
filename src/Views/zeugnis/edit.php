<div class="page-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <a href="/zeugnis/<?= (int) $instance['id'] ?>/export-pdf" class="btn btn-secondary">PDF exportieren</a>
        <?php if ((int) $instance['created_by'] === $_SESSION['user_id']): ?>
        <a href="/zeugnis/<?= (int) $instance['id'] ?>/share" class="btn btn-muted">Teilen</a>
        <?php endif; ?>
        <a href="/zeugnis" class="btn btn-muted">← Zurück</a>
    </div>
</div>

<?php if (!$canEdit): ?>
<div class="alert alert-info">Dieses Zeugnis wurde mit Ihnen geteilt (nur Ansicht).</div>
<?php endif; ?>

<div class="zeugnis-fill-layout">

    <!-- Canvas mit Formularfeldern -->
    <div>
        <form method="post" action="/zeugnis/<?= (int) $instance['id'] ?>" id="zeugnis-fill-form">
            <?= \OpenClassbook\View::csrfField() ?>

            <div class="zeugnis-canvas-wrapper">
                <div class="zeugnis-canvas zeugnis-fill-canvas" id="zeugnis-canvas"
                     data-instance-id="<?= (int) $instance['id'] ?>"
                     data-can-edit="<?= $canEdit ? '1' : '0' ?>">
                </div>
            </div>

            <?php if ($canEdit): ?>
            <div class="form-actions mt-3">
                <button type="submit" name="status" value="draft" class="btn btn-secondary">Entwurf speichern</button>
                <button type="submit" name="status" value="final" class="btn btn-primary"
                        data-confirm="Zeugnis als fertig markieren? Dann noch einmal prüfen, ob alle Felder ausgefüllt sind.">
                    Als fertig markieren
                </button>
            </div>
            <?php endif; ?>

        </form>
    </div>

    <!-- Seitenleiste -->
    <div class="zeugnis-fill-sidebar">
        <div class="card">
            <h3 class="card-title" style="font-size:var(--font-size-base)">Zeugnis-Info</h3>
            <dl class="meta-list">
                <dt>Schüler/in</dt>
                <dd><?= htmlspecialchars($instance['student_first_name'] . ' ' . $instance['student_last_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Klasse</dt>
                <dd><?= htmlspecialchars($instance['class_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Vorlage</dt>
                <dd><?= htmlspecialchars($instance['template_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                <dt>Format</dt>
                <dd><?= htmlspecialchars($instance['page_format'], ENT_QUOTES, 'UTF-8') ?>
                    <?= $instance['page_orientation'] === 'L' ? 'Querformat' : 'Hochformat' ?></dd>
                <dt>Status</dt>
                <dd>
                    <?php if ($instance['status'] === 'final'): ?>
                        <span class="badge badge-final">Fertig</span>
                    <?php else: ?>
                        <span class="badge badge-draft">Entwurf</span>
                    <?php endif; ?>
                </dd>
                <dt>Zuletzt gespeichert</dt>
                <dd><?= date('d.m.Y H:i', strtotime($instance['updated_at'])) ?></dd>
            </dl>
        </div>

        <div class="card mt-3">
            <h3 class="card-title" style="font-size:var(--font-size-base)">Aktionen</h3>
            <div class="btn-group btn-group--vertical">
                <a href="/zeugnis/<?= (int) $instance['id'] ?>/export-pdf" class="btn btn-secondary">
                    PDF herunterladen
                </a>
                <?php if ((int) $instance['created_by'] === $_SESSION['user_id']): ?>
                <a href="/zeugnis/<?= (int) $instance['id'] ?>/share" class="btn btn-muted">
                    Zeugnis teilen
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div id="autosave-status" style="font-size:var(--font-size-xs);color:var(--color-muted);margin-top:var(--spacing-sm);text-align:right"></div>
    </div>

</div>

<script>
var ZEUGNIS_CANVAS_DATA   = <?= json_encode($canvas, JSON_UNESCAPED_UNICODE) ?>;
var ZEUGNIS_FIELD_VALUES  = <?= json_encode($fieldValues, JSON_UNESCAPED_UNICODE) ?>;
var ZEUGNIS_TOKENS        = <?= json_encode($tokens, JSON_UNESCAPED_UNICODE) ?>;
var ZEUGNIS_CAN_EDIT      = <?= $canEdit ? 'true' : 'false' ?>;
var ZEUGNIS_INSTANCE_ID   = <?= (int) $instance['id'] ?>;
var ZEUGNIS_CSRF_TOKEN    = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
</script>
<script src="/js/zeugnis-fill.js"></script>
