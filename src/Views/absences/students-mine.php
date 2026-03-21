<div class="page-header">
    <h1>Meine Fehlzeiten</h1>
    <a href="/absences/students/self" class="btn">Krankmeldung erstellen</a>
</div>

<p class="text-muted mb-1"><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES, 'UTF-8') ?> - Klasse <?= htmlspecialchars($student['class_name'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Meine Fehlzeiten">
            <thead>
                <tr>
                    <th scope="col">Von</th>
                    <th scope="col">Bis</th>
                    <th scope="col">Status</th>
                    <th scope="col">Grund</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absences)): ?>
                    <tr><td colspan="4" class="text-center">Keine Fehlzeiten vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ($absences as $a): ?>
                <tr>
                    <td><?= date('d.m.Y', strtotime($a['date_from'])) ?></td>
                    <td><?= date('d.m.Y', strtotime($a['date_to'])) ?></td>
                    <td>
                        <?php if ($a['excused'] === 'ja'): ?>
                            <span class="badge badge-success">Entschuldigt</span>
                        <?php elseif ($a['excused'] === 'nein'): ?>
                            <span class="badge badge-danger">Unentschuldigt</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Offen</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($a['reason'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
