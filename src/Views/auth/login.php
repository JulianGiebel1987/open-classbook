<div class="auth-form">
    <h2>Anmelden</h2>
    <form method="post" action="/login">
        <?= \OpenClassbook\View::csrfField() ?>

        <div class="form-group">
            <label for="username">Benutzername oder E-Mail</label>
            <input type="text" id="username" name="username" class="form-control" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>

        <div class="form-group">
            <button type="submit" class="btn" style="width:100%">Anmelden</button>
        </div>

        <div class="text-center">
            <a href="/forgot-password">Passwort vergessen?</a>
        </div>
    </form>
</div>
