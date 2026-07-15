<?php
/** @var array $template */
/** @var array $categories */
/** @var bool $canManageShared */

$isShared = $template['owner_user_id'] === null;
?>
<div class="page-header">
    <h1>Vorlage bearbeiten</h1>
</div>

<div class="card">
    <form method="post" action="/content-templates/<?= (int) $template['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="category">Kategorie</label>
            <input type="text" id="category" name="category" class="form-control" list="category_suggestions"
                   maxlength="100" placeholder="z.B. Mathematik"
                   value="<?= htmlspecialchars($template['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="category_help">
            <span class="form-help" id="category_help">Optional – zum Gruppieren der Inhalte (Freitext).</span>
            <datalist id="category_suggestions">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"></option>
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="form-group">
            <label for="topic">Thema <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="topic" name="topic" class="form-control" required maxlength="500"
                   value="<?= htmlspecialchars($template['topic'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"><?= htmlspecialchars($template['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <?php if ($canManageShared): ?>
        <div class="form-group">
            <label for="visibility">Sichtbarkeit</label>
            <select id="visibility" name="visibility" class="form-control">
                <option value="personal" <?= !$isShared ? 'selected' : '' ?>>Persönlich (nur für mich)</option>
                <option value="shared" <?= $isShared ? 'selected' : '' ?>>Geteilt (schulweit für alle Lehrkräfte)</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/content-templates" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
