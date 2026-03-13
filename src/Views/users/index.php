<h1>Benutzerverwaltung</h1>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
        <a href="/users/create" class="btn">Neuer Benutzer</a>
    </div>
    <form method="get" action="/users" style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="role">Rolle</label>
            <select name="role" id="role" class="form-control">
                <option value="">Alle Rollen</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= ($filters['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="search">Suche</label>
            <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Benutzername oder E-Mail">
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Benutzername</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th>Letzter Login</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center">Keine Benutzer gefunden.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($u['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-info"><?= ucfirst(htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8')) ?></span></td>
                    <td>
                        <?php if ($u['active']): ?>
                            <span class="badge badge-success">Aktiv</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['last_login'] ? date('d.m.Y H:i', strtotime($u['last_login'])) : 'Nie' ?></td>
                    <td style="white-space:nowrap;">
                        <a href="/users/<?= $u['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                        <form method="post" action="/users/<?= $u['id'] ?>/toggle" style="display:inline;">
                            <?= \OpenClassbook\View::csrfField() ?>
                            <button type="submit" class="btn btn-sm <?= $u['active'] ? 'btn-danger' : 'btn-success' ?>" data-confirm="<?= $u['active'] ? 'Benutzer wirklich deaktivieren?' : 'Benutzer wieder aktivieren?' ?>">
                                <?= $u['active'] ? 'Deaktivieren' : 'Aktivieren' ?>
                            </button>
                        </form>
                        <form method="post" action="/users/<?= $u['id'] ?>/reset-password" style="display:inline;">
                            <?= \OpenClassbook\View::csrfField() ?>
                            <button type="submit" class="btn btn-sm btn-secondary" data-confirm="Passwort wirklich zuruecksetzen?">PW Reset</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
