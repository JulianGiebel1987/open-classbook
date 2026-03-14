<div class="page-header">
    <h1>Import-Vorschau: Lehrkraefte</h1>
</div>

<?php if (!empty($preview['errors'])): ?>
<div class="alert alert-warning" role="alert">
    <div>
        <strong>Hinweise:</strong>
        <ul style="margin:0.5rem 0 0 1rem;">
            <?php foreach ($preview['errors'] as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Import-Vorschau Lehrkraefte">
            <thead>
                <tr>
                    <th scope="col">Zeile</th>
                    <th scope="col">Vorname</th>
                    <th scope="col">Nachname</th>
                    <th scope="col">Kuerzel</th>
                    <th scope="col">E-Mail</th>
                    <th scope="col">Faecher</th>
                    <th scope="col">Status</th>
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

<div class="mt-1 btn-group">
    <form method="post" action="/import/teachers/confirm" class="d-inline">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="stored_file" value="<?= htmlspecialchars($storedFile, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn" data-confirm="Import jetzt durchfuehren? Fehlerhafte Zeilen werden uebersprungen.">Import durchfuehren</button>
    </form>
    <a href="/import" class="btn btn-secondary">Abbrechen</a>
</div>
