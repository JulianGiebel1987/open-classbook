<h1>Import-Vorschau: Schueler/innen</h1>
<p style="color:var(--color-text-light);">Schuljahr: <?= htmlspecialchars($schoolYear, ENT_QUOTES, 'UTF-8') ?></p>

<?php if (!empty($preview['errors'])): ?>
<div class="alert alert-warning">
    <strong>Hinweise:</strong>
    <ul style="margin:0.5rem 0 0 1rem;">
        <?php foreach ($preview['errors'] as $err): ?>
            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Zeile</th>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>Klasse</th>
                    <th>Geburtsdatum</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview['rows'] as $row): ?>
                <tr>
                    <td><?= $row['row'] ?></td>
                    <td><?= htmlspecialchars($row['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['class_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $row['birthday'] ? date('d.m.Y', strtotime($row['birthday'])) : '-' ?></td>
                    <td>
                        <?php if (empty($row['errors'])): ?>
                            <span class="badge badge-success">OK</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?= htmlspecialchars(implode(', ', $row['errors']), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-1" style="display:flex; gap:0.5rem;">
    <form method="post" action="/import/students/confirm">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="stored_file" value="<?= htmlspecialchars($storedFile, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="school_year" value="<?= htmlspecialchars($schoolYear, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn" data-confirm="Import jetzt durchfuehren? Fehlerhafte Zeilen werden uebersprungen.">Import durchfuehren</button>
    </form>
    <a href="/import" class="btn btn-secondary">Abbrechen</a>
</div>
