<div class="page-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/zeugnis/templates" class="btn btn-muted">← Zurück</a>
</div>

<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" id="zeugnis-editor-form">
    <?= \OpenClassbook\View::csrfField() ?>
    <input type="hidden" name="template_canvas" id="template-canvas-input" value="">

    <!-- Metadaten -->
    <div class="card mb-4">
        <h2 class="card-title">Vorlage Einstellungen</h2>
        <div class="form-row">
            <div class="form-group form-group--lg">
                <label for="name">Name <span class="required">*</span></label>
                <input type="text" name="name" id="name" class="form-control" required maxlength="255"
                       value="<?= htmlspecialchars($template['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="school_year">Schuljahr</label>
                <input type="text" name="school_year" id="school_year" class="form-control" maxlength="9"
                       placeholder="2025/2026"
                       value="<?= htmlspecialchars($template['school_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="grade_levels">Klassenstufen</label>
                <input type="text" name="grade_levels" id="grade_levels" class="form-control" maxlength="255"
                       placeholder="z.B. 1,2,3"
                       value="<?= htmlspecialchars($template['grade_levels'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="page_format">Seitenformat</label>
                <select name="page_format" id="page_format" class="form-control">
                    <option value="A4" <?= ($template['page_format'] ?? 'A4') === 'A4' ? 'selected' : '' ?>>DIN A4</option>
                    <option value="A3" <?= ($template['page_format'] ?? '') === 'A3' ? 'selected' : '' ?>>DIN A3</option>
                </select>
            </div>
            <div class="form-group">
                <label for="page_orientation">Ausrichtung</label>
                <select name="page_orientation" id="page_orientation" class="form-control">
                    <option value="P" <?= ($template['page_orientation'] ?? 'P') === 'P' ? 'selected' : '' ?>>Hochformat</option>
                    <option value="L" <?= ($template['page_orientation'] ?? '') === 'L' ? 'selected' : '' ?>>Querformat</option>
                </select>
            </div>
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <input type="text" name="description" id="description" class="form-control" maxlength="500"
                       value="<?= htmlspecialchars($template['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
    </div>

    <!-- Editor -->
    <div class="zeugnis-editor-layout">

        <!-- Toolbar -->
        <div class="zeugnis-editor-toolbar">
            <button type="button" id="btn-toggle-grid" class="btn btn-sm btn-secondary" title="Raster ein/ausblenden">
                Raster
            </button>
            <button type="button" id="btn-add-page" class="btn btn-sm btn-secondary" title="Seite hinzufügen">
                + Seite
            </button>
            <button type="button" id="btn-remove-page" class="btn btn-sm btn-muted" title="Aktuelle Seite entfernen">
                − Seite
            </button>
            <span class="toolbar-sep">|</span>
            <span id="page-indicator" class="text-muted" style="font-size:var(--font-size-sm)">Seite 1 / 1</span>
            <span class="toolbar-sep">|</span>
            <button type="button" id="btn-prev-page" class="btn btn-sm btn-muted">◀</button>
            <button type="button" id="btn-next-page" class="btn btn-sm btn-muted">▶</button>
            <span class="toolbar-sep" style="flex:1"></span>
            <button type="submit" class="btn btn-primary" id="btn-save">Speichern</button>
        </div>

        <!-- Element-Palette -->
        <div class="zeugnis-palette">
            <h3 class="palette-title">Elemente</h3>

            <p class="palette-section-label">Inhalt</p>
            <div class="zeugnis-palette-item" draggable="true" data-type="text_static">
                <span class="palette-icon">T</span> Statischer Text
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="placeholder">
                <span class="palette-icon">⊕</span> Platzhalter
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="image">
                <span class="palette-icon">🖼</span> Bild / Logo
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="divider">
                <span class="palette-icon">—</span> Trennlinie
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="table">
                <span class="palette-icon">⊞</span> Tabelle
            </div>

            <p class="palette-section-label">Felder</p>
            <div class="zeugnis-palette-item" draggable="true" data-type="text_free">
                <span class="palette-icon">✎</span> Freitextfeld
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="grade">
                <span class="palette-icon">①</span> Notenfeld
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="checkbox">
                <span class="palette-icon">☐</span> Checkbox
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="date">
                <span class="palette-icon">📅</span> Datumsfeld
            </div>
            <div class="zeugnis-palette-item" draggable="true" data-type="signature">
                <span class="palette-icon">✒</span> Unterschriftsfeld
            </div>
        </div>

        <!-- Canvas-Wrapper -->
        <div class="zeugnis-canvas-wrapper" id="canvas-wrapper">
            <div class="zeugnis-canvas" id="zeugnis-canvas"></div>
        </div>

        <!-- Eigenschaften-Panel -->
        <div class="zeugnis-props-panel" id="props-panel">
            <p class="text-muted" style="font-size:var(--font-size-sm)">
                Element auswählen zum Bearbeiten.
            </p>

            <!-- Token-Liste für Platzhalter -->
            <div id="token-list-section" style="display:none">
                <h3>Verfügbare Platzhalter</h3>
                <p style="font-size:var(--font-size-xs);color:var(--color-muted)">Klicken zum Einfügen</p>
                <ul class="zeugnis-token-list">
                    <?php foreach ($tokens as $token => $label): ?>
                        <li data-token="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Bild-Upload-Bereich -->
            <?php if ($template): ?>
            <div id="image-upload-section" style="display:none;margin-top:var(--spacing-md)">
                <h3>Bild hochladen</h3>
                <input type="file" id="image-upload-input" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
                       style="margin-bottom:var(--spacing-sm)">
                <button type="button" id="btn-upload-image" class="btn btn-sm btn-secondary">Hochladen</button>
                <p id="image-upload-status" style="font-size:var(--font-size-xs);margin-top:4px"></p>

                <?php if (!empty($images)): ?>
                <p class="palette-section-label" style="margin-top:var(--spacing-sm)">Vorhandene Bilder</p>
                <div id="image-gallery">
                    <?php foreach ($images as $img): ?>
                    <div class="image-gallery-item" data-image-id="<?= (int) $img['id'] ?>">
                        <img src="/zeugnis/images/<?= (int) $img['id'] ?>"
                             alt="<?= htmlspecialchars($img['original_name'], ENT_QUOTES, 'UTF-8') ?>"
                             title="<?= htmlspecialchars($img['original_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.zeugnis-editor-layout -->
</form>

<script>
var ZEUGNIS_INITIAL_STATE = <?= $canvasJson ?>;
var ZEUGNIS_TEMPLATE_ID   = <?= $template ? (int) $template['id'] : 'null' ?>;
var ZEUGNIS_CSRF_TOKEN    = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
var ZEUGNIS_IMAGE_UPLOAD_URL = <?= $template ? json_encode('/zeugnis/templates/' . (int) $template['id'] . '/images') : 'null' ?>;
</script>
<script src="/js/zeugnis-editor.js"></script>
