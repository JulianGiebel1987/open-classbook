<div class="page-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/zeugnis/browse" class="btn btn-muted">← Zurück</a>
</div>

<div class="card">
    <form method="post" action="/zeugnis/create/<?= (int) $template['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="student_id">Schüler/in <span class="required">*</span></label>
            <select name="student_id" id="student_id" class="form-control" required>
                <option value="">Bitte auswählen …</option>
                <?php
                $byClass = [];
                foreach ($students as $s) {
                    $byClass[$s['class_name'] ?? 'Ohne Klasse'][] = $s;
                }
                foreach ($byClass as $className => $classStudents):
                ?>
                <optgroup label="<?= htmlspecialchars($className, ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($classStudents as $s): ?>
                    <option value="<?= (int) $s['id'] ?>">
                        <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Titel (optional)</label>
            <input type="text" name="title" id="title" class="form-control" maxlength="255"
                   placeholder="z.B. Halbjahreszeugnis 2025/26">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Zeugnis anlegen</button>
            <a href="/zeugnis/browse" class="btn btn-muted">Abbrechen</a>
        </div>
    </form>
</div>
