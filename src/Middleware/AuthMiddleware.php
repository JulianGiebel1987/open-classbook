<?php

namespace OpenClassbook\Middleware;

use OpenClassbook\App;

class AuthMiddleware
{
    public function handle(): bool
    {
        // Session-Timeout pruefen
        $timeout = App::config('session.timeout') ?? 3600;

        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                session_unset();
                session_destroy();
                session_start();
                App::setFlash('warning', 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.');
                App::redirect('/login');
                return false;
            }
        }

        $_SESSION['last_activity'] = time();

        if (!App::isLoggedIn()) {
            // 2FA-Pending: Nutzer ist halb-authentifiziert, darf nur /two-factor/verify
            if (!empty($_SESSION['2fa_pending'])) {
                App::redirect('/two-factor/verify');
                return false;
            }
            App::redirect('/login');
            return false;
        }

        return true;
    }
}
