<h1>Neuer Benutzer</h1>

<div class="card">
    <form method="post" action="/users">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="username">Benutzername *</label>
            <input type="text" id="username" name="username" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" class="form-control">
        </div>

        <div class="form-group">
            <label for="role">Rolle *</label>
            <select name="role" id="role" class="form-control" required>
                <option value="">Bitte waehlen</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password">Passwort * (min. 10 Zeichen, Gross-/Kleinbuchstaben, Ziffer)</label>
            <input type="password" id="password" name="password" class="form-control" required minlength="10">
        </div>

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Anlegen</button>
            <a href="/users" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
