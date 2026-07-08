<?php
$role = \OpenClassbook\App::currentUserRole();

// Fehlzeiten-Zusammenfassung nach Status aufbereiten
$summary = ['ja' => 0, 'nein' => 0, 'offen' => 0];
$summaryDays = ['ja' => 0, 'nein' => 0, 'offen' => 0];
foreach ($absenceSummary as $row) {
    $key = $row['excused'] ?? 'offen';
    if (isset($summary[$key])) {
        $summary[$key]     = (int) $row['cnt'];
        $summaryDays[$key] = (int) $row['total_days'];
    }
}
$totalDays = array_sum($summaryDays);
?>

<div class="page-header">
    <h1>Schülerakte: <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <a href="/classbook/<?= $class['id'] ?>/students" class="btn btn-secondary">Alle Schüler:innen</a>
        <a href="/classbook/<?= $class['id'] ?>" class="btn btn-secondary">Zum Klassenbuch</a>
    </div>
</div>

<p class="text-muted mb-1">Klasse <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <form method="get" class="filter-form" onchange="if(this.student_id.value){window.location='/classbook/<?= $class['id'] ?>/students/'+this.student_id.value;}">
        <div class="form-group">
            <label for="student_id">Schüler:in wählen</label>
            <select name="student_id" id="student_id" class="form-control">
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= (int) $s['id'] === (int) $student['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-secondary">Anzeigen</button></noscript>
    </form>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Fehlzeiten</h2>
    </div>
    <p class="text-muted mb-1">
        Gesamt: <strong><?= $totalDays ?></strong> Fehltag(e) &middot;
        <span class="badge badge-success">Entschuldigt: <?= $summaryDays['ja'] ?></span>
        <span class="badge badge-danger">Unentschuldigt: <?= $summaryDays['nein'] ?></span>
        <span class="badge badge-warning">Offen: <?= $summaryDays['offen'] ?></span>
    </p>
    <div class="table-responsive">
        <table aria-label="Fehlzeiten von <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES, 'UTF-8') ?>">
            <thead>
                <tr>
                    <th scope="col">Von</th>
                    <th scope="col">Bis</th>
                    <th scope="col">Status</th>
                    <?php if ($canViewReason): ?><th scope="col">Grund</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absences)): ?>
                    <tr><td colspan="<?= $canViewReason ? 4 : 3 ?>" class="text-center text-muted">Keine Fehlzeiten vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ($absences as $a): ?>
                <tr>
                    <td style="white-space:nowrap"><?= date('d.m.Y', strtotime($a['date_from'])) ?></td>
                    <td style="white-space:nowrap"><?= date('d.m.Y', strtotime($a['date_to'])) ?></td>
                    <td>
                        <?php if ($a['excused'] === 'ja'): ?>
                            <span class="badge badge-success">Entschuldigt</span>
                        <?php elseif ($a['excused'] === 'nein'): ?>
                            <span class="badge badge-danger">Unentschuldigt</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Offen</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($canViewReason): ?>
                        <td><?= htmlspecialchars($a['reason'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Bemerkungen</h2>
    </div>
    <div class="table-responsive">
        <table aria-label="Bemerkungen zu <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES, 'UTF-8') ?>">
            <thead>
                <tr>
                    <th scope="col" style="white-space:nowrap">Datum</th>
                    <th scope="col">Lehrkraft</th>
                    <th scope="col">Bemerkung</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($remarks)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Keine Bemerkungen vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ($remarks as $r): ?>
                <tr>
                    <td style="white-space:nowrap"><?= date('d.m.Y', strtotime($r['remark_date'])) ?></td>
                    <td style="white-space:nowrap"><?= htmlspecialchars($r['abbreviation'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= nl2br(htmlspecialchars($r['remark'], ENT_QUOTES, 'UTF-8')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
