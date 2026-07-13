<?php
$dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag'];
$days = $plan['days_of_week'];
$numBreaks = count($breaks);
?>

<div class="page-header">
    <h1>Pausenaufsichten: <?= htmlspecialchars($plan['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="page-header-actions">
        <a href="/supervision/settings?id=<?= (int) $plan['id'] ?>" class="btn btn-secondary">Plan-Einstellungen</a>
        <a href="/supervision/<?= (int) $plan['id'] ?>/pdf" class="btn btn-secondary">PDF-Export</a>
    </div>
</div>

<div class="card">
    <p class="text-muted" style="margin:0;">
        Schuljahr: <?= htmlspecialchars($plan['school_year'], ENT_QUOTES, 'UTF-8') ?>
        &nbsp;|&nbsp; Aufsichtspunkte (Zeilen) definieren, dann je Zelle Lehrkräfte zuweisen. Pro Zelle sind mehrere Lehrkräfte möglich.
    </p>
</div>

<?php if ($numBreaks === 0): ?>
    <div class="card">
        <p class="text-muted">Für diesen Plan sind noch keine Pausenspalten definiert.
        <a href="/supervision/settings?id=<?= (int) $plan['id'] ?>">Jetzt in den Plan-Einstellungen anlegen.</a></p>
    </div>
<?php else: ?>
<div class="timetable-layout">
    <div class="timetable-grid-wrapper">
        <table class="table timetable-grid supervision-grid" id="supervisionGrid"
               data-plan-id="<?= (int) $plan['id'] ?>">
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
                <?php if (empty($locations)): ?>
                <tr>
                    <td colspan="<?= ($numBreaks * count($days)) + 1 ?>" class="text-muted" style="text-align:center;">
                        Noch keine Aufsichtspunkte. Bitte unten einen Aufsichtspunkt hinzufügen.
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($locations as $loc): ?>
                <tr>
                    <td class="time-col supervision-location-cell">
                        <strong><?= htmlspecialchars($loc['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <form method="post" action="/supervision/location/<?= (int) $loc['id'] ?>/delete"
                              class="supervision-location-delete">
                            <?= \OpenClassbook\View::csrfField() ?>
                            <button type="submit" class="slot-remove" aria-label="Aufsichtspunkt entfernen" title="Aufsichtspunkt entfernen"
                                    data-confirm="Aufsichtspunkt &quot;<?= htmlspecialchars($loc['name'], ENT_QUOTES, 'UTF-8') ?>&quot; mit allen Zuweisungen entfernen?">&times;</button>
                        </form>
                    </td>
                    <?php foreach ($days as $day): ?>
                        <?php foreach ($breaks as $brk): ?>
                        <td class="slot-cell supervision-cell"
                            data-location="<?= (int) $loc['id'] ?>"
                            data-day="<?= (int) $day ?>"
                            data-break="<?= (int) $brk['id'] ?>">
                            <?php
                            $cellAssignments = $grid[$loc['id']][$day][$brk['id']] ?? [];
                            foreach ($cellAssignments as $a):
                            ?>
                            <div class="slot-entry" data-assignment-id="<?= (int) $a['id'] ?>" data-teacher-id="<?= (int) $a['teacher_id'] ?>">
                                <span class="slot-teacher"><?= htmlspecialchars($a['abbreviation'] ?? ($a['lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <button type="button" class="slot-remove" data-id="<?= (int) $a['id'] ?>"
                                        aria-label="Zuweisung entfernen" title="Entfernen">&times;</button>
                            </div>
                            <?php endforeach; ?>
                            <select class="supervision-select"
                                    data-location="<?= (int) $loc['id'] ?>"
                                    data-day="<?= (int) $day ?>"
                                    data-break="<?= (int) $brk['id'] ?>"
                                    aria-label="Lehrkraft für diesen Aufsichtspunkt auswählen">
                                <option value="">+ Lehrkraft&hellip;</option>
                                <?php foreach ($teachers as $t): ?>
                                <option value="<?= (int) $t['id'] ?>">
                                    <?= htmlspecialchars($t['abbreviation'] ?: ($t['lastname'] . ', ' . $t['firstname']), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Lehrer-Sidebar -->
    <aside class="timetable-sidebar" id="teacherSidebar">
        <h3>Lehrkräfte</h3>
        <input type="text" id="teacherSearch" class="form-control" placeholder="Suchen..." aria-label="Lehrkraft suchen">
        <ul class="teacher-list" id="teacherList">
            <?php foreach ($teachers as $t): ?>
            <li class="teacher-item" data-teacher-id="<?= (int) $t['id'] ?>"
                data-name="<?= htmlspecialchars(mb_strtolower($t['lastname'] . ' ' . $t['firstname'] . ' ' . $t['abbreviation']), ENT_QUOTES, 'UTF-8') ?>">
                <span class="teacher-name">
                    <?= htmlspecialchars($t['abbreviation'], ENT_QUOTES, 'UTF-8') ?> –
                    <?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="teacher-units badge" id="teacherCount-<?= (int) $t['id'] ?>">
                    <?= (int) ($teacherCounts[$t['id']] ?? 0) ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </aside>
</div>
<?php endif; ?>

<!-- Aufsichtspunkt hinzufügen -->
<div class="card">
    <form method="post" action="/supervision/location" class="inline-form" style="display:flex; gap:0.5rem; align-items:flex-end;">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
        <div class="form-group" style="margin:0;">
            <label for="newLocation">Neuer Aufsichtspunkt</label>
            <input type="text" id="newLocation" name="name" class="form-control" maxlength="120"
                   placeholder="z.B. Tor, Sandkasten, Rutsche" required>
        </div>
        <button type="submit" class="btn btn-primary">Hinzufügen</button>
    </form>
</div>

<script src="<?= \OpenClassbook\View::asset('/js/supervision-editor.js') ?>"></script>
