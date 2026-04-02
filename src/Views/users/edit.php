<div class="page-header">
    <h1>Benutzer bearbeiten</h1>
</div>

<div class="card">
    <form method="post" action="/users/<?= $user['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <?php $old = $old ?? []; ?>

        <div class="form-group">
            <label for="username">Benutzername <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="username" name="username" class="form-control" required value="<?= htmlspecialchars($old['username'] ?? $user['username'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="username">
        </div>

        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="email">
        </div>

        <div class="form-group">
            <label for="role">Rolle <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="role" id="role" class="form-control" required>
                <?php $selectedRole = $old['role'] ?? $user['role']; ?>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= $selectedRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="profile-fields" style="display: none;">
            <div class="form-group">
                <label for="firstname">Vorname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="firstname" name="firstname" class="form-control" value="<?= htmlspecialchars($profile['firstname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="lastname">Nachname <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="lastname" name="lastname" class="form-control" value="<?= htmlspecialchars($profile['lastname'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div id="teacher-fields" style="display: none;">
            <div class="form-group">
                <label for="abbreviation">Kuerzel <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <input type="text" id="abbreviation" name="abbreviation" class="form-control" maxlength="10" value="<?= htmlspecialchars($profile['abbreviation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="subjects">Faecher</label>
                <input type="text" id="subjects" name="subjects" class="form-control" value="<?= htmlspecialchars($profile['subjects'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div id="student-fields" style="display: none;">
            <div class="form-group">
                <label for="class_id">Klasse <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
                <select name="class_id" id="class_id" class="form-control">
                    <option value="">- Klasse waehlen -</option>
                    <?php foreach ($classes ?? [] as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($profile['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Speichern</button>
            <a href="/users" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>

<?php if (\OpenClassbook\App::currentUserRole() === 'admin' && !empty($twoFactorData)): ?>
<div class="card" style="margin-top: var(--spacing-lg);">
    <h2>Zwei-Faktor-Authentifizierung</h2>

    <?php if ($twoFactorData['two_factor_method'] !== 'none' && !empty($twoFactorData['two_factor_confirmed_at'])): ?>
        <p>
            <strong>Status:</strong> Aktiv<br>
            <strong>Methode:</strong> <?= $twoFactorData['two_factor_method'] === 'totp' ? 'Authenticator-App' : 'E-Mail' ?><br>
            <strong>Eingerichtet am:</strong> <?= date('d.m.Y H:i', strtotime($twoFactorData['two_factor_confirmed_at'])) ?>
        </p>

        <form method="post" action="/users/<?= $user['id'] ?>/reset-2fa" style="display: inline;">
            <?= \OpenClassbook\View::csrfField() ?>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Moechten Sie die 2FA fuer diesen Benutzer wirklich zuruecksetzen? Der Benutzer muss 2FA anschliessend neu einrichten.')">2FA zuruecksetzen</button>
        </form>
    <?php else: ?>
        <p><strong>Status:</strong> Nicht eingerichtet</p>
    <?php endif; ?>
</div>
<?php endif; ?>

<script src="/js/user-form.js"></script>
