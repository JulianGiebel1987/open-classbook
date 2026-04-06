<div class="page-header">
    <h1>Einstellungen</h1>
</div>

<form method="post" action="/settings">
    <?= \OpenClassbook\View::csrfField() ?>

    <!-- ====================================================== -->
    <!-- Zwei-Faktor-Authentifizierung                          -->
    <!-- ====================================================== -->
    <div class="card">
        <h2>Zwei-Faktor-Authentifizierung (2FA)</h2>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="two_factor_enabled" value="1" <?= ($settings['two_factor_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                2FA global aktivieren
            </label>
            <span class="form-help">Wenn aktiviert, können Benutzer die Zwei-Faktor-Authentifizierung einrichten. Bei erzwungenen Rollen müssen Benutzer 2FA beim naechsten Login einrichten.</span>
        </div>

        <fieldset class="form-group">
            <legend>2FA erzwingen für Rollen</legend>
            <span class="form-help">Benutzer mit diesen Rollen müssen 2FA einrichten, bevor sie auf das System zugreifen können.</span>
            <?php foreach ($allRoles as $role): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="two_factor_enforce_roles[]" value="<?= $role ?>" <?= in_array($role, $enforceRoles) ? 'checked' : '' ?>>
                    <?= ucfirst(htmlspecialchars($role, ENT_QUOTES, 'UTF-8')) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div class="form-group">
            <label for="two_factor_code_lifetime">Code-Gueltigkeit (Sekunden)</label>
            <input type="number" id="two_factor_code_lifetime" name="two_factor_code_lifetime" class="form-control" min="60" max="3600"
                   value="<?= htmlspecialchars($settings['two_factor_code_lifetime'] ?? '600', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="lifetime_help">
            <span class="form-help" id="lifetime_help">Wie lange ein per E-Mail gesendeter Code gültig ist (Standard: 600 Sekunden = 10 Minuten).</span>
        </div>

        <div class="form-group">
            <label for="two_factor_max_attempts">Max. Fehlversuche</label>
            <input type="number" id="two_factor_max_attempts" name="two_factor_max_attempts" class="form-control" min="3" max="20"
                   value="<?= htmlspecialchars($settings['two_factor_max_attempts'] ?? '5', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="attempts_help">
            <span class="form-help" id="attempts_help">Nach dieser Anzahl fehlgeschlagener 2FA-Versuche wird der Benutzer voruebergehend gesperrt (Standard: 5).</span>
        </div>
    </div>

    <!-- ====================================================== -->
    <!-- Globale Modulaktivierung                               -->
    <!-- ====================================================== -->
    <div class="card">
        <h2>Module aktivieren / deaktivieren</h2>
        <p class="form-help">Deaktivierte Module werden in der Navigation für alle Nutzer ausgeblendet und sind nicht zugänglich. Der Admin kann Module jederzeit hier wieder aktivieren.</p>

        <?php
        $globalModules = [
            'module_timetable'   => 'Stundenplanung',
            'module_substitution'=> 'Vertretung',
            'module_messages'    => 'Nachrichten',
            'module_lists'       => 'Listen',
            'module_files'       => 'Dateien',
            'module_templates'   => 'Vorlagen',
        ];
        ?>
        <fieldset class="form-group">
            <legend>Aktive Module</legend>
            <?php foreach ($globalModules as $key => $label): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="<?= $key ?>" value="1"
                           <?= ($settings[$key] ?? '1') !== '0' ? 'checked' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    </div>

    <!-- ====================================================== -->
    <!-- Rollenzugriff für spezifische Module                  -->
    <!-- ====================================================== -->
    <div class="card">
        <h2>Modulzugriff für Schulleitung und Sekretariat</h2>
        <p class="form-help">Hier kann eingestellt werden, ob Schulleitung und Sekretariat auf bestimmte Module zugreifen dürfen. Wenn ein Modul hier deaktiviert wird, ist es für die jeweilige Rolle unsichtbar &mdash; unabhaengig von der globalen Moduleinstellung oben.</p>

        <?php
        $roleModules = [
            'Lehrerfehlzeiten' => [
                'module_teacher_absences_schulleitung' => 'Schulleitung',
                'module_teacher_absences_sekretariat'  => 'Sekretariat',
            ],
            'Stundenplanung' => [
                'module_timetable_schulleitung' => 'Schulleitung',
                'module_timetable_sekretariat'  => 'Sekretariat',
            ],
            'Vertretung' => [
                'module_substitution_schulleitung' => 'Schulleitung',
                'module_substitution_sekretariat'  => 'Sekretariat',
            ],
        ];
        ?>

        <?php foreach ($roleModules as $moduleName => $roles): ?>
        <fieldset class="form-group">
            <legend><?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?></legend>
            <?php foreach ($roles as $key => $roleLabel): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="<?= $key ?>" value="1"
                           <?= ($settings[$key] ?? '1') !== '0' ? 'checked' : '' ?>>
                    <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?> hat Zugriff
                </label>
            <?php endforeach; ?>
        </fieldset>
        <?php endforeach; ?>
    </div>

    <div class="btn-group">
        <button type="submit" class="btn">Einstellungen speichern</button>
    </div>
</form>
