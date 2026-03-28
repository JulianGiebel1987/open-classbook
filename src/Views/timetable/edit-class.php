<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
$days = $setting['days_of_week'];
?>

<div class="page-header">
    <h1>Stundenplan: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="page-header-actions">
        <form method="get" action="" class="inline-form" id="classSelectForm">
            <label for="classSelect" class="sr-only">Klasse waehlen</label>
            <select id="classSelect" class="form-control" onchange="switchClass(this.value)">
                <?php foreach ($classes as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $class['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="/timetable/<?= (int) $setting['id'] ?>/class/<?= (int) $class['id'] ?>/pdf"
           class="btn btn-secondary">PDF-Export</a>
        <a href="/timetable" class="btn btn-secondary">Zurueck</a>
    </div>
</div>

<div class="timetable-layout">
    <!-- Hauptraster -->
    <div class="timetable-grid-wrapper">
        <table class="table timetable-grid" id="timetableGrid"
               data-setting-id="<?= (int) $setting['id'] ?>"
               data-class-id="<?= (int) $class['id'] ?>">
            <thead>
                <tr>
                    <th class="time-col">Zeit</th>
                    <?php foreach ($days as $day): ?>
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
                    <?php foreach ($days as $day): ?>
                    <td class="slot-cell" data-day="<?= $day ?>" data-slot="<?= $slotNum ?>">
                        <?php
                        $cellSlots = $slotGrid[$day][$slotNum] ?? [];
                        foreach ($cellSlots as $cs):
                        ?>
                        <div class="slot-entry" data-slot-id="<?= (int) $cs['id'] ?>" data-teacher-id="<?= (int) $cs['teacher_id'] ?>">
                            <span class="slot-teacher"><?= htmlspecialchars($cs['abbreviation'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($cs['subject'])): ?>
                                <span class="slot-subject"><?= htmlspecialchars($cs['subject'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <button type="button" class="slot-remove" data-id="<?= (int) $cs['id'] ?>"
                                    aria-label="Eintrag entfernen" title="Entfernen">&times;</button>
                        </div>
                        <?php endforeach; ?>
                        <button type="button" class="slot-add-btn" data-day="<?= $day ?>" data-slot="<?= $slotNum ?>"
                                aria-label="Lehrkraft hinzufuegen" title="Lehrkraft hinzufuegen">+</button>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Lehrer-Sidebar -->
    <aside class="timetable-sidebar" id="teacherSidebar">
        <h3>Lehrkraefte</h3>
        <input type="text" id="teacherSearch" class="form-control" placeholder="Suchen..." aria-label="Lehrkraft suchen">
        <ul class="teacher-list" id="teacherList">
            <?php foreach ($teachers as $t): ?>
            <li class="teacher-item" data-teacher-id="<?= (int) $t['id'] ?>"
                data-name="<?= htmlspecialchars(mb_strtolower($t['lastname'] . ' ' . $t['firstname'] . ' ' . $t['abbreviation']), ENT_QUOTES, 'UTF-8') ?>">
                <span class="teacher-name">
                    <?= htmlspecialchars($t['abbreviation'], ENT_QUOTES, 'UTF-8') ?> –
                    <?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="teacher-units badge" id="teacherUnits-<?= (int) $t['id'] ?>">
                    <?= (int) ($teacherUnitCounts[$t['id']] ?? 0) ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>
</div>

<!-- Modal: Slot hinzufuegen -->
<div class="modal-overlay" id="slotModal" role="dialog" aria-modal="true" aria-labelledby="slotModalTitle" aria-hidden="true">
    <div class="modal">
        <h3 id="slotModalTitle">Lehrkraft zuweisen</h3>
        <div id="slotConflictWarning" class="alert alert-warning" style="display: none;" role="alert"></div>

        <form id="slotForm">
            <input type="hidden" name="timetable_setting_id" value="<?= (int) $setting['id'] ?>">
            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
            <input type="hidden" name="day_of_week" id="slotDay" value="">
            <input type="hidden" name="slot_number" id="slotNumber" value="">
            <?= \OpenClassbook\View::csrfField() ?>

            <div class="form-group">
                <label for="slotTeacher">Lehrkraft <span class="required">*</span></label>
                <select id="slotTeacher" name="teacher_id" class="form-control" required>
                    <option value="">– Lehrkraft waehlen –</option>
                    <?php foreach ($teachers as $t): ?>
                    <option value="<?= (int) $t['id'] ?>">
                        <?= htmlspecialchars($t['abbreviation'] . ' – ' . $t['lastname'] . ', ' . $t['firstname'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="slotSubject">Fach</label>
                <input type="text" id="slotSubject" name="subject" class="form-control" maxlength="100">
            </div>

            <div class="form-group">
                <label for="slotRoom">Raum</label>
                <input type="text" id="slotRoom" name="room" class="form-control" maxlength="50">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="slotModalCancel">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Zuweisen</button>
            </div>
        </form>
    </div>
</div>

<script src="/js/timetable-editor.js"></script>
