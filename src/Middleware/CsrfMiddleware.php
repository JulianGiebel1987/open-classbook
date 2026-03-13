<?php

namespace OpenClassbook\Middleware;

use OpenClassbook\App;

class CsrfMiddleware
{
    public function handle(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true;
        }

        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (empty($token) || !hash_equals($sessionToken, $token)) {
            App::setFlash('error', 'Ungueltige Anfrage. Bitte versuchen Sie es erneut.');
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            App::redirect($referer);
            return false;
        }

        return true;
    }

    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
