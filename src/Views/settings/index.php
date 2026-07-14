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
            <span class="form-help">Wenn aktiviert, können Benutzer die Zwei-Faktor-Authentifizierung einrichten. Bei erzwungenen Rollen müssen Benutzer 2FA beim nächsten Login einrichten.</span>
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
            'module_school_aides'=> 'Schulbegleiter:innen',
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
        <p class="form-help">Hier kann eingestellt werden, ob Schulleitung und Sekretariat auf bestimmte Module zugreifen dürfen. Wenn ein Modul hier deaktiviert wird, ist es für die jeweilige Rolle unsichtbar &mdash; unabhängig von der globalen Moduleinstellung oben.</p>

        <?php
        $roleModules = [
            'Lehrkraft-Abwesenheiten' => [
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
            'Schulbegleiter:innen' => [
                'module_school_aides_schulleitung' => 'Schulleitung',
                'module_school_aides_sekretariat'  => 'Sekretariat',
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

    <!-- ====================================================== -->
    <!-- Aufbewahrungsfristen / Löschkonzept                    -->
    <!-- ====================================================== -->
    <div class="card">
        <h2>Aufbewahrungsfristen (Löschkonzept)</h2>
        <p class="form-help">
            Personenbezogene Daten werden nach Ablauf der hier festgelegten Fristen automatisch gelöscht
            (DSGVO Art. 5 Abs. 1 lit. e / Art. 17). Die Löschung erfolgt über den Cronjob
            <code>database/cleanup.php</code> oder manuell über die Schaltfläche unten. Der Wert <strong>0</strong>
            deaktiviert die automatische Löschung der jeweiligen Kategorie.
        </p>

        <div class="form-group">
            <label for="retention_messages_days">Nachrichten aufbewahren (Tage)</label>
            <input type="number" id="retention_messages_days" name="retention_messages_days" class="form-control" min="0" max="3650"
                   value="<?= htmlspecialchars($settings['retention_messages_days'] ?? '730', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="retention_messages_help">
            <span class="form-help" id="retention_messages_help">Ältere 1:1- und Gruppen-Nachrichten (inkl. Anhänge) werden gelöscht (Standard: 730 Tage = 2 Jahre).</span>
        </div>

        <div class="form-group">
            <label for="retention_audit_days">Audit-Log aufbewahren (Tage)</label>
            <input type="number" id="retention_audit_days" name="retention_audit_days" class="form-control" min="0" max="3650"
                   value="<?= htmlspecialchars($settings['retention_audit_days'] ?? '90', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="retention_audit_help">
            <span class="form-help" id="retention_audit_help">Protokoll sicherheitsrelevanter Aktionen (Standard: 90 Tage).</span>
        </div>

        <div class="form-group">
            <label for="retention_login_attempts_days">Login-Versuche aufbewahren (Tage)</label>
            <input type="number" id="retention_login_attempts_days" name="retention_login_attempts_days" class="form-control" min="0" max="3650"
                   value="<?= htmlspecialchars($settings['retention_login_attempts_days'] ?? '30', ENT_QUOTES, 'UTF-8') ?>"
                   aria-describedby="retention_login_help">
            <span class="form-help" id="retention_login_help">Pseudonymisierte Anmeldeprotokolle (Standard: 30 Tage).</span>
        </div>
    </div>

    <div class="btn-group">
        <button type="submit" class="btn">Einstellungen speichern</button>
    </div>
</form>

<div class="card">
    <h2>Daten jetzt bereinigen</h2>
    <p class="form-help">
        Führt die oben konfigurierten Löschroutinen sofort aus. Bereits gelöschte Daten können nicht
        wiederhergestellt werden.
    </p>
    <form method="post" action="/settings/retention/run" onsubmit="return confirm('Löschroutinen jetzt ausführen? Betroffene Daten werden unwiderruflich gelöscht.');">
        <?= \OpenClassbook\View::csrfField() ?>
        <button type="submit" class="btn btn-secondary">Jetzt aufräumen</button>
    </form>
</div>
