<?php

namespace OpenClassbook\Middleware;

use OpenClassbook\App;
use OpenClassbook\Models\User;

class AuthMiddleware
{
    public function handle(): bool
    {
        // Session-Timeout prüfen
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

        // Session-Version gegen DB abgleichen (Invalidierung nach Passwort-Reset)
        $dbVersion = User::getSessionVersion((int) $_SESSION['user_id']);
        $sessionVersion = $_SESSION['session_version'] ?? null;

        if ($dbVersion === null || $sessionVersion === null || $dbVersion !== (int) $sessionVersion) {
            session_unset();
            session_destroy();
            session_start();
            App::setFlash('warning', 'Ihre Sitzung wurde invalidiert. Bitte melden Sie sich erneut an.');
            App::redirect('/login');
            return false;
        }

        return true;
    }
}
