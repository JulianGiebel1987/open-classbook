<div class="page-header">
    <h1>Datensicherung</h1>
</div>

<p class="mb-1">
    Hier können Sie <strong>sämtliche Daten dieser Instanz</strong> in einer einzigen
    Datei sichern und bei Bedarf wieder einspielen. Die Sicherung enthält alle
    Datenbankinhalte (Benutzer, Klassen, Klassenbücher, Fehlzeiten, Nachrichten,
    Listen, Stunden- und Vertretungspläne, Zeugnisse, Einstellungen usw.) sowie
    alle hochgeladenen Dateien.
</p>

<!-- ====================================================== -->
<!-- Export                                                 -->
<!-- ====================================================== -->
<div class="card">
    <div class="card-header">
        <h2>Sicherung erstellen (Export)</h2>
    </div>
    <p class="mb-sm">
        Erzeugt eine ZIP-Datei mit allen Daten dieser Instanz und lädt sie
        herunter. Bewahren Sie die Datei sicher auf – sie enthält
        personenbezogene Daten und Passwort-Hashes.
    </p>
    <div class="alert alert-warning" role="alert">
        <span>
            <strong>Datenschutzhinweis:</strong> Die Sicherungsdatei enthält alle
            personenbezogenen Daten (inkl. Passwort-Hashes und
            Zwei-Faktor-Schlüssel). Speichern Sie sie ausschließlich verschlüsselt
            und geben Sie sie nicht an Unbefugte weiter (DSGVO).
        </span>
    </div>
    <form method="post" action="/backup/export">
        <?= \OpenClassbook\View::csrfField() ?>
        <button type="submit" class="btn">Sicherung herunterladen</button>
    </form>
</div>

<!-- ====================================================== -->
<!-- Import                                                 -->
<!-- ====================================================== -->
<div class="card mt-1">
    <div class="card-header">
        <h2>Sicherung einspielen (Import)</h2>
    </div>

    <div class="alert alert-error" role="alert">
        <span>
            <strong>Achtung:</strong> Beim Einspielen werden <strong>alle
            vorhandenen Daten dieser Instanz vollständig ersetzt</strong>. Dieser
            Vorgang kann nicht rückgängig gemacht werden. Erstellen Sie vorher
            unbedingt eine aktuelle Sicherung.
        </span>
    </div>

    <?php if (empty($uploadsWritable)): ?>
        <div class="alert alert-warning" role="alert">
            <span>
                Das Verzeichnis <code>storage/uploads/</code> ist nicht
                beschreibbar. Ein Import ist daher aktuell nicht möglich. Bitte
                Berechtigungen prüfen (z.&nbsp;B.: <code>chmod 775 storage/uploads/</code>).
            </span>
        </div>
    <?php endif; ?>

    <form method="post" action="/backup/import" enctype="multipart/form-data">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="form-group">
            <label for="backup_file">Sicherungsdatei (.zip)</label>
            <input type="file" id="backup_file" name="file" class="form-control" accept=".zip" required aria-describedby="backup_file_help">
            <span class="form-help" id="backup_file_help">
                Nur ZIP-Dateien, die zuvor über diese Funktion erstellt wurden.
                Maximale Uploadgröße: <?= htmlspecialchars($maxUploadSize, ENT_QUOTES, 'UTF-8') ?>.
            </span>
        </div>
        <button type="submit" class="btn btn-danger" <?= empty($uploadsWritable) ? 'disabled' : '' ?>>
            Datei prüfen und Vorschau anzeigen
        </button>
    </form>
</div>
