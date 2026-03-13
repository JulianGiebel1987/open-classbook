<h1>Klasse <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
<p style="color:var(--color-text-light);">Schuljahr: <?= htmlspecialchars($class['school_year'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card mt-1">
    <div class="card-header">
        <h2>Lehrkraefte</h2>
    </div>
    <?php if (empty($teachers)): ?>
        <p style="color:var(--color-text-light);">Keine Lehrkraefte zugewiesen.</p>
    <?php else: ?>
        <ul style="list-style:none;">
            <?php foreach ($teachers as $t): ?>
                <li style="padding:4px 0;"><?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Schueler/innen (<?= count($students) ?>)</h2>
    </div>
    <?php if (empty($students)): ?>
        <p style="color:var(--color-text-light);">Keine Schueler in dieser Klasse.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nachname</th>
                        <th>Vorname</th>
                        <th>Geburtsdatum</th>
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

<div class="mt-1" style="display:flex; gap:0.5rem;">
    <a href="/classbook/<?= $class['id'] ?>" class="btn">Klassenbuch</a>
    <a href="/classes/<?= $class['id'] ?>/edit" class="btn btn-secondary">Bearbeiten</a>
    <a href="/classes" class="btn btn-secondary">Zurueck</a>
</div>
