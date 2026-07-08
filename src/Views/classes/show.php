<div class="page-header">
    <h1>Klasse <?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="btn-group">
        <a href="/classbook/<?= $class['id'] ?>" class="btn">Klassenbuch</a>
        <a href="/absences/students?class_id=<?= $class['id'] ?>" class="btn">Schüler:innen-Fehlzeiten</a>
        <a href="/classes/<?= $class['id'] ?>/edit" class="btn btn-secondary">Bearbeiten</a>
    </div>
</div>

<p class="text-muted mb-1">Schuljahr: <?= htmlspecialchars($class['school_year'], ENT_QUOTES, 'UTF-8') ?></p>

<div class="card">
    <div class="card-header">
        <h2>Lehrkräfte</h2>
    </div>
    <?php if (empty($teachers)): ?>
        <p class="text-muted">Keine Lehrkräfte zugewiesen.</p>
    <?php else: ?>
        <ul class="list-unstyled">
            <?php foreach ($teachers as $t): ?>
                <li><?= htmlspecialchars($t['lastname'] . ', ' . $t['firstname'] . ' (' . $t['abbreviation'] . ')', ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Schüler:innen (<?= count($students) ?>)</h2>
        <?php if ($canTransfer): ?>
            <a href="/classes/<?= $class['id'] ?>/students/create" class="btn btn-sm">Neue:n Schüler:in anlegen</a>
        <?php endif; ?>
    </div>
    <?php if (empty($students)): ?>
        <p class="text-muted">Keine Schüler in dieser Klasse.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table aria-label="Schülerliste">
                <thead>
                    <tr>
                        <th scope="col">Nachname</th>
                        <th scope="col">Vorname</th>
                        <th scope="col">Geburtsdatum</th>
                        <?php if ($canTransfer): ?>
                            <th scope="col">Aktionen</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($s['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $s['birthday'] ? date('d.m.Y', strtotime($s['birthday'])) : '-' ?></td>
                        <?php if ($canTransfer): ?>
                            <td>
                                <div class="table-actions">
                                    <a href="/students/<?= $s['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>

                                    <form method="post" action="/students/<?= $s['id'] ?>/archive" class="form-inline">
                                        <?= \OpenClassbook\View::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-secondary" data-confirm="<?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname'], ENT_QUOTES, 'UTF-8') ?> archivieren? Der Login wird deaktiviert, die Daten bleiben erhalten.">Archivieren</button>
                                    </form>

                                    <form method="post" action="/classes/<?= $class['id'] ?>/transfer" class="form-inline">
                                        <?= \OpenClassbook\View::csrfField() ?>
                                        <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                        <label for="new_class_<?= $s['id'] ?>" class="sr-only">Neue Klasse für <?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname'], ENT_QUOTES, 'UTF-8') ?></label>
                                        <select name="new_class_id" id="new_class_<?= $s['id'] ?>" class="form-control form-control-sm" required>
                                            <option value="">Klasse wählen...</option>
                                            <?php foreach ($otherClasses as $c): ?>
                                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . ' (' . $c['school_year'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-secondary" data-confirm="<?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname'], ENT_QUOTES, 'UTF-8') ?> wirklich in eine andere Klasse versetzen?">Versetzen</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($canTransfer && !empty($archivedStudents)): ?>
<div class="card mt-1">
    <div class="card-header">
        <h2>Archivierte Schüler:innen (<?= count($archivedStudents) ?>)</h2>
    </div>
    <div class="table-responsive">
        <table aria-label="Archivierte Schülerliste">
            <thead>
                <tr>
                    <th scope="col">Nachname</th>
                    <th scope="col">Vorname</th>
                    <th scope="col">Geburtsdatum</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archivedStudents as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['lastname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($s['firstname'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $s['birthday'] ? date('d.m.Y', strtotime($s['birthday'])) : '-' ?></td>
                    <td>
                        <div class="table-actions">
                            <form method="post" action="/students/<?= $s['id'] ?>/restore" class="form-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-secondary">Wiederherstellen</button>
                            </form>

                            <?php if ($isAdmin): ?>
                            <form method="post" action="/students/<?= $s['id'] ?>/delete" class="form-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="<?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname'], ENT_QUOTES, 'UTF-8') ?> endgültig löschen? Konto, Fehlzeiten und Bemerkungen werden unwiderruflich entfernt.">Endgültig löschen</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
