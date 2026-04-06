<div class="page-header">
    <h1>Vertretungsplan</h1>
    <a href="/absences/teachers" class="btn btn-secondary">Lehrerabwesenheiten</a>
</div>

<?php if (!$setting): ?>
    <div class="card">
        <p class="text-muted">Kein aktiver Stundenplan vorhanden. Bitte zuerst einen <a href="/timetable">Stundenplan erstellen</a>.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h2>Naechste Schultage</h2>
        </div>
        <div class="sub-day-grid">
            <?php foreach ($dates as $d): ?>
                <?php
                $planInfo = $planStatusMap[$d['date']] ?? null;
                $absentCount = $absentCounts[$d['date']] ?? 0;
                $statusClass = '';
                if ($planInfo && $planInfo['is_published']) {
                    $statusClass = 'sub-day-published';
                } elseif ($absentCount > 0) {
                    $statusClass = 'sub-day-pending';
                }
                ?>
                <a href="/substitution/plan?date=<?= htmlspecialchars($d['date'], ENT_QUOTES, 'UTF-8') ?>"
                   class="sub-day-card <?= $statusClass ?> <?= $d['is_today'] ? 'sub-day-today' : '' ?>"
                   aria-label="Vertretungsplan <?= htmlspecialchars($d['formatted'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="sub-day-name"><?= htmlspecialchars($d['day_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="sub-day-date"><?= htmlspecialchars($d['formatted'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($d['is_today']): ?>
                        <span class="badge badge-info">Heute</span>
                    <?php endif; ?>
                    <?php if ($absentCount > 0): ?>
                        <span class="badge badge-warning"><?= $absentCount ?> abwesend</span>
                    <?php endif; ?>
                    <?php if ($planInfo && $planInfo['is_published']): ?>
                        <span class="badge badge-success">Veröffentlicht</span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
