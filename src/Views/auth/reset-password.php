<div class="auth-form">
    <h2>Neues Passwort setzen</h2>
    <form method="post" action="/reset-password">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="new_password">Neues Passwort (mind. 10 Zeichen, Gross-/Kleinbuchstaben, Ziffer)</label>
            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="10">
        </div>

        <div class="form-group">
            <label for="confirm_password">Passwort bestaetigen</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>

        <div class="form-group">
            <button type="submit" class="btn" style="width:100%">Passwort setzen</button>
        </div>
    </form>
</div>
