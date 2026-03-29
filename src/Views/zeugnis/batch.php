<div class="page-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/zeugnis/browse" class="btn btn-muted">← Zurück</a>
</div>

<div class="card mb-4">
    <form method="get" action="/zeugnis/batch/<?= (int) $template['id'] ?>" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="class_id">Klasse auswählen</label>
                <select name="class_id" id="class_id" class="form-control">
                    <option value="">Bitte auswählen …</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $selectedClassId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-group--align-end">
                <button type="submit" class="btn btn-secondary">Klasse laden</button>
            </div>
        </div>
    </form>
</div>

<?php if ($selectedClassId && !empty($students)): ?>
<div class="card">
    <form method="post" action="/zeugnis/batch/<?= (int) $template['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">

        <div class="table-responsive">
            <table aria-label="Schülerinnen und Schüler">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-cb"></th>
                        <th scope="col">Nachname</th>
                        <th scope="col">Vorname</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><input type="checkbox" name="student_ids[]" value="<?= (int) $s['id'] ?>" class="student-cb" checked></td>
                        <td><?= htmlspecialchars($s['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">
                Zeugnisse für Auswahl anlegen
            </button>
        </div>
    </form>
</div>
<?php elseif ($selectedClassId): ?>
<div class="card">
    <p class="text-center text-muted">Keine Schülerinnen/Schüler in dieser Klasse gefunden.</p>
</div>
<?php endif; ?>

<script>
document.getElementById('select-all-cb')?.addEventListener('change', function() {
    document.querySelectorAll('.student-cb').forEach(cb => cb.checked = this.checked);
});
</script>
