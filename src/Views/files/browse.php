<div class="page-header">
    <div>
        <?php
        if ($currentFolderId) {
            $parentFolderId = $breadcrumbs[count($breadcrumbs) - 1]['parent_id'] ?? null;
            $backUrl = $parentFolderId
                ? '/files/folder/' . (int) $parentFolderId
                : ($isShared ? '/files/shared' : '/files/private');
        } else {
            $backUrl = '/files';
        }
        ?>
        <a href="<?= $backUrl ?>" class="btn btn-sm btn-secondary mb-05">Zurück</a>
        <h1><?= $isShared ? 'Gemeinschaftliche Dateien' : 'Meine Dateien' ?></h1>
    </div>
</div>

<?php if (!empty($breadcrumbs)): ?>
<nav aria-label="Ordnerpfad">
    <ol class="breadcrumb">
        <li><a href="<?= $isShared ? '/files/shared' : '/files/private' ?>"><?= $isShared ? 'Gemeinschaftlich' : 'Meine Dateien' ?></a></li>
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <li>
                <?php if ($i < count($breadcrumbs) - 1): ?>
                    <a href="/files/folder/<?= (int) $crumb['id'] ?>"><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>

<div class="storage-info card mb-1">
    <div class="storage-bar-wrapper">
        <div class="storage-bar">
            <div class="storage-bar-fill" style="width: <?= min(100, round($usedStorage / $maxStorage * 100)) ?>%"></div>
        </div>
        <span class="storage-text"><?= $usedFormatted ?> / <?= $maxFormatted ?> belegt (<?= $remainingFormatted ?> frei)</span>
    </div>
</div>

<div class="card mb-1">
    <div class="card-header">
        <h2>Aktionen</h2>
    </div>
    <div class="file-actions">
        <form method="post" action="/files/upload" enctype="multipart/form-data" class="file-upload-form">
            <?= \OpenClassbook\View::csrfField() ?>
            <input type="hidden" name="folder_id" value="<?= $currentFolderId ? (int) $currentFolderId : '' ?>">
            <input type="hidden" name="is_shared" value="<?= $isShared ? '1' : '0' ?>">
            <div class="file-upload-row">
                <input type="file" name="file" id="fileInput" class="form-control" required data-max-size="<?= 15 * 1024 * 1024 ?>">
                <button type="submit" class="btn">Hochladen</button>
            </div>
            <p class="text-muted file-upload-hint">Max. 15 MB pro Datei</p>
        </form>

        <button type="button" class="btn btn-secondary" id="toggleFolderForm">Neuer Ordner</button>
        <form method="post" action="/files/folder" class="folder-create-form" id="folderForm" style="display: none;">
            <?= \OpenClassbook\View::csrfField() ?>
            <input type="hidden" name="parent_id" value="<?= $currentFolderId ? (int) $currentFolderId : '' ?>">
            <input type="hidden" name="is_shared" value="<?= $isShared ? '1' : '0' ?>">
            <div class="folder-create-row">
                <input type="text" name="name" class="form-control" placeholder="Ordnername" required maxlength="100">
                <button type="submit" class="btn">Erstellen</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <?php if (empty($folders) && empty($files)): ?>
        <p class="text-muted text-center">Dieser Ordner ist leer.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table aria-label="Dateien und Ordner">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Groesse</th>
                        <th scope="col">Datum</th>
                        <th scope="col">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($folders as $f): ?>
                    <tr>
                        <td>
                            <a href="/files/folder/<?= (int) $f['id'] ?>" class="folder-link">
                                <span class="folder-icon">&#128194;</span>
                                <?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td class="text-muted">—</td>
                        <td><?= date('d.m.Y H:i', strtotime($f['created_at'])) ?></td>
                        <td>
                            <form method="post" action="/files/folder/<?= (int) $f['id'] ?>/delete" class="d-inline">
                                <?= \OpenClassbook\View::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Ordner und gesamten Inhalt wirklich löschen?">Löschen</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td>
                            <span class="file-icon">&#128196;</span>
                            <?= htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= \OpenClassbook\Models\FileEntry::formatSize((int) $file['file_size']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($file['created_at'])) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/files/<?= (int) $file['id'] ?>/download" class="btn btn-sm btn-secondary">Download</a>
                                <form method="post" action="/files/<?= (int) $file['id'] ?>/delete" class="d-inline">
                                    <?= \OpenClassbook\View::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Datei wirklich löschen?">Löschen</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
