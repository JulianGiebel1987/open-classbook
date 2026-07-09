<div class="page-header">
    <h1>Pausenaufsichtsplan</h1>
    <div class="page-header-actions">
        <a href="/timetable" class="btn btn-secondary">Zur Stundenplanung</a>
        <a href="/supervision/settings" class="btn btn-primary">Neuer Pausenaufsichtsplan</a>
    </div>
</div>

<?php if (empty($plans)): ?>
    <div class="card">
        <p class="text-muted">Noch keine Pausenaufsichtspläne vorhanden.</p>
    </div>
<?php else: ?>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Schuljahr</th>
                    <th>Wochentage</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php $dayNames = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa']; ?>
                <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($p['school_year'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                        $days = is_array($p['days_of_week']) ? $p['days_of_week'] : [];
                        $dayLabels = array_map(fn($d) => $dayNames[$d] ?? $d, $days);
                        echo htmlspecialchars(implode(', ', $dayLabels), ENT_QUOTES, 'UTF-8');
                        ?>
                    </td>
                    <td>
                        <?php if ($p['is_published']): ?>
                            <span class="badge badge-success">Veröffentlicht</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Entwurf</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/supervision/settings?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <a href="/supervision/<?= (int) $p['id'] ?>" class="btn btn-sm btn-primary">Planen</a>
                            <a href="/supervision/<?= (int) $p['id'] ?>/pdf" class="btn btn-sm btn-secondary">PDF</a>
                            <?php if ($p['is_published']): ?>
                                <form method="post" action="/supervision/<?= (int) $p['id'] ?>/unpublish" style="display:inline;">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-warning">Zurückziehen</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/supervision/<?= (int) $p['id'] ?>/publish" style="display:inline;">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-success">Veröffentlichen</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
