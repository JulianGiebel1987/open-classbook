<div class="page-header">
    <h1>Schülerakten: <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <a href="/classbook/<?= $class['id'] ?>" class="btn btn-secondary">Zurück zum Klassenbuch</a>
    </div>
</div>

<p class="text-muted mb-1">Wählen Sie eine/n Schüler:in, um alle Fehlzeiten und Bemerkungen anzuzeigen.</p>

<?php if (empty($students)): ?>
<div class="card mt-1">
    <p class="text-muted text-center">Keine Schüler:innen in dieser Klasse.</p>
</div>
<?php else: ?>
<div class="card mt-1">
    <div class="table-responsive">
        <table aria-label="Schüler:innen der Klasse <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?>">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td>
                        <a href="/classbook/<?= $class['id'] ?>/students/<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td style="text-align:right">
                        <a href="/classbook/<?= $class['id'] ?>/students/<?= $s['id'] ?>" class="btn btn-sm btn-secondary">Akte öffnen</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
