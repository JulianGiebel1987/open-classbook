<?php
$roleLabels = [
    'admin'        => 'Admin',
    'schulleitung' => 'Schulleitung',
    'sekretariat'  => 'Sekretariat',
    'lehrer'       => 'Lehrkraft',
    'schueler'     => 'Schüler:in',
    'schulbegleiter' => 'Schulbegleiter:in',
];
?>
<div class="page-header">
    <h1>Neuer Benutzer</h1>
</div>

<div class="card">
    <form method="post" action="/users">
        <?= \OpenClassbook\View::csrfField() ?>

        <?php $old = $old ?? []; ?>

        <div class="form-group" id="email-field">
            <label for="email">E-Mail-Adresse <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="email" id="email" name="email" class="form-control" autocomplete="email" aria-describedby="email_help" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <span class="form-help" id="email_help">Die E-Mail-Adresse ist zugleich der Anmeldename. Schüler:innen erhalten stattdessen einen generierten Login (Eltern-E-Mail im Schüler-Bereich).</span>
        </div>

        <div class="form-group">
            <label for="role">Rolle <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="role" id="role" class="form-control" required>
                <option value="">Bitte wählen</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= ($old['role'] ?? '') === $r ? 'selected' : '' ?>><?= $roleLabels[$r] ?? ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="profile-fields" style="display: none;">
            <div class="form-group">
                <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="firstname" name="firstname" class="form-control" value="<?= htmlspecialchars($old['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="lastname" name="lastname" class="form-control" value="<?= htmlspecialchars($old['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div id="teacher-fields" style="display: none;">
            <div class="form-group">
                <label for="abbreviation">Kürzel <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="abbreviation" name="abbreviation" class="form-control" maxlength="10" aria-describedby="abbreviation_help" value="<?= htmlspecialchars($old['abbreviation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <span class="form-help" id="abbreviation_help">z.B. MÜL für Müller</span>
            </div>

            <div class="form-group">
                <label for="subjects">Fächer</label>
                <input type="text" id="subjects" name="subjects" class="form-control" aria-describedby="subjects_help" value="<?= htmlspecialchars($old['subjects'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <span class="form-help" id="subjects_help">Kommagetrennt, z.B. Mathematik, Deutsch</span>
            </div>
        </div>

        <div id="student-fields" style="display: none;">
            <div class="form-group">
                <label for="class_id">Klasse <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <select name="class_id" id="class_id" class="form-control">
                    <option value="">- Klasse wählen -</option>
                    <?php foreach ($classes ?? [] as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($old['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="birthday">Geburtsdatum</label>
                <input type="date" id="birthday" name="birthday" class="form-control" value="<?= htmlspecialchars($old['birthday'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="guardian_email">Erziehungsberechtigten-E-Mail</label>
                <input type="email" id="guardian_email" name="guardian_email" class="form-control" maxlength="255" value="<?= htmlspecialchars($old['guardian_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div id="aide-fields" style="display: none;">
            <div class="form-group">
                <label for="comment">Kommentar</label>
                <textarea id="comment" name="comment" class="form-control" rows="2" aria-describedby="comment_help"><?= htmlspecialchars($old['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <span class="form-help" id="comment_help">Freitext, z.B. Einsatzzeiten oder Hinweise.</span>
            </div>

            <fieldset class="form-group fieldset-clean">
                <legend>Begleitete Schüler:innen zuweisen</legend>
                <div class="checkbox-scroll">
                    <?php foreach ($students ?? [] as $s): ?>
                    <label>
                        <input type="checkbox" name="student_ids[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $assignedStudentIds ?? []) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname'] . ' (' . $s['class_name'] . ')', ENT_QUOTES, 'UTF-8') ?>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <p class="form-help">Noch keine Schüler:innen vorhanden.</p>
                    <?php endif; ?>
                </div>
            </fieldset>
        </div>

        <p class="form-help" id="invite-note">
            Es wird kein Passwort vergeben: Die Person erhält eine Einladungs-E-Mail mit einem
            Link zum Festlegen des eigenen Passworts. Ist der Mailversand deaktiviert, wird der
            Einladungslink im Anschluss einmalig angezeigt.
        </p>
        <p class="form-help" id="student-note" style="display: none;">
            Für Schüler:innen wird ein Login mit temporärem Passwort erzeugt und anschließend
            einmalig angezeigt.
        </p>

        <div class="btn-group">
            <button type="submit" class="btn">Anlegen</button>
            <a href="/users" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<script src="<?= \OpenClassbook\View::asset('/js/user-form.js') ?>"></script>
