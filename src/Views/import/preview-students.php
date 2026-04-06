<div class="page-header">
    <h1>Import-Vorschau: Schüler:innen</h1>
</div>
<p class="text-muted mb-1">Schuljahr: <?= htmlspecialchars($schoolYear, ENT_QUOTES, 'UTF-8') ?></p>

<?php if (!empty($preview['errors'])): ?>
<div class="alert alert-warning" role="alert">
    <div>
        <strong>Hinweise:</strong>
        <ul class="preview-errors">
            <?php foreach ($preview['errors'] as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Import-Vorschau Schüler">
            <thead>
                <tr>
                    <th scope="col">Zeile</th>
                    <th scope="col">Vorname</th>
                    <th scope="col">Nachname</th>
                    <th scope="col">Klasse</th>
                    <th scope="col">Geburtsdatum</th>
                    <th scope="col">Status</th>
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

<div class="mt-1 btn-group">
    <form method="post" action="/import/students/confirm" class="d-inline">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="stored_file" value="<?= htmlspecialchars($storedFile, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="school_year" value="<?= htmlspecialchars($schoolYear, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn" data-confirm="Import jetzt durchführen? Fehlerhafte Zeilen werden übersprungen.">Import durchführen</button>
    </form>
    <a href="/import" class="btn btn-secondary">Abbrechen</a>
</div>
