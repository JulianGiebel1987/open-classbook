<div class="page-header">
    <h1>Schueler-Fehlzeit eintragen</h1>
</div>

<div class="card">
    <form method="post" action="/absences/students">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="class_id">Klasse waehlen <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="class_id" id="class_id" class="form-control" required>
                <option value="">- Klasse waehlen -</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($selectedClassId ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="student_id">Schueler/in <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="student_id" id="student_id" class="form-control" required disabled>
                <option value="">- Zuerst Klasse waehlen -</option>
            </select>
        </div>

        <div class="date-range-group">
            <div class="form-group flex-1">
                <label for="date_from">Von <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="date" id="date_from" name="date_from" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group flex-1">
                <label for="date_to">Bis <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="date" id="date_to" name="date_to" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="excused">Status <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="excused" id="excused" class="form-control" required>
                <option value="offen">Offen</option>
                <option value="ja">Entschuldigt</option>
                <option value="nein">Unentschuldigt</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reason">Grund</label>
            <input type="text" id="reason" name="reason" class="form-control" maxlength="500">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn" id="submitBtn" disabled>Eintragen</button>
            <a href="/absences/students" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<script src="/js/absence-create.js"></script>
