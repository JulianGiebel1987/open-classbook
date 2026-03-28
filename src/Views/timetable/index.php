<div class="page-header">
    <h1>Stundenplanung</h1>
    <a href="/timetable/settings" class="btn btn-primary">Neuer Stundenplan</a>
</div>

<?php if (empty($settings)): ?>
    <div class="card">
        <p class="text-muted">Noch keine Stundenplan-Konfigurationen vorhanden.</p>
    </div>
<?php else: ?>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Schuljahr</th>
                    <th>Einheitsdauer</th>
                    <th>Einheiten/Tag</th>
                    <th>Beginn</th>
                    <th>Wochentage</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dayNames = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa'];
                ?>
                <?php foreach ($settings as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['school_year'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $s['unit_duration'] ?> Min.</td>
                    <td><?= (int) $s['units_per_day'] ?></td>
                    <td><?= htmlspecialchars(substr($s['day_start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                        $days = is_array($s['days_of_week']) ? $s['days_of_week'] : [];
                        $dayLabels = array_map(fn($d) => $dayNames[$d] ?? $d, $days);
                        echo htmlspecialchars(implode(', ', $dayLabels), ENT_QUOTES, 'UTF-8');
                        ?>
                    </td>
                    <td>
                        <?php if ($s['is_published']): ?>
                            <span class="badge badge-success">Veroeffentlicht</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Entwurf</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/timetable/settings?id=<?= (int) $s['id'] ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <a href="/timetable/<?= (int) $s['id'] ?>/class/select" class="btn btn-sm btn-primary">Planen</a>
                            <?php if ($s['is_published']): ?>
                                <form method="post" action="/timetable/<?= (int) $s['id'] ?>/unpublish" style="display:inline;">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-warning">Zurueckziehen</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/timetable/<?= (int) $s['id'] ?>/publish" style="display:inline;">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-success">Veroeffentlichen</button>
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
