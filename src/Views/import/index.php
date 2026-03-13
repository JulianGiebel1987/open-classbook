<h1>Daten importieren</h1>

<div class="card">
    <div class="card-header">
        <h2>Lehrkraefte importieren</h2>
    </div>
    <p style="margin-bottom:0.5rem;">
        <a href="/import/template/lehrer" class="btn btn-sm btn-secondary">Vorlage herunterladen</a>
    </p>
    <form method="post" action="/import/teachers" enctype="multipart/form-data">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="form-group">
            <label for="file_teachers">Excel-Datei (.xlsx)</label>
            <input type="file" id="file_teachers" name="file" class="form-control" accept=".xlsx" required>
        </div>
        <button type="submit" class="btn">Vorschau anzeigen</button>
    </form>
</div>

<div class="card mt-1">
    <div class="card-header">
        <h2>Schueler/innen importieren</h2>
    </div>
    <p style="margin-bottom:0.5rem;">
        <a href="/import/template/schueler" class="btn btn-sm btn-secondary">Vorlage herunterladen</a>
    </p>
    <form method="post" action="/import/students" enctype="multipart/form-data">
        <?= \OpenClassbook\View::csrfField() ?>
        <div class="form-group">
            <label for="school_year">Schuljahr *</label>
            <input type="text" id="school_year" name="school_year" class="form-control" required placeholder="z.B. 2025/2026">
        </div>
        <div class="form-group">
            <label for="file_students">Excel-Datei (.xlsx)</label>
            <input type="file" id="file_students" name="file" class="form-control" accept=".xlsx" required>
        </div>
        <button type="submit" class="btn">Vorschau anzeigen</button>
    </form>
</div>
