<div class="page-header">
    <div>
        <a href="/messages" class="btn btn-sm btn-secondary mb-05">Zurueck</a>
        <h1>Neue Nachricht</h1>
    </div>
</div>

<?php
$roleLabels = [
    'admin' => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat' => 'Sekretariat',
    'lehrer' => 'Lehrer/in',
    'schueler' => 'Schueler/in',
];
?>

<div class="card">
    <form method="post" action="/messages/new">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="recipient_id">Empfaenger <span aria-hidden="true">*</span></label>
            <select name="recipient_id" id="recipient_id" class="form-control" required>
                <option value="">-- Bitte waehlen --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>">
                        <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?> (<?= $roleLabels[$u['role']] ?? $u['role'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="body">Nachricht <span aria-hidden="true">*</span></label>
            <textarea name="body" id="body" class="form-control" rows="5" required maxlength="5000" placeholder="Ihre Nachricht..."></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Nachricht senden</button>
            <a href="/messages" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
