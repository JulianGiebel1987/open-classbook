<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
?>

<div class="page-header">
    <h1>Meine Pausenaufsichten</h1>
</div>

<?php if (!$plan): ?>
    <div class="card">
        <p class="text-muted">Es ist aktuell kein Pausenaufsichtsplan veröffentlicht.</p>
    </div>
<?php elseif (empty($byDay)): ?>
    <div class="card">
        <p class="text-muted">Für Sie sind im aktuellen Pausenaufsichtsplan
            (<?= htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8') ?>) keine Aufsichten eingetragen.</p>
    </div>
<?php else: ?>
    <div class="card">
        <p class="text-muted">Plan: <?= htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars($plan['school_year'], ENT_QUOTES, 'UTF-8') ?>)
            | Gesamt: <strong><?= (int) $totalCount ?></strong> Aufsichten pro Woche</p>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Tag</th>
                    <th>Pause</th>
                    <th>Zeit</th>
                    <th>Aufsichtspunkt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byDay as $day => $entries): ?>
                    <?php foreach ($entries as $e): ?>
                    <tr>
                        <td><?= $dayNames[$day] ?? '' ?></td>
                        <td><?= htmlspecialchars($e['break_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($e['start_time'])): ?>
                                <?= htmlspecialchars(substr($e['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?><?php
                                if (!empty($e['end_time'])): ?>–<?= htmlspecialchars(substr($e['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            <?php else: ?>
                                –
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($e['location_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
