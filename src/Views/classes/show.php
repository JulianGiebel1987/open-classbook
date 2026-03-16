<div class="page-header">
    <h1>Klasse <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <a href="/classbook/<?= $class['id'] ?>" class="btn">Klassenbuch</a>
        <a href="/classes/<?= $class['id'] ?>/edit" class="btn btn-secondary">Bearbeiten</a>
        <a href="/classes" class="btn btn-secondary">Zurueck</a>
    </div>
</div>

<p class="text-muted mb-1">Schuljahr: <?= htmlspecialchars($class['school_year'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <div class="card-header">
        <h2>Lehrkraefte</h2>
    </div>
    <?php if (empty($teachers)): ?>
        <p class="text-muted">Keine Lehrkraefte zugewiesen.</p>
    <?php else: ?>
        <ul class="list-unstyled">
            <?php foreach ($teachers as $t): ?>
                <li><?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Schueler/innen (<?= count($students) ?>)</h2>
    </div>
    <?php if (empty($students)): ?>
        <p class="text-muted">Keine Schueler in dieser Klasse.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table aria-label="Schuelerliste">
                <thead>
                    <tr>
                        <th scope="col">Nachname</th>
                        <th scope="col">Vorname</th>
                        <th scope="col">Geburtsdatum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $s['birthday'] ? date('d.m.Y', strtotime($s['birthday'])) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
