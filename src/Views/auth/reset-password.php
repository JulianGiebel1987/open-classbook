<div class="auth-form">
    <h2>Neues Passwort setzen</h2>
    <form method="post" action="/reset-password">
        <?= \OpenClassbook\View::csrfField() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="new_password">Neues Passwort <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="10" autocomplete="new-password" aria-describedby="new_password_help">
            <span class="form-help" id="new_password_help">Min. 10 Zeichen, Gross- und Kleinbuchstaben, mindestens eine Ziffer.</span>
        </div>

        <div class="form-group">
            <label for="confirm_password">Passwort bestaetigen <span aria-hidden="true">*</span><span class="sr-only">(Pflichtfeld)</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
        </div>

        <div class="form-group">
            <button type="submit" class="btn" style="width:100%">Passwort setzen</button>
        </div>
    </form>
</div>
