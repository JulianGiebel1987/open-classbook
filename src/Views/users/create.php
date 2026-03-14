<div class="page-header">
    <h1>Neuer Benutzer</h1>
</div>

<div class="card">
    <form method="post" action="/users">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="username">Benutzername <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="text" id="username" name="username" class="form-control" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" class="form-control" autocomplete="email">
        </div>

        <div class="form-group">
            <label for="role">Rolle <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <select name="role" id="role" class="form-control" required>
                <option value="">Bitte waehlen</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Passwort <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="password" id="password" name="password" class="form-control" required minlength="10" autocomplete="new-password" aria-describedby="password_help">
            <span class="form-help" id="password_help">Min. 10 Zeichen, Gross- und Kleinbuchstaben, mindestens eine Ziffer.</span>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn">Anlegen</button>
            <a href="/users" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
