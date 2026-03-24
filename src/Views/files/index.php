<div class="page-header">
    <h1>Dateien</h1>
</div>

<div class="storage-info card mb-1">
    <div class="storage-bar-wrapper">
        <div class="storage-bar">
            <div class="storage-bar-fill" style="width: <?= min(100, round($usedStorage / $maxStorage * 100)) ?>%"></div>
        </div>
        <span class="storage-text"><?= $usedFormatted ?> / <?= $maxFormatted ?> belegt</span>
    </div>
</div>

<div class="file-overview">
    <a href="/files/private" class="file-overview-card card">
        <div class="file-overview-icon">&#128194;</div>
        <div class="file-overview-card-body">
            <h2>Meine Dateien</h2>
            <p class="text-muted">Privater Bereich — nur fuer Sie sichtbar</p>
            <span class="file-overview-card-link">Oeffnen &rarr;</span>
        </div>
    </a>
    <a href="/files/shared" class="file-overview-card card">
        <div class="file-overview-icon">&#128101;</div>
        <div class="file-overview-card-body">
            <h2>Gemeinschaftliche Dateien</h2>
            <p class="text-muted">Fuer alle Lehrkraefte und Verwaltung sichtbar</p>
            <span class="file-overview-card-link">Oeffnen &rarr;</span>
        </div>
    </a>
</div>
