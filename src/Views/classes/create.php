<h1>Neue Klasse</h1>

<div class="card">
    <form method="post" action="/classes">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="name">Klassenbezeichnung *</label>
            <input type="text" id="name" name="name" class="form-control" required placeholder="z.B. 5a, 7b">
        </div>

        <div class="form-group">
            <label for="school_year">Schuljahr *</label>
            <input type="text" id="school_year" name="school_year" class="form-control" required placeholder="z.B. 2025/2026">
        </div>

        <div class="form-group">
            <label for="head_teacher_id">Klassenlehrer</label>
            <select name="head_teacher_id" id="head_teacher_id" class="form-control">
                <option value="">- Keiner -</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Fachlehrer zuweisen</label>
            <div style="max-height:200px; overflow-y:auto; border:1px solid var(--color-border); border-radius:var(--radius); padding:0.5rem;">
                <?php foreach ($teachers as $t): ?>
                <label style="display:block; padding:2px 0; cursor:pointer;">
                    <input type="checkbox" name="teacher_ids[]" value="<?= $t['id'] ?>">
                    <?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Anlegen</button>
            <a href="/classes" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
