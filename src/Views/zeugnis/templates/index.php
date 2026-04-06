<div class="page-header">
    <h1>Vorlagen</h1>
    <a href="/zeugnis/templates/create" class="btn">Neue Vorlage</a>
</div>

<div class="card mb-4">
    <form method="get" action="/zeugnis/templates" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">Alle</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Entwurf</option>
                    <option value="published" <?= ($filters['status'] ?? '') === 'published' ? 'selected' : '' ?>>Veröffentlicht</option>
                </select>
            </div>
            <div class="form-group">
                <label for="school_year">Schuljahr</label>
                <input type="text" name="school_year" id="school_year" class="form-control"
                       value="<?= htmlspecialchars($filters['school_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="z.B. 2025/2026">
            </div>
            <div class="form-group form-group--align-end">
                <button type="submit" class="btn btn-secondary">Filtern</button>
                <a href="/zeugnis/templates" class="btn btn-muted">Zurücksetzen</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table aria-label="Vorlagen">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Format</th>
                    <th scope="col">Schuljahr</th>
                    <th scope="col">Status</th>
                    <th scope="col">Ersteller:in</th>
                    <th scope="col">Aktualisiert</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="7" class="text-center">Keine Vorlagen vorhanden.</td></tr>
                <?php endif; ?>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if (!empty($t['description'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($t['description'], 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($t['page_format'], ENT_QUOTES, 'UTF-8') ?>
                        <?= $t['page_orientation'] === 'L' ? 'Quer' : 'Hoch' ?></td>
                    <td><?= htmlspecialchars($t['school_year'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($t['status'] === 'published'): ?>
                            <span class="badge badge-published">Veröffentlicht</span>
                        <?php else: ?>
                            <span class="badge badge-draft">Entwurf</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($t['creator_username'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('d.m.Y', strtotime($t['updated_at'])) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="/zeugnis/templates/<?= (int) $t['id'] ?>/edit" class="btn btn-sm btn-secondary">Bearbeiten</a>
                            <a href="/zeugnis/templates/<?= (int) $t['id'] ?>/preview" class="btn btn-sm btn-muted">Vorschau</a>
                            <?php if ($t['status'] === 'draft'): ?>
                            <form method="post" action="/zeugnis/templates/<?= (int) $t['id'] ?>/publish" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-success">Veröffentlichen</button>
                            </form>
                            <?php else: ?>
                            <form method="post" action="/zeugnis/templates/<?= (int) $t['id'] ?>/unpublish" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-warning">Zurückziehen</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" action="/zeugnis/templates/<?= (int) $t['id'] ?>/duplicate" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-muted">Duplizieren</button>
                            </form>
                            <form method="post" action="/zeugnis/templates/<?= (int) $t['id'] ?>/delete" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-danger"
                                        data-confirm="Vorlage &quot;<?= htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') ?>&quot; wirklich löschen?">
                                    Löschen
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
