<div class="page-header">
    <h1>Schulbegleiter:innen</h1>
    <div class="page-header-actions">
        <a href="/import" class="btn btn-secondary">Importieren</a>
        <a href="/aides/create" class="btn">Neu anlegen</a>
    </div>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Schulbegleiter:innen">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Benutzername</th>
                    <th scope="col">Begleitete Schüler:innen</th>
                    <th scope="col">Kommentar</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aides)): ?>
                    <tr><td colspan="6" class="text-center">Keine Schulbegleiter:innen vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ($aides as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['lastname'] . ', ' . $a['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (empty($a['students'])): ?>
                            <span class="text-muted">–</span>
                        <?php else: ?>
                            <?php
                            $names = array_map(
                                fn($s) => $s['lastname'] . ', ' . $s['firstname'] . ' (' . $s['class_name'] . ')',
                                $a['students']
                            );
                            echo htmlspecialchars(implode('; ', $names), ENT_QUOTES, 'UTF-8');
                            ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?: '<span class="text-muted">–</span>' ?></td>
                    <td>
                        <?php if ((int) $a['active'] === 1): ?>
                            <span class="badge badge-success">Aktiv</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/aides/<?= $a['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <?php if (\OpenClassbook\App::currentUserRole() === 'admin'): ?>
                            <form method="post" action="/aides/<?= $a['id'] ?>/delete" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Schulbegleiter:in inkl. Konto endgültig löschen?">Löschen</button>
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
