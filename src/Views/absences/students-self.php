<div class="page-header">
    <h1>Krankmeldung</h1>
</div>

<div class="card">
    <p class="mb-1">
        Krankmeldung für: <strong><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES, 'UTF-8') ?></strong>
        (Klasse <?= htmlspecialchars($student['class_name'], ENT_QUOTES, 'UTF-8') ?>)
    </p>

    <form method="post" action="/absences/students/self">
        <?= \OpenClassbook\View::csrfField() ?>

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
            <label for="reason">Grund</label>
            <input type="text" id="reason" name="reason" class="form-control" maxlength="500" placeholder="z.B. Krank, Arzttermin">
        </div>

        <div class="form-group">
            <label for="notes">Notizen</label>
            <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Optionale Notiz..."></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Krankmeldung absenden</button>
            <a href="/dashboard" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
