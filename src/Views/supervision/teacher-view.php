<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
$days = $plan['days_of_week'] ?? [];
$numBreaks = isset($breaks) ? count($breaks) : 0;
$teacherId = (int) ($teacherId ?? 0);
?>

<div class="page-header">
    <h1>Pausenaufsichtsplan</h1>
</div>

<?php if (!$plan): ?>
    <div class="card">
        <p class="text-muted">Es ist aktuell kein Pausenaufsichtsplan veröffentlicht.</p>
    </div>
<?php elseif ($numBreaks === 0 || empty($locations)): ?>
    <div class="card">
        <p class="text-muted">Der Pausenaufsichtsplan
            (<?= htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8') ?>) enthält noch keine Einträge.</p>
    </div>
<?php else: ?>
    <div class="card">
        <p class="text-muted" style="margin:0;">
            Plan: <?= htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= htmlspecialchars($plan['school_year'], ENT_QUOTES, 'UTF-8') ?>)
            &nbsp;|&nbsp; Ihre Aufsichten: <strong><?= (int) $ownCount ?></strong> pro Woche
            &nbsp;|&nbsp; Dies ist der festgelegte Plan (nur Ansicht). Ihre eigenen Aufsichten sind hervorgehoben.
        </p>
    </div>

    <div class="card timetable-view-card">
        <div class="timetable-grid-wrapper">
            <table class="table timetable-grid supervision-grid supervision-readonly">
                <thead>
                    <tr>
                        <th class="time-col" rowspan="2">Aufsichtspunkt</th>
                        <?php foreach ($days as $day): ?>
                        <th class="day-col supervision-day-col" colspan="<?= $numBreaks ?>"><?= $dayNames[$day] ?? '' ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <?php foreach ($breaks as $brk): ?>
                            <th class="supervision-break-col">
                                <?= htmlspecialchars($brk['label'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($brk['start_time'])): ?>
                                <br><small><?= htmlspecialchars(substr($brk['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?><?php
                                    if (!empty($brk['end_time'])): ?>–<?= htmlspecialchars(substr($brk['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?><?php endif; ?></small>
                                <?php endif; ?>
                            </th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $loc): ?>
                    <tr>
                        <td class="time-col supervision-location-cell">
                            <strong><?= htmlspecialchars($loc['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                        <?php foreach ($days as $day): ?>
                            <?php foreach ($breaks as $brk): ?>
                            <?php
                            $cellAssignments = $grid[$loc['id']][$day][$brk['id']] ?? [];
                            $hasOwn = false;
                            foreach ($cellAssignments as $a) {
                                if ((int) $a['teacher_id'] === $teacherId) {
                                    $hasOwn = true;
                                    break;
                                }
                            }
                            ?>
                            <td class="slot-cell supervision-cell<?= $hasOwn ? ' supervision-cell-own' : '' ?>">
                                <?php foreach ($cellAssignments as $a): ?>
                                    <?php $isOwn = (int) $a['teacher_id'] === $teacherId; ?>
                                    <div class="slot-entry slot-entry-readonly<?= $isOwn ? ' slot-entry-own' : '' ?>">
                                        <span class="slot-teacher"><?= htmlspecialchars($a['abbreviation'] ?? ($a['lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($cellAssignments)): ?>
                                    <span class="text-muted">–</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
