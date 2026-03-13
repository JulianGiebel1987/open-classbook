<h1>Import-Vorschau: Lehrkraefte</h1>

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
                    <th>Kuerzel</th>
                    <th>E-Mail</th>
                    <th>Faecher</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview['rows'] as $row): ?>
                <tr>
                    <td><?= $row['row'] ?></td>
                    <td><?= htmlspecialchars($row['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['abbreviation'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['subjects'], ENT_QUOTES, 'UTF-8') ?></td>
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
    <form method="post" action="/import/teachers/confirm">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="stored_file" value="<?= htmlspecialchars($storedFile, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn" data-confirm="Import jetzt durchfuehren? Fehlerhafte Zeilen werden uebersprungen.">Import durchfuehren</button>
    </form>
    <a href="/import" class="btn btn-secondary">Abbrechen</a>
</div>
