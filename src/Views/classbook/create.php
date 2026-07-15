<?php
/** @var array $class */
/** @var array $templates */

// Nach Kategorie gruppieren (leere Kategorie zuletzt) fuer die <optgroup>-Struktur.
$groupedTemplates = [];
foreach ($templates as $t) {
    $key = ($t['category'] ?? '') !== '' ? $t['category'] : '__none__';
    $groupedTemplates[$key][] = $t;
}
?>
<div class="page-header">
    <h1>Neuer Klassenbucheintrag</h1>
</div>
<p class="text-muted mb-1">Klasse: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <form method="post" action="/classbook/<?= $class['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <?php if (!empty($templates)): ?>
        <div class="form-group">
            <label for="template_picker">Vorlage einfügen</label>
            <select id="template_picker" class="form-control" aria-describedby="template_picker_help">
                <option value="">– Vorlage wählen –</option>
                <?php foreach ($groupedTemplates as $categoryKey => $items): ?>
                    <optgroup label="<?= $categoryKey === '__none__' ? 'Ohne Kategorie' : htmlspecialchars($categoryKey, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($items as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"
                                    data-topic="<?= htmlspecialchars($t['topic'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-notes="<?= htmlspecialchars($t['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($t['topic'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <span class="form-help" id="template_picker_help">
                Füllt Thema und Notizen mit einem Klick. <a href="/content-templates">Vorlagen verwalten</a>
            </span>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="entry_date">Datum <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="date" id="entry_date" name="entry_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="lesson">Unterrichtsstunde <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="number" id="lesson" name="lesson" class="form-control" required min="1" max="10" value="1" aria-describedby="lesson_help">
            <span class="form-help" id="lesson_help">Stunde 1 bis 10</span>
        </div>

        <div class="form-group">
            <label for="topic">Thema <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="topic" name="topic" class="form-control" required maxlength="500">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Eintragen</button>
            <a href="/classbook/<?= $class['id'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<?php if (!empty($templates)): ?>
<script src="<?= \OpenClassbook\View::asset('/js/classbook-template-picker.js') ?>"></script>
<?php endif; ?>
