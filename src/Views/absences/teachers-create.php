<div class="page-header">
    <h1>Lehrer-Abwesenheit eintragen</h1>
</div>

<div class="card">
    <form method="post" action="/absences/teachers">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="teacher_id">Lehrkraft <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="teacher_id" id="teacher_id" class="form-control" required>
                <option value="">- Waehlen -</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-form">
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
            <label for="type">Typ <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="type" id="type" class="form-control" required>
                <option value="krank">Krank</option>
                <option value="fortbildung">Fortbildung</option>
                <option value="sonstiges">Sonstiges</option>
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
            <button type="submit" class="btn">Eintragen</button>
            <a href="/absences/teachers" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
