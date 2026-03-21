<div class="page-header">
    <h1>Mein Dashboard</h1>
</div>

<?php if ($student): ?>
<div class="card">
    <div class="card-header">
        <h2>Meine Daten</h2>
    </div>
    <p><strong>Name:</strong> <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Klasse:</strong> <?= htmlspecialchars($student['class_name'], ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Schnellzugriff</h2>
    </div>
    <div class="btn-group flex-wrap">
        <a href="/absences/students/self" class="btn">Krankmeldung erstellen</a>
        <a href="/absences/students/mine" class="btn btn-secondary">Meine Fehlzeiten</a>
    </div>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Aktuelle Fehlzeiten</h2>
    </div>
    <?php if (empty($absences)): ?>
        <p class="text-muted">Keine Fehlzeiten vorhanden.</p>
    <?php else: ?>
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
                    <?php foreach (array_slice($absences, 0, 5) as $a): ?>
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
        <?php if (count($absences) > 5): ?>
            <p class="mt-1"><a href="/absences/students/mine">Alle <?= count($absences) ?> Fehlzeiten anzeigen</a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-warning" role="alert">
    Kein Schueler-Profil gefunden. Bitte wenden Sie sich an das Sekretariat.
</div>
<?php endif; ?>
