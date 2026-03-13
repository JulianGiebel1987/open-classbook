<h1>Benutzer bearbeiten</h1>

<div class="card">
    <form method="post" action="/users/<?= $user['id'] ?>">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="username">Benutzername *</label>
            <input type="text" id="username" name="username" class="form-control" required value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="email">E-Mail</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group">
            <label for="role">Rolle *</label>
            <select name="role" id="role" class="form-control" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="display:flex; gap:0.5rem;">
            <button type="submit" class="btn">Speichern</button>
            <a href="/users" class="btn btn-secondary">Abbrechen</a>
        </div>
    </form>
</div>
