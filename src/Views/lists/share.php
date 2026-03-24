<div class="page-header">
    <div>
        <a href="/lists/<?= (int) $list['id'] ?>" class="btn btn-sm btn-secondary mb-05">Zurueck zur Liste</a>
        <h1>Freigabe: <?= htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
</div>

<?php
$roleLabels = [
    'admin' => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat' => 'Sekretariat',
    'lehrer' => 'Lehrer/in',
];
?>

<div class="card mb-1">
    <div class="card-header">
        <h2>Nutzer hinzufuegen</h2>
    </div>
    <?php if (empty($availableUsers)): ?>
        <p class="text-muted">Alle Nutzer haben bereits Zugriff.</p>
    <?php else: ?>
        <form method="post" action="/lists/<?= (int) $list['id'] ?>/share">
            <?= \OpenClassbook\View::csrfField() ?>
            <div class="list-inline-form-row">
                <select name="user_id" class="form-control" required>
                    <option value="">— Nutzer waehlen —</option>
                    <?php foreach ($availableUsers as $u): ?>
                        <option value="<?= (int) $u['id'] ?>">
                            <?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?> (<?= $roleLabels[$u['role']] ?? $u['role'] ?>)
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
    <div class="card-header">
        <h2>Aktuelle Freigaben</h2>
    </div>
    <?php if (empty($shares)): ?>
        <p class="text-muted">Noch keine Freigaben vorhanden.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table aria-label="Freigaben">
                <thead>
                    <tr>
                        <th scope="col">Nutzer</th>
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
                            <form method="post" action="/lists/<?= (int) $list['id'] ?>/unshare" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $s['user_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Freigabe entfernen?">Entfernen</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
