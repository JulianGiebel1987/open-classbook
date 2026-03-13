<h1>Klassenverwaltung</h1>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
        <a href="/classes/create" class="btn">Neue Klasse</a>
    </div>
    <form method="get" action="/classes" style="display:flex; gap:0.5rem; align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="school_year">Schuljahr</label>
            <select name="school_year" id="school_year" class="form-control">
                <option value="">Alle</option>
                <?php foreach ($schoolYears as $sy): ?>
                    <option value="<?= htmlspecialchars($sy['school_year'], ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['school_year'] ?? '') === $sy['school_year'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sy['school_year'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Klasse</th>
                    <th>Schuljahr</th>
                    <th>Klassenlehrer</th>
                    <th>Schueler</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr><td colspan="5" class="text-center">Keine Klassen gefunden.</td></tr>
                <?php endif; ?>
                <?php foreach ($classes as $c): ?>
                <tr>
                    <td><a href="/classes/<?= $c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><?= htmlspecialchars($c['school_year'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($c['head_teacher_lastname']): ?>
                            <?= htmlspecialchars($c['head_teacher_firstname'] . ' ' . $c['head_teacher_lastname'], ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>
                            <span style="color:var(--color-text-light)">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $c['student_count'] ?></td>
                    <td>
                        <a href="/classes/<?= $c['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                        <a href="/classbook/<?= $c['id'] ?>" class="btn btn-sm">Klassenbuch</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
