<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
$typeLabels = ['krank' => 'krank', 'fortbildung' => 'Fortbildung', 'sonstiges' => 'sonstiges'];

// Navigation: Vor-/Zurück-Datum
$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
?>

<div class="page-header">
    <h1>Vertretungsplan: <?= htmlspecialchars($dayNames[$dayOfWeek] ?? '', ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(date('d.m.Y', strtotime($date)), ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="page-header-actions">
        <a href="/substitution/plan?date=<?= $prevDate ?>" class="btn btn-sm btn-secondary">&laquo; Zurück</a>
        <a href="/substitution/plan?date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-secondary">Heute</a>
        <a href="/substitution/plan?date=<?= $nextDate ?>" class="btn btn-sm btn-secondary">Vor &raquo;</a>
        <a href="/substitution/pdf?date=<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-secondary">PDF-Export</a>
        <a href="/substitution" class="btn btn-sm btn-secondary">Übersicht</a>
    </div>
</div>

<!-- Veröffentlichungsstatus -->
<div class="card sub-publish-bar">
    <?php if ($plan && $plan['is_published']): ?>
        <div class="sub-status sub-status-published">
            <span>Veröffentlicht am <?= htmlspecialchars(date('d.m.Y H:i', strtotime($plan['published_at'])), ENT_QUOTES, 'UTF-8') ?></span>
            <form method="post" action="/substitution/unpublish" style="display:inline;">
                <?= \OpenClassbook\View::csrfField() ?>
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-warning">Zurückziehen</button>
            </form>
        </div>
    <?php else: ?>
        <div class="sub-status sub-status-draft">
            <span>Entwurf – noch nicht veröffentlicht</span>
            <form method="post" action="/substitution/publish" style="display:inline;">
                <?= \OpenClassbook\View::csrfField() ?>
                <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-success">Veröffentlichen</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Abwesende Lehrkräfte -->
<?php if (!empty($absentTeachers)): ?>
<div class="card">
    <div class="card-header">
        <h2>Abwesende Lehrkräfte (<?= count($absentTeachers) ?>)</h2>
    </div>
    <div class="sub-absent-list">
        <?php foreach ($absentTeachers as $at): ?>
        <div class="sub-absent-item">
            <strong><?= htmlspecialchars($at['abbreviation'], ENT_QUOTES, 'UTF-8') ?></strong> –
            <?= htmlspecialchars($at['lastname'] . ', ' . $at['firstname'], ENT_QUOTES, 'UTF-8') ?>
            <span class="badge badge-warning"><?= htmlspecialchars($typeLabels[$at['absence_type']] ?? $at['absence_type'], ENT_QUOTES, 'UTF-8') ?></span>
            <small class="text-muted">
                (<?= htmlspecialchars(date('d.m.', strtotime($at['date_from'])), ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars(date('d.m.Y', strtotime($at['date_to'])), ENT_QUOTES, 'UTF-8') ?>)
            </small>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Zugewiesene Vertretungen -->
<div class="card" id="substitutionPlan"
     data-setting-id="<?= (int) $setting['id'] ?>"
     data-date="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-header">
        <h2>Vertretungen</h2>
    </div>

    <table class="table sub-table" id="subTable">
        <thead>
            <tr>
                <th>Einheit</th>
                <th>Klasse</th>
                <th>Fach</th>
                <th>Abwesend</th>
                <th>Vertretung</th>
                <th>Raum</th>
                <th>Hinweis</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody id="subTableBody">
            <?php foreach ($assignedSubstitutions as $s): ?>
            <tr class="sub-row <?= $s['is_cancelled'] ? 'sub-row-cancelled' : '' ?>" data-sub-id="<?= (int) $s['id'] ?>">
                <td>
                    <?= (int) $s['slot_number'] ?>.
                    <?php if (isset($timeSlots[$s['slot_number']])): ?>
                        <small>(<?= $timeSlots[$s['slot_number']]['from'] ?>)</small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($s['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($s['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(($s['absent_abbreviation'] ?? '') . ' ' . ($s['absent_lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if ($s['is_cancelled']): ?>
                        <span class="sub-cancelled-label">Entfall</span>
                    <?php elseif ($s['substitute_teacher_id']): ?>
                        <span class="sub-substitute-name">
                            <?= htmlspecialchars(($s['substitute_abbreviation'] ?? '') . ' ' . ($s['substitute_lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">–</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($s['room'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($s['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger sub-delete-btn" data-id="<?= (int) $s['id'] ?>">Entfernen</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Offene Slots -->
<?php if (!empty($openSlots)): ?>
<div class="card">
    <div class="card-header">
        <h2>Offene Vertretungen (<?= count($openSlots) ?>)</h2>
    </div>
    <table class="table sub-open-table" id="openSlotsTable">
        <thead>
            <tr>
                <th>Einheit</th>
                <th>Klasse</th>
                <th>Fach</th>
                <th>Abwesend</th>
                <th>Raum</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($openSlots as $os): ?>
            <tr class="open-slot-row"
                data-slot-number="<?= (int) $os['slot_number'] ?>"
                data-class-id="<?= (int) $os['class_id'] ?>"
                data-absent-teacher-id="<?= (int) $os['absent_teacher_id'] ?>"
                data-absence-id="<?= (int) ($os['absence_id'] ?? 0) ?>"
                data-subject="<?= htmlspecialchars($os['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                data-room="<?= htmlspecialchars($os['room'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <td>
                    <?= (int) $os['slot_number'] ?>.
                    <?php if (isset($timeSlots[$os['slot_number']])): ?>
                        <small>(<?= $timeSlots[$os['slot_number']]['from'] ?>)</small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($os['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($os['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(($os['absent_abbreviation'] ?? '') . ' ' . ($os['absent_lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($os['room'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary sub-assign-btn">Zuweisen</button>
                    <button type="button" class="btn btn-sm btn-warning sub-cancel-btn">Entfall</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif (empty($absentTeachers)): ?>
<div class="card">
    <p class="text-muted">Keine abwesenden Lehrkräfte an diesem Tag.</p>
</div>
<?php elseif (empty($openSlots) && !empty($assignedSubstitutions)): ?>
<div class="card">
    <p class="text-muted">Alle Vertretungen sind zugewiesen.</p>
</div>
<?php endif; ?>

<!-- Modal: Vertretung zuweisen -->
<div class="modal-overlay" id="subAssignModal" role="dialog" aria-modal="true" aria-labelledby="subAssignModalTitle" aria-hidden="true">
    <div class="modal">
        <h3 id="subAssignModalTitle">Vertretung zuweisen</h3>
        <div id="subConflictWarning" class="alert alert-warning" style="display: none;" role="alert"></div>

        <form id="subAssignForm">
            <input type="hidden" name="date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="slot_number" id="assignSlotNumber" value="">
            <input type="hidden" name="class_id" id="assignClassId" value="">
            <input type="hidden" name="absent_teacher_id" id="assignAbsentTeacherId" value="">
            <input type="hidden" name="absence_teacher_id" id="assignAbsenceId" value="">
            <?= \OpenClassbook\View::csrfField() ?>

            <div class="form-group">
                <label for="assignTeacher">Vertretungslehrkraft <span class="required">*</span></label>
                <select id="assignTeacher" name="substitute_teacher_id" class="form-control" required>
                    <option value="">Wird geladen...</option>
                </select>
                <small class="form-hint" id="assignTeacherHint"></small>
            </div>

            <div class="form-group">
                <label for="assignSubject">Fach</label>
                <input type="text" id="assignSubject" name="subject" class="form-control" maxlength="100">
            </div>

            <div class="form-group">
                <label for="assignRoom">Raum</label>
                <input type="text" id="assignRoom" name="room" class="form-control" maxlength="50">
            </div>

            <div class="form-group">
                <label for="assignNotes">Hinweis</label>
                <input type="text" id="assignNotes" name="notes" class="form-control" maxlength="255" placeholder="z.B. Aufgaben liegen bereit">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="subAssignCancel">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Zuweisen</button>
            </div>
        </form>
    </div>
</div>

<script src="/js/substitution-editor.js"></script>
