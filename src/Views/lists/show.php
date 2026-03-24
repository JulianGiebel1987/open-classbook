<div class="page-header">
    <div>
        <a href="/lists" class="btn btn-sm btn-secondary mb-05">Zurueck</a>
        <h1><?= htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($list['description']): ?>
            <p class="text-muted"><?= htmlspecialchars($list['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php if ($isOwner): ?>
    <div class="btn-group">
        <a href="/lists/<?= (int) $list['id'] ?>/share" class="btn btn-secondary">Freigabe</a>
        <button type="button" class="btn btn-secondary" id="toggleEditMeta">Bearbeiten</button>
    </div>
    <?php endif; ?>
</div>

<?php if ($isOwner): ?>
<div class="card mb-1" id="editMetaForm" style="display:none">
    <form method="post" action="/lists/<?= (int) $list['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="form-row">
            <div class="form-group">
                <label for="title">Titel</label>
                <input type="text" name="title" id="editTitle" class="form-control" value="<?= htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label for="visibility">Sichtbarkeit</label>
                <select name="visibility" class="form-control">
                    <option value="private" <?= $list['visibility'] === 'private' ? 'selected' : '' ?>>Privat</option>
                    <option value="global" <?= $list['visibility'] === 'global' ? 'selected' : '' ?>>Global</option>
                    <option value="shared" <?= $list['visibility'] === 'shared' ? 'selected' : '' ?>>Freigegeben</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Beschreibung</label>
            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($list['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="btn btn-sm">Speichern</button>
    </form>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="card mb-1">
    <div class="list-actions-bar">
        <button type="button" class="btn btn-sm btn-secondary" id="toggleAddColumn">Spalte hinzufuegen</button>
        <button type="button" class="btn btn-sm btn-secondary" id="toggleAddRow">Zeile hinzufuegen</button>
    </div>

    <form method="post" action="/lists/<?= (int) $list['id'] ?>/column" class="list-inline-form" id="addColumnForm" style="display:none">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="list-inline-form-row">
            <input type="text" name="col_title" class="form-control" placeholder="Spaltenname" required>
            <select name="col_type" class="form-control list-col-type-select">
                <option value="text">Freitext</option>
                <option value="checkbox">Checkbox</option>
                <option value="number">Zahl</option>
                <option value="date">Datum</option>
                <option value="select">Auswahl</option>
                <option value="rating">Bewertung (1-6)</option>
            </select>
            <input type="text" name="col_options" class="form-control list-col-options" placeholder="Optionen (kommasepariert)" style="display:none">
            <button type="submit" class="btn btn-sm">Hinzufuegen</button>
        </div>
    </form>

    <form method="post" action="/lists/<?= (int) $list['id'] ?>/row" class="list-inline-form" id="addRowForm" style="display:none">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="list-inline-form-row">
            <input type="text" name="row_label" class="form-control" placeholder="Zeilenbeschriftung (optional)">
            <button type="submit" class="btn btn-sm">Hinzufuegen</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <?php if (empty($columns)): ?>
        <p class="text-muted text-center">Noch keine Spalten definiert. Fuegen Sie zuerst Spalten hinzu.</p>
    <?php elseif (empty($rows)): ?>
        <p class="text-muted text-center">Noch keine Zeilen vorhanden. Fuegen Sie Zeilen hinzu.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="list-table" aria-label="<?= htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8') ?>" data-list-id="<?= (int) $list['id'] ?>" data-can-edit="<?= $canEdit ? '1' : '0' ?>">
                <thead>
                    <tr>
                        <th scope="col" class="list-row-label-header">#</th>
                        <?php foreach ($columns as $col): ?>
                        <th scope="col">
                            <?= htmlspecialchars($col['title'], ENT_QUOTES, 'UTF-8') ?>
                            <span class="list-col-type">(<?= $col['type'] ?>)</span>
                            <?php if ($canEdit): ?>
                                <form method="post" action="/lists/column/<?= (int) $col['id'] ?>/delete" class="d-inline">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn-icon btn-icon-danger" data-confirm="Spalte und alle Werte loeschen?" title="Spalte loeschen">&times;</button>
                                </form>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                        <?php if ($canEdit): ?>
                        <th scope="col" class="list-action-col"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="list-row-label"><?= htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <?php foreach ($columns as $col):
                            $cellValue = $cells[$row['id']][$col['id']] ?? '';
                            $colOptions = $col['options'] ? json_decode($col['options'], true) : [];
                        ?>
                        <td class="list-cell" data-row-id="<?= (int) $row['id'] ?>" data-column-id="<?= (int) $col['id'] ?>">
                            <?php if (!$canEdit): ?>
                                <?php if ($col['type'] === 'checkbox'): ?>
                                    <?= $cellValue ? '&#10003;' : '—' ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($cellValue ?: '—', ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            <?php elseif ($col['type'] === 'text'): ?>
                                <input type="text" class="list-cell-input" value="<?= htmlspecialchars($cellValue, ENT_QUOTES, 'UTF-8') ?>">
                            <?php elseif ($col['type'] === 'checkbox'): ?>
                                <input type="checkbox" class="list-cell-input" <?= $cellValue ? 'checked' : '' ?>>
                            <?php elseif ($col['type'] === 'number'): ?>
                                <input type="number" class="list-cell-input" value="<?= htmlspecialchars($cellValue, ENT_QUOTES, 'UTF-8') ?>">
                            <?php elseif ($col['type'] === 'date'): ?>
                                <input type="date" class="list-cell-input" value="<?= htmlspecialchars($cellValue, ENT_QUOTES, 'UTF-8') ?>">
                            <?php elseif ($col['type'] === 'select'): ?>
                                <select class="list-cell-input">
                                    <option value="">—</option>
                                    <?php foreach ($colOptions as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>" <?= $cellValue === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($col['type'] === 'rating'): ?>
                                <select class="list-cell-input">
                                    <option value="">—</option>
                                    <?php for ($n = 1; $n <= 6; $n++): ?>
                                        <option value="<?= $n ?>" <?= $cellValue == $n ? 'selected' : '' ?>><?= $n ?></option>
                                    <?php endfor; ?>
                                </select>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                        <?php if ($canEdit): ?>
                        <td>
                            <form method="post" action="/lists/row/<?= (int) $row['id'] ?>/delete" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn-icon btn-icon-danger" data-confirm="Zeile loeschen?" title="Zeile loeschen">&times;</button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
