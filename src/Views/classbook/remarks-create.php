<div class="page-header">
    <h1>Neue Bemerkung</h1>
</div>
<p class="text-muted mb-1">Klasse: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <form method="post" action="/classbook/<?= $class['id'] ?>/remarks">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="student_id">Schueler/in <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select id="student_id" name="student_id" class="form-control" required>
                <option value="">— bitte waehlen —</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>">
                        <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="remark_date">Datum <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="date" id="remark_date" name="remark_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="remark">Bemerkung <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <textarea id="remark" name="remark" class="form-control" rows="6" required maxlength="2000" aria-describedby="remark_help"></textarea>
            <span class="form-help" id="remark_help">Max. 2000 Zeichen</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/classbook/<?= $class['id'] ?>/remarks" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
