<div class="page-header">
    <div>
        <a href="/lists" class="btn btn-sm btn-secondary mb-05">Zurueck</a>
        <h1>Neue Liste</h1>
    </div>
</div>

<div class="card">
    <form method="post" action="/lists" id="createListForm">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="title">Titel <span aria-hidden="true">*</span></label>
            <input type="text" name="title" id="title" class="form-control" required maxlength="255" placeholder="z.B. Anwesenheitsliste, Notenuebersicht">
        </div>

        <div class="form-group">
            <label for="description">Beschreibung</label>
            <textarea name="description" id="description" class="form-control" rows="2" placeholder="Optionale Beschreibung"></textarea>
        </div>

        <div class="form-group">
            <label for="visibility">Sichtbarkeit</label>
            <select name="visibility" id="visibility" class="form-control">
                <option value="private">Privat (nur ich)</option>
                <option value="global">Global (alle Lehrkraefte/Verwaltung)</option>
                <option value="shared">Freigegeben (gezielt an einzelne Nutzer)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="class_id">Klasse (optional — Schueler als Zeilen vorbefuellen)</label>
            <select name="class_id" id="class_id" class="form-control">
                <option value="">— Leere Liste —</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <fieldset class="form-group">
            <legend>Spalten</legend>
            <div id="columnContainer">
                <div class="list-column-row">
                    <input type="text" name="col_title[]" class="form-control" placeholder="Spaltenname" required>
                    <select name="col_type[]" class="form-control list-col-type-select">
                        <option value="text">Freitext</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="number">Zahl</option>
                        <option value="date">Datum</option>
                        <option value="select">Auswahl</option>
                        <option value="rating">Bewertung (1-6)</option>
                    </select>
                    <input type="text" name="col_options[]" class="form-control list-col-options" placeholder="Optionen (kommasepariert)" style="display:none">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-secondary mt-05" id="addColumnBtn">Spalte hinzufuegen</button>
        </fieldset>

        <div class="btn-group mt-1">
            <button type="submit" class="btn">Liste erstellen</button>
            <a href="/lists" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
