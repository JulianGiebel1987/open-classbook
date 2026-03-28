<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
?>

<div class="page-header">
    <h1>Stundenplan: <?= htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="page-header-actions">
        <?php if ($setting): ?>
        <a href="/timetable/<?= (int) $setting['id'] ?>/teacher/<?= (int) $teacher['id'] ?>/pdf"
           class="btn btn-secondary">PDF-Export</a>
        <?php endif; ?>
        <a href="/timetable" class="btn btn-secondary">Zurueck</a>
    </div>
</div>

<?php if (!empty($allSettings)): ?>
<div class="card">
    <form method="get" class="inline-form">
        <label for="settingSelect">Stundenplan:</label>
        <select id="settingSelect" name="setting_id" class="form-control"
                onchange="this.form.submit()">
            <?php foreach ($allSettings as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= $setting && (int) $s['id'] === (int) $setting['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['school_year'], ENT_QUOTES, 'UTF-8') ?>
                <?= $s['is_published'] ? '(veroeffentlicht)' : '(Entwurf)' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<?php endif; ?>

<?php if (!$setting): ?>
    <div class="card">
        <p class="text-muted">Kein Stundenplan vorhanden.</p>
    </div>
<?php elseif (empty($slotGrid)): ?>
    <div class="card">
        <p class="text-muted">Keine Einheiten fuer diese Lehrkraft eingetragen.</p>
    </div>
<?php else: ?>
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
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
