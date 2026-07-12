<?php
$roleLabels = [
    'admin'        => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat'  => 'Sekretariat',
    'lehrer'       => 'Lehrkraft',
    'schueler'     => 'Schüler:in',
];
?>
<div class="page-header">
    <h1>Benutzerverwaltung</h1>
    <div class="btn-group">
        <a href="/users/create" class="btn">Neuer Benutzer</a>
        <?php if (in_array(\OpenClassbook\App::currentUserRole(), ['admin', 'sekretariat'], true)): ?>
            <a href="/import" class="btn btn-secondary">Daten importieren</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Filter</h2>
    </div>
    <form method="get" action="/users" class="filter-form">
        <div class="form-group">
            <label for="role">Rolle</label>
            <select name="role" id="role" class="form-control">
                <option value="">Alle Rollen</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= ($filters['role'] ?? '') === $r ? 'selected' : '' ?>><?= $roleLabels[$r] ?? ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="search">Suche</label>
            <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Benutzername oder E-Mail">
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Benutzer</h2>
        <div class="form-group mb-0">
            <label for="tableSearchUsers" class="sr-only">Tabelle durchsuchen</label>
            <input type="text" id="tableSearchUsers" class="form-control" placeholder="Tabelle durchsuchen..." data-table-search="usersTable">
        </div>
    </div>
    <div class="table-responsive">
        <table id="usersTable" aria-label="Benutzerliste">
            <thead>
                <tr>
                    <th scope="col">Benutzername</th>
                    <th scope="col">E-Mail</th>
                    <th scope="col">Rolle</th>
                    <th scope="col">Status</th>
                    <th scope="col">Letzter Login</th>
                    <th scope="col">Aktionen</th>
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
                    <td><span class="badge badge-info"><?= htmlspecialchars($roleLabels[$u['role']] ?? ucfirst($u['role']), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                        <?php if ($u['active']): ?>
                            <span class="badge badge-success">Aktiv</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['last_login'] ? date('d.m.Y H:i', strtotime($u['last_login'])) : 'Nie' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="/users/<?= $u['id'] ?>/edit" class="btn btn-sm btn-icon-only btn-secondary" title="Bearbeiten" aria-label="Benutzer bearbeiten">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            </a>
                            <form method="post" action="/users/<?= $u['id'] ?>/toggle" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-icon-only <?= $u['active'] ? 'btn-danger' : 'btn-success' ?>" data-confirm="<?= $u['active'] ? 'Benutzer wirklich deaktivieren?' : 'Benutzer wieder aktivieren?' ?>" title="<?= $u['active'] ? 'Deaktivieren' : 'Aktivieren' ?>" aria-label="<?= $u['active'] ? 'Benutzer deaktivieren' : 'Benutzer aktivieren' ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                                </button>
                            </form>
                            <form method="post" action="/users/<?= $u['id'] ?>/reset-password" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-icon-only btn-secondary" data-confirm="Passwort wirklich zurücksetzen?" title="Passwort zurücksetzen" aria-label="Passwort zurücksetzen">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                                </button>
                            </form>
                            <?php if (!empty($u['email'])): ?>
                            <form method="post" action="/users/<?= $u['id'] ?>/email-password" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-icon-only btn-secondary" data-confirm="Neues Zufallspasswort erzeugen und an <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?> senden?" title="Passwort per E-Mail senden" aria-label="Neues Passwort per E-Mail senden">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (\OpenClassbook\App::currentUserRole() === 'admin' && (int) $u['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                            <form method="post" action="/users/<?= $u['id'] ?>/delete" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-icon-only btn-danger" data-confirm="ACHTUNG: Benutzer &quot;<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?>&quot; und alle zugehörigen Daten (Dateien, Nachrichten, Einträge) werden unwiderruflich gelöscht. Fortfahren?" title="Löschen" aria-label="Benutzer löschen">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
