<?php $role = \OpenClassbook\App::currentUserRole(); ?>

<div class="page-header">
    <div>
        <h1>Klassenbuch: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (!empty($class['school_year'])): ?>
            <p class="page-subtitle">Schuljahr <?= htmlspecialchars($class['school_year'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <div class="btn-group">
        <?php if ($role === 'admin' || $role === 'lehrer'): ?>
            <a href="/classbook/<?= $class['id'] ?>/create" class="btn">Neuer Eintrag</a>
        <?php endif; ?>
        <a href="/classbook/<?= $class['id'] ?>/students" class="btn btn-secondary">Schülerakten</a>
        <a href="/absences/students?class_id=<?= $class['id'] ?>" class="btn btn-secondary">Schüler:innen-Fehlzeiten</a>
        <a href="/classbook/<?= $class['id'] ?>/remarks" class="btn btn-secondary">Schüler:innen-Bemerkungen</a>
        <a href="/classbook/<?= $class['id'] ?>/export-csv?date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>" class="btn btn-secondary">CSV Export</a>
        <a href="/classbook/<?= $class['id'] ?>/export-pdf?date_from=<?= urlencode($filters['date_from'] ?? '') ?>&date_to=<?= urlencode($filters['date_to'] ?? '') ?>" class="btn btn-secondary">PDF Export</a>
    </div>
</div>

<!-- Kennzahlen-Übersicht -->
<div class="dashboard-grid">
    <div class="widget">
        <div class="widget-value"><?= (int) $stats['students'] ?></div>
        <div class="widget-label">Schüler:innen</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= (int) $stats['entries'] ?></div>
        <div class="widget-label">Einträge</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= (int) $stats['absence_days'] ?></div>
        <div class="widget-label">Fehltage (Schuljahr)</div>
    </div>
    <div class="widget">
        <div class="widget-value"><?= (int) $stats['open_days'] ?></div>
        <div class="widget-label">Offene Fehltage</div>
    </div>
</div>

<!-- Klassenleitung & Lehrkräfte -->
<div class="card mt-1">
    <div class="card-header">
        <h2>Klassenleitung &amp; Lehrkräfte</h2>
    </div>
    <p class="mb-0">
        <strong>Klassenleitung:</strong>
        <?php if ($headTeacher): ?>
            <?= htmlspecialchars($headTeacher['firstname'] . ' ' . $headTeacher['lastname'], ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($headTeacher['abbreviation'])): ?>
                (<?= htmlspecialchars($headTeacher['abbreviation'], ENT_QUOTES, 'UTF-8') ?>)
            <?php endif; ?>
        <?php else: ?>
            <span class="text-muted">nicht festgelegt</span>
        <?php endif; ?>
    </p>
    <p class="mb-0 mt-1">
        <strong>Fachlehrkräfte:</strong>
        <?php if (empty($teachers)): ?>
            <span class="text-muted">keine zugeordnet</span>
        <?php else: ?>
            <?php
            $teacherLabels = array_map(static function ($t) {
                $name = trim(($t['firstname'] ?? '') . ' ' . ($t['lastname'] ?? ''));
                $abbr = $t['abbreviation'] ?? '';
                return $abbr !== '' ? $name . ' (' . $abbr . ')' : $name;
            }, $teachers);
            ?>
            <?= htmlspecialchars(implode(', ', $teacherLabels), ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>
</div>

<!-- Schülerübersicht mit Fehltagen -->
<div class="card mt-1">
    <div class="card-header">
        <h2>Schülerübersicht</h2>
    </div>
    <?php if (empty($students)): ?>
        <p class="text-muted text-center">Keine Schüler:innen in dieser Klasse.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="classbook-roster" aria-label="Schülerübersicht der Klasse <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?>">
            <thead>
                <tr>
                    <th scope="col" style="width:2.5rem;">Nr.</th>
                    <th scope="col">Name</th>
                    <th scope="col">Geb.</th>
                    <th scope="col">Kontakt</th>
                    <th scope="col" style="text-align:right;">Fehltage</th>
                    <th scope="col" style="text-align:right;">Ents.</th>
                    <th scope="col" style="text-align:right;">Unents.</th>
                    <th scope="col" style="text-align:right;">Offen</th>
                    <th scope="col" style="text-align:right;">Bemerk.</th>
                </tr>
            </thead>
            <tbody>
                <?php $nr = 1; ?>
                <?php foreach ($students as $s): ?>
                <?php
                    $sid   = (int) $s['id'];
                    $abs   = $absenceMap[$sid] ?? ['ja' => 0, 'nein' => 0, 'offen' => 0, 'total' => 0];
                    $rem   = $remarkCounts[$sid] ?? 0;
                    $recordUrl = '/classbook/' . (int) $class['id'] . '/students/' . $sid;

                    $birthdayText = '–';
                    if (!empty($s['birthday'])) {
                        $bts = strtotime($s['birthday']);
                        $age = (new \DateTime($s['birthday']))->diff(new \DateTime('today'))->y;
                        $birthdayText = date('d.m.Y', $bts) . ' (' . $age . ')';
                    }
                ?>
                <tr>
                    <td><?= $nr++ ?></td>
                    <td>
                        <a href="<?= $recordUrl ?>">
                            <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td style="white-space:nowrap;"><?= htmlspecialchars($birthdayText, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php $hasContact = false; ?>
                        <?php if (!empty($s['guardian_phone'])): ?>
                            <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $s['guardian_phone']), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($s['guardian_phone'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php $hasContact = true; ?>
                        <?php endif; ?>
                        <?php if (!empty($s['guardian_email'])): ?>
                            <?php if ($hasContact): ?><br><?php endif; ?>
                            <a href="mailto:<?= htmlspecialchars($s['guardian_email'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($s['guardian_email'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php $hasContact = true; ?>
                        <?php endif; ?>
                        <?php if (!$hasContact): ?><span class="text-muted">–</span><?php endif; ?>
                    </td>
                    <td style="text-align:right;"><strong><?= (int) $abs['total'] ?></strong></td>
                    <td style="text-align:right;"><?= (int) $abs['ja'] ?></td>
                    <td style="text-align:right;">
                        <?php if ((int) $abs['nein'] > 0): ?>
                            <span class="roster-unexcused"><?= (int) $abs['nein'] ?></span>
                        <?php else: ?>
                            0
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;"><?= (int) $abs['offen'] ?></td>
                    <td style="text-align:right;">
                        <?php if ($rem > 0): ?>
                            <a href="<?= $recordUrl ?>"><?= (int) $rem ?></a>
                        <?php else: ?>
                            0
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Stundenprotokoll -->
<div class="card mt-1">
    <div class="card-header">
        <h2>Einträge</h2>
    </div>
    <form method="get" action="/classbook/<?= $class['id'] ?>" class="filter-form">
        <div class="form-group">
            <label for="date_from">Von</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="date_to">Bis</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
            <label for="teacher_id">Lehrkraft</label>
            <select name="teacher_id" id="teacher_id" class="form-control">
                <option value="">Alle</option>
                <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($filters['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['abbreviation'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtern</button>
    </form>
</div>

<?php if (empty($entries)): ?>
<div class="card mt-1">
    <p class="text-muted text-center">Keine Einträge gefunden.</p>
</div>
<?php else: ?>

<?php
$weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
$grouped = [];
foreach ($entries as $e) {
    $grouped[$e['entry_date']][] = $e;
}
?>

<div class="classbook-day-list mt-1">
    <?php foreach ($grouped as $date => $dayEntries): ?>
    <?php
        $ts = strtotime($date);
        $dayName = $weekdays[(int) date('w', $ts)];
        $dateFormatted = date('d.m.Y', $ts);
        $count = count($dayEntries);
    ?>
    <div class="classbook-day">
        <div class="classbook-day-header">
            <span class="classbook-day-weekday"><?= $dayName ?></span>
            <span class="classbook-day-date"><?= $dateFormatted ?></span>
            <span class="classbook-day-count"><?= $count ?> <?= $count === 1 ? 'Stunde' : 'Stunden' ?></span>
        </div>
        <div class="table-responsive">
            <table aria-label="Einträge vom <?= $dateFormatted ?>">
                <thead>
                    <tr>
                        <th scope="col">Std.</th>
                        <th scope="col">Lehrkraft</th>
                        <th scope="col">Thema / Notizen</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dayEntries as $e): ?>
                    <tr>
                        <td style="width: 3.5rem;">
                            <span class="classbook-lesson-number"><?= (int) $e['lesson'] ?></span>
                        </td>
                        <td style="width: 7rem; white-space: nowrap;">
                            <?= htmlspecialchars($e['abbreviation'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div class="classbook-topic"><?= htmlspecialchars($e['topic'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($e['notes'])): ?>
                                <div class="classbook-notes"><?= htmlspecialchars($e['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="width: 6rem; text-align: right;">
                            <?php if (\OpenClassbook\Models\ClassbookEntry::canEdit($e, $_SESSION['user_id'], $role)): ?>
                                <a href="/classbook/entry/<?= $e['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
