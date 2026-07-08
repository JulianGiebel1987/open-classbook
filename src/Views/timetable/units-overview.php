<?php
/**
 * Summierte Unterrichtseinheiten je Lehrkraft für einen Stundenplan.
 * Erwartet: $setting, $rows [id, abbreviation, name, units], $totalUnits, $unitDuration
 */
$formatDuration = function (int $units, int $minutesPerUnit): string {
    $total = $units * $minutesPerUnit;
    $h = intdiv($total, 60);
    $m = $total % 60;
    if ($h > 0 && $m > 0) {
        return $h . 'h ' . $m . 'min';
    }
    if ($h > 0) {
        return $h . 'h';
    }
    return $m . 'min';
};
?>

<div class="page-header">
    <h1>Einheitenübersicht: <?= htmlspecialchars($setting['school_year'], ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/timetable" class="btn btn-secondary">Zurück zur Stundenplanung</a>
</div>

<p class="text-muted mb-1">Unterrichtseinheiten je Lehrkraft (Einheitsdauer <?= (int) $unitDuration ?> Min.). Pausen sind nicht enthalten.</p>

<div class="card">
    <table class="table" aria-label="Einheiten je Lehrkraft">
        <thead>
            <tr>
                <th scope="col">Kürzel</th>
                <th scope="col">Lehrkraft</th>
                <th scope="col" style="text-align:right">Einheiten</th>
                <th scope="col" style="text-align:right">Zeit/Woche</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows) || $totalUnits === 0): ?>
                <tr><td colspan="4" class="text-muted text-center">Noch keine Einheiten eingetragen.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['abbreviation'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="text-align:right"><span class="badge"><?= (int) $r['units'] ?></span></td>
                <td style="text-align:right"><?= htmlspecialchars($formatDuration((int) $r['units'], (int) $unitDuration), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
        <tfoot>
            <tr>
                <th scope="row" colspan="2">Gesamt</th>
                <td style="text-align:right"><strong><?= (int) $totalUnits ?></strong></td>
                <td style="text-align:right"><strong><?= htmlspecialchars($formatDuration((int) $totalUnits, (int) $unitDuration), ENT_QUOTES, 'UTF-8') ?></strong></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
