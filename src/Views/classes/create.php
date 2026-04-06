<div class="page-header">
    <h1>Neue Klasse</h1>
</div>

<div class="card">
    <form method="post" action="/classes">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="name">Klassenbezeichnung <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="name" name="name" class="form-control" required placeholder="z.B. 5a, 7b">
        </div>

        <div class="form-group">
            <label for="school_year">Schuljahr <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="school_year" name="school_year" class="form-control" required placeholder="z.B. 2025/2026" pattern="\d{4}/\d{4}" aria-describedby="school_year_help">
            <span class="form-help" id="school_year_help">Format: JJJJ/JJJJ</span>
        </div>

        <div class="form-group">
            <label for="head_teacher_id">Klassenleitung</label>
            <select name="head_teacher_id" id="head_teacher_id" class="form-control">
                <option value="">- Keiner -</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <fieldset class="form-group fieldset-clean">
            <legend>Fachlehrer zuweisen</legend>
            <div class="checkbox-scroll">
                <?php foreach ($teachers as $t): ?>
                <label>
                    <input type="checkbox" name="teacher_ids[]" value="<?= $t['id'] ?>">
                    <?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="btn-group">
            <button type="submit" class="btn">Anlegen</button>
            <a href="/classes" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
