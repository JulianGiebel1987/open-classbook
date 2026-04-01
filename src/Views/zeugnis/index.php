<div class="page-header">
    <h1>Meine Dokumente</h1>
    <a href="/zeugnis/browse" class="btn">Neues Dokument</a>
</div>

<?php
$statusLabels = ['draft' => 'Entwurf', 'final' => 'Fertig'];
$statusBadges = ['draft' => 'badge-draft', 'final' => 'badge-final'];
?>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Dokumente">
            <thead>
                <tr>
                    <th scope="col">Schüler/in</th>
                    <th scope="col">Klasse</th>
                    <th scope="col">Vorlage</th>
                    <th scope="col">Status</th>
                    <th scope="col">Ersteller</th>
                    <th scope="col">Aktualisiert</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($instances)): ?>
                    <tr><td colspan="7" class="text-center">
                        Keine Dokumente vorhanden.
                        <a href="/zeugnis/browse">Jetzt Vorlage auswählen →</a>
                    </td></tr>
                <?php endif; ?>

                <?php foreach ($instances as $inst): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($inst['student_first_name'] . ' ' . $inst['student_last_name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($inst['title'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($inst['title'], ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                        <?php if ((int) $inst['created_by'] !== $userId && isset($inst['shared_can_edit'])): ?>
                            <br><small class="badge badge-info">Geteilt</small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($inst['class_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($inst['template_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge <?= $statusBadges[$inst['status']] ?? '' ?>">
                            <?= $statusLabels[$inst['status']] ?? $inst['status'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($inst['creator_username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('d.m.Y', strtotime($inst['updated_at'])) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="/zeugnis/<?= (int) $inst['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <a href="/zeugnis/<?= (int) $inst['id'] ?>/export-pdf" class="btn btn-sm btn-muted">PDF</a>
                            <?php if ((int) $inst['created_by'] === $userId): ?>
                                <a href="/zeugnis/<?= (int) $inst['id'] ?>/share" class="btn btn-sm btn-muted">Teilen</a>
                                <form method="post" action="/zeugnis/<?= (int) $inst['id'] ?>/delete" class="d-inline">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            data-confirm="Dokument wirklich löschen?">Löschen</button>
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

<?php if (!empty($instances)): ?>
<div class="card mt-4">
    <h2 class="card-title">Batch-Export</h2>
    <form method="post" action="/zeugnis/batch-export" id="batch-export-form">
        <?= \OpenClassbook\View::csrfField() ?>
        <p class="text-muted">Mehrere Dokumente als ZIP herunterladen:</p>
        <div class="form-row">
            <button type="button" id="btn-select-all" class="btn btn-sm btn-muted">Alle auswählen</button>
            <button type="submit" class="btn btn-sm btn-primary">Ausgewählte exportieren</button>
        </div>
        <div class="table-responsive mt-2">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-cb"></th>
                        <th>Schüler/in</th>
                        <th>Klasse</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instances as $inst): ?>
                    <tr>
                        <td><input type="checkbox" name="instance_ids[]" value="<?= (int) $inst['id'] ?>" class="batch-cb"></td>
                        <td><?= htmlspecialchars($inst['student_first_name'] . ' ' . $inst['student_last_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($inst['class_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= $statusBadges[$inst['status']] ?? '' ?>"><?= $statusLabels[$inst['status']] ?? '' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script>
document.getElementById('select-all-cb').addEventListener('change', function() {
    document.querySelectorAll('.batch-cb').forEach(cb => cb.checked = this.checked);
});
</script>
<?php endif; ?>
