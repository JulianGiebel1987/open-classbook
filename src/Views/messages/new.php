<div class="page-header">
    <div>
        <h1>Neue Nachricht</h1>
    </div>
</div>

<?php
$roleLabels = [
    'admin' => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat' => 'Sekretariat',
    'lehrer' => 'Lehrkraft',
    'schueler' => 'Schüler:in',
];
?>

<div class="card">
    <form method="post" action="/messages/new" enctype="multipart/form-data">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="recipient_id">Empfänger <span aria-hidden="true">*</span></label>
            <select name="recipient_id" id="recipient_id" class="form-control" required>
                <option value="">-- Bitte wählen --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>">
                        <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?> (<?= $roleLabels[$u['role']] ?? $u['role'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="body">Nachricht</label>
            <textarea name="body" id="body" class="form-control" rows="5" maxlength="5000" placeholder="Ihre Nachricht..."></textarea>
        </div>

        <div class="form-group">
            <label for="attachments">Anhänge</label>
            <input type="file" id="attachments" name="attachments[]" multiple class="form-control">
            <span class="form-help">Bis zu 5 Dateien, je max. 15 MB. Nachricht oder mindestens ein Anhang ist erforderlich.</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Nachricht senden</button>
            <a href="/messages" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
