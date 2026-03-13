<h1>Mein Dashboard</h1>

<div class="card">
    <div class="card-header">
        <h2>Meine Klassen</h2>
    </div>

    <?php if (empty($classes)): ?>
        <p style="color: var(--color-text-light);">Ihnen sind noch keine Klassen zugewiesen.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Klasse</th>
                        <th>Schuljahr</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($class['school_year'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="/classbook/<?= $class['id'] ?>" class="btn btn-sm">Klassenbuch</a>
                            <a href="/absences/students?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-secondary">Fehlzeiten</a>
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
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="/absences/teachers/self" class="btn">Krankmeldung erstellen</a>
    </div>
</div>
