<?php
/** @var array<string,mixed> $manifest */
/** @var string $storedFile */

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$tables = is_array($manifest['tables'] ?? null) ? $manifest['tables'] : [];
$totalRows = array_sum(array_map('intval', $tables));
$fileCount = (int) ($manifest['files']['count'] ?? 0);
$fileBytes = (int) ($manifest['files']['bytes'] ?? 0);

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = (int) floor(log($bytes, 1024));
    $i = max(0, min($i, count($units) - 1));
    return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
};
?>
<div class="page-header">
    <h1>Sicherung einspielen &ndash; Vorschau</h1>
</div>

<div class="alert alert-error" role="alert">
    <span>
        <strong>Achtung:</strong> Mit dem Einspielen werden <strong>alle aktuellen
        Daten dieser Instanz unwiderruflich ersetzt</strong>. Fahren Sie nur fort,
        wenn Sie sicher sind und zuvor eine aktuelle Sicherung erstellt haben.
    </span>
</div>

<div class="card">
    <div class="card-header">
        <h2>Inhalt der Sicherung</h2>
    </div>
    <table class="table">
        <tbody>
            <tr>
                <th scope="row">Erstellt am</th>
                <td><?= $e($manifest['created_at'] ?? 'unbekannt') ?></td>
            </tr>
            <tr>
                <th scope="row">Erstellt von</th>
                <td><?= $e($manifest['created_by'] ?? 'unbekannt') ?></td>
            </tr>
            <tr>
                <th scope="row">Programmversion</th>
                <td><?= $e($manifest['app_version'] ?? 'unbekannt') ?></td>
            </tr>
            <tr>
                <th scope="row">Datensätze gesamt</th>
                <td><?= $e($totalRows) ?> in <?= $e(count($tables)) ?> Tabellen</td>
            </tr>
            <tr>
                <th scope="row">Hochgeladene Dateien</th>
                <td><?= $e($fileCount) ?> Datei(en), <?= $e($formatBytes($fileBytes)) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (($manifest['app_version'] ?? null) !== null && ($manifest['app_version'] ?? '') !== trim(@file_get_contents(dirname(__DIR__, 3) . '/VERSION') ?: '')): ?>
        <div class="alert alert-warning" role="alert">
            <span>
                Die Sicherung wurde mit einer anderen Programmversion erstellt
                (<?= $e($manifest['app_version'] ?? 'unbekannt') ?>). Stellen Sie
                sicher, dass die Datenbankstruktur kompatibel ist (Migrationen
                ausgeführt), bevor Sie fortfahren.
            </span>
        </div>
    <?php endif; ?>

    <?php if (!empty($tables)): ?>
        <details class="mt-1">
            <summary>Tabellen im Detail anzeigen</summary>
            <table class="table mt-sm">
                <thead>
                    <tr><th scope="col">Tabelle</th><th scope="col">Datensätze</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $name => $count): ?>
                        <tr>
                            <td><code><?= $e($name) ?></code></td>
                            <td><?= $e((int) $count) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
    <?php endif; ?>
</div>

<div class="card mt-1">
    <form method="post" action="/backup/import/confirm">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="stored_file" value="<?= $e($storedFile) ?>">

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="confirm" value="1" required>
                Ich habe verstanden, dass alle aktuellen Daten ersetzt werden, und
                möchte die Sicherung jetzt einspielen.
            </label>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-danger">Sicherung jetzt einspielen</button>
            <a href="/backup" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
