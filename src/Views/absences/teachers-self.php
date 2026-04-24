<div class="page-header">
    <h1>Lehrkraft-Abwesenheit</h1>
</div>

<div class="card">
    <form method="post" action="/absences/teachers/self">
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
            <label for="type">Typ <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="type" id="type" class="form-control" required>
                <option value="krank">Krank</option>
                <option value="fortbildung">Fortbildung</option>
                <option value="sonstiges">Sonstiges</option>
            </select>
        </div>

        <div class="form-group">
            <label for="notes">Notiz</label>
            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optionale Notiz..."></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Lehrkraft-Abwesenheit absenden</button>
            <a href="/dashboard" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
