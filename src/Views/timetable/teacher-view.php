<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
?>

<div class="page-header">
    <h1>Mein Stundenplan</h1>
    <?php if ($setting && !empty($teacherId)): ?>
    <a href="/timetable/<?= (int) $setting['id'] ?>/teacher/<?= (int) $teacherId ?>/pdf"
       class="btn btn-secondary">Als PDF herunterladen</a>
    <?php endif; ?>
</div>

<?php if (!$setting): ?>
    <div class="card">
        <p class="text-muted">Es ist aktuell kein Stundenplan veroeffentlicht.</p>
    </div>
<?php elseif (empty($slotGrid)): ?>
    <div class="card">
        <p class="text-muted">Fuer Sie sind im aktuellen Stundenplan (<?= htmlspecialchars($setting['school_year'], ENT_QUOTES, 'UTF-8') ?>) noch keine Einheiten eingetragen.</p>
    </div>
<?php else: ?>
    <div class="card">
        <p class="text-muted">Schuljahr: <?= htmlspecialchars($setting['school_year'], ENT_QUOTES, 'UTF-8') ?>
            | Einheitsdauer: <?= (int) $setting['unit_duration'] ?> Min.</p>
    </div>

    <div class="card timetable-view-card">
        <table class="table timetable-grid timetable-readonly">
            <thead>
                <tr>
                    <th class="time-col">Zeit</th>
                    <?php foreach ($setting['days_of_week'] as $day): ?>
                    <th class="day-col"><?= $dayNames[$day] ?? '' ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeSlots as $slotNum => $time): ?>
                <tr>
                    <td class="time-col">
                        <strong><?= $slotNum ?>.</strong><br>
                        <small><?= htmlspecialchars($time['from'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($time['to'], ENT_QUOTES, 'UTF-8') ?></small>
                    </td>
                    <?php foreach ($setting['days_of_week'] as $day): ?>
                    <td class="slot-cell">
                        <?php
                        $cellSlots = $slotGrid[$day][$slotNum] ?? [];
                        foreach ($cellSlots as $cs):
                        ?>
                        <div class="slot-entry slot-entry-readonly">
                            <span class="slot-class"><?= htmlspecialchars($cs['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($cs['subject'])): ?>
                                <span class="slot-subject"><?= htmlspecialchars($cs['subject'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($cs['room'])): ?>
                                <span class="slot-room">(<?= htmlspecialchars($cs['room'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($cellSlots)): ?>
                            <span class="text-muted">–</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php if (!empty($time['break_after'])): ?>
                <tr class="timetable-break-row">
                    <td colspan="<?= count($setting['days_of_week']) + 1 ?>">
                        <small><?= htmlspecialchars($time['break_after']['label'], ENT_QUOTES, 'UTF-8') ?>
                        (<?= (int) $time['break_after']['duration'] ?> Min.)</small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
