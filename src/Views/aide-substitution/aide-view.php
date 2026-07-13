<div class="page-header">
    <h1>Meine Vertretungen</h1>
    <?php if (!empty($needs)): ?>
    <div class="page-header-actions">
        <a href="/aide-substitution/pdf" class="btn btn-secondary">PDF-Export</a>
    </div>
    <?php endif; ?>
</div>

<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Meine Vertretungen">
            <thead>
                <tr>
                    <th scope="col">Priorität</th>
                    <th scope="col">Zeitraum</th>
                    <th scope="col">Kind</th>
                    <th scope="col">Für (abwesend)</th>
                    <th scope="col">Notiz</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($needs)): ?>
                    <tr><td colspan="5" class="text-center">Aktuell sind Ihnen keine Vertretungen zugewiesen.</td></tr>
                <?php endif; ?>
                <?php foreach ($needs as $n): ?>
                <tr>
                    <td>
                        <span class="badge prio-<?= (int) $n['priority'] ?>">
                            <?= (int) $n['priority'] ?> – <?= htmlspecialchars($priorities[(int) $n['priority']] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y', strtotime($n['date_from'])) ?> – <?= date('d.m.Y', strtotime($n['date_to'])) ?></td>
                    <td><?= htmlspecialchars($n['student_lastname'] . ', ' . $n['student_firstname'] . ' (' . $n['class_name'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($n['absent_lastname'] . ', ' . $n['absent_firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($n['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?: '<span class="text-muted">–</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
