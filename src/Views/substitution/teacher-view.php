<div class="page-header">
    <h1>Vertretung</h1>
</div>

<?php if (!$setting): ?>
    <div class="card">
        <p class="text-muted">Es ist aktuell kein Stundenplan veroeffentlicht.</p>
    </div>
<?php else: ?>

    <!-- Meine Vertretungseinsaetze -->
    <div class="card">
        <div class="card-header">
            <h2>Meine Vertretungseinsaetze</h2>
        </div>
        <?php if (empty($mySubstitutions)): ?>
            <p class="text-muted">Keine anstehenden Vertretungseinsaetze.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Einheit</th>
                        <th>Klasse</th>
                        <th>Fach</th>
                        <th>Fuer</th>
                        <th>Raum</th>
                        <th>Hinweis</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mySubstitutions as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d.m.Y', strtotime($s['date'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $s['slot_number'] ?>.</td>
                        <td><?= htmlspecialchars($s['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars(($s['absent_abbreviation'] ?? '') . ' ' . ($s['absent_lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($s['room'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/substitution/pdf?date=<?= htmlspecialchars($s['date'], ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-sm btn-secondary" title="Tagesplan herunterladen">PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Mein Unterricht wird vertreten -->
    <div class="card">
        <div class="card-header">
            <h2>Mein Unterricht wird vertreten</h2>
        </div>
        <?php if (empty($myAbsences)): ?>
            <p class="text-muted">Kein Unterricht wird aktuell vertreten.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Einheit</th>
                        <th>Klasse</th>
                        <th>Fach</th>
                        <th>Vertretung durch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myAbsences as $s): ?>
                    <tr class="<?= $s['is_cancelled'] ? 'sub-row-cancelled' : '' ?>">
                        <td><?= htmlspecialchars(date('d.m.Y', strtotime($s['date'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $s['slot_number'] ?>.</td>
                        <td><?= htmlspecialchars($s['class_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($s['is_cancelled']): ?>
                                <span class="sub-cancelled-label">Entfall</span>
                            <?php elseif ($s['substitute_teacher_id']): ?>
                                <?= htmlspecialchars(($s['substitute_abbreviation'] ?? '') . ' ' . ($s['substitute_lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="text-muted">Noch nicht zugewiesen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['is_cancelled']): ?>
                                <span class="badge badge-warning">Entfall</span>
                            <?php elseif ($s['substitute_teacher_id']): ?>
                                <span class="badge badge-success">Vertreten</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Offen</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endif; ?>
