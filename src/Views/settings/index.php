<div class="page-header">
    <h1>Einstellungen</h1>
</div>

<div class="card">
    <h2>Zwei-Faktor-Authentifizierung (2FA)</h2>

    <form method="post" action="/settings">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="two_factor_enabled" value="1" <?= ($settings['two_factor_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                2FA global aktivieren
            </label>
            <span class="form-help">Wenn aktiviert, koennen Benutzer die Zwei-Faktor-Authentifizierung einrichten. Bei erzwungenen Rollen muessen Benutzer 2FA beim naechsten Login einrichten.</span>
        </div>

        <fieldset class="form-group">
            <legend>2FA erzwingen fuer Rollen</legend>
            <span class="form-help">Benutzer mit diesen Rollen muessen 2FA einrichten, bevor sie auf das System zugreifen koennen.</span>
            <?php foreach ($allRoles as $role): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="two_factor_enforce_roles[]" value="<?= $role ?>" <?= in_array($role, $enforceRoles) ? 'checked' : '' ?>>
                    <?= ucfirst(htmlspecialchars($role, ENT_QUOTES, 'UTF-8')) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="form-group">
            <label for="two_factor_code_lifetime">Code-Gueltigkeit (Sekunden)</label>
            <input type="number" id="two_factor_code_lifetime" name="two_factor_code_lifetime" class="form-control" min="60" max="3600" value="<?= htmlspecialchars($settings['two_factor_code_lifetime'] ?? '600', ENT_QUOTES, 'UTF-8') ?>" aria-describedby="lifetime_help">
            <span class="form-help" id="lifetime_help">Wie lange ein per E-Mail gesendeter Code gueltig ist (Standard: 600 Sekunden = 10 Minuten).</span>
        </div>

        <div class="form-group">
            <label for="two_factor_max_attempts">Max. Fehlversuche</label>
            <input type="number" id="two_factor_max_attempts" name="two_factor_max_attempts" class="form-control" min="3" max="20" value="<?= htmlspecialchars($settings['two_factor_max_attempts'] ?? '5', ENT_QUOTES, 'UTF-8') ?>" aria-describedby="attempts_help">
            <span class="form-help" id="attempts_help">Nach dieser Anzahl fehlgeschlagener 2FA-Versuche wird der Benutzer voruebergehend gesperrt (Standard: 5).</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Einstellungen speichern</button>
        </div>
    </form>
</div>
