<div class="page-header">
    <h1>Mein Dashboard</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2>Meine Klassen</h2>
    </div>

    <?php if (empty($classes)): ?>
        <p class="text-muted">Ihnen sind noch keine Klassen zugewiesen.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table aria-label="Meine Klassen">
                <thead>
                    <tr>
                        <th scope="col">Klasse</th>
                        <th scope="col">Schuljahr</th>
                        <th scope="col">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($class['school_year'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/classbook/<?= $class['id'] ?>" class="btn btn-sm">Klassenbuch</a>
                                <a href="/absences/students?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-secondary">Fehlzeiten</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Schnellzugriff</h2>
    </div>
    <div class="btn-group flex-wrap">
        <a href="/absences/teachers/self" class="btn">Krankmeldung erstellen</a>
    </div>
</div>
