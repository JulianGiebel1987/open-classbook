<div class="page-header">
    <div>
        <a href="/zeugnis/<?= (int) $instance['id'] ?>/edit" class="btn btn-sm btn-secondary mb-05">← Zurück zum Dokument</a>
        <h1>Dokument teilen:
            <?= htmlspecialchars($instance['student_first_name'] . ' ' . $instance['student_last_name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>
</div>

<?php
$roleLabels = [
    'admin'       => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat' => 'Sekretariat',
    'lehrer'      => 'Lehrkraft',
];
?>

<div class="card mb-4">
    <h2 class="card-title">Person hinzufügen</h2>
    <?php if (empty($users)): ?>
        <p class="text-muted">Alle Personen haben bereits Zugriff.</p>
    <?php else: ?>
        <form method="post" action="/zeugnis/<?= (int) $instance['id'] ?>/share">
            <?= \OpenClassbook\View::csrfField() ?>
            <div class="list-inline-form-row">
                <select name="user_id" class="form-control" required>
                    <option value="">— Person auswählen —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>">
                            <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>
                            (<?= $roleLabels[$u['role']] ?? $u['role'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="checkbox-label">
                    <input type="checkbox" name="can_edit" value="1"> Bearbeiten erlauben
                </label>
                <button type="submit" class="btn btn-sm">Freigeben</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2 class="card-title">Aktuelle Freigaben</h2>
    <?php if (empty($shares)): ?>
        <p class="text-muted">Noch keine Freigaben vorhanden.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table aria-label="Freigaben">
                <thead>
                    <tr>
                        <th scope="col">Person</th>
                        <th scope="col">Rolle</th>
                        <th scope="col">Berechtigung</th>
                        <th scope="col">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shares as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $roleLabels[$s['role']] ?? $s['role'] ?></td>
                        <td>
                            <?php if ((int) $s['can_edit']): ?>
                                <span class="badge badge-success">Lesen + Bearbeiten</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Nur Lesen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="/zeugnis/<?= (int) $instance['id'] ?>/unshare" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $s['user_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Freigabe entfernen?">
                                    Entfernen
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
