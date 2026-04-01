<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\User;
use OpenClassbook\Services\AuthService;

class AuthController
{
    public function loginForm(): void
    {
        if (App::isLoggedIn()) {
            App::redirect('/dashboard');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('auth/login', ['title' => 'Anmelden'], 'auth');
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            App::setFlash('error', 'Bitte Benutzername und Passwort eingeben.');
            App::redirect('/login');
            return;
        }

        $result = AuthService::attempt($username, $password);

        if (!$result['success']) {
            App::setFlash('error', $result['message']);
            App::redirect('/login');
            return;
        }

        if ($result['must_change_password']) {
            App::redirect('/change-password');
            return;
        }

        App::redirect('/dashboard');
    }

    public function logout(): void
    {
        AuthService::logout();
        session_start();
        App::setFlash('success', 'Sie wurden erfolgreich abgemeldet.');
        App::redirect('/login');
    }

    public function changePasswordForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('auth/change-password', [
            'title' => 'Passwort aendern',
            'forced' => (bool) ($_SESSION['user']['must_change_password'] ?? false),
        ]);
    }

    public function changePassword(): void
    {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            App::setFlash('error', 'Die Passwoerter stimmen nicht ueberein.');
            App::redirect('/change-password');
            return;
        }

        $errors = AuthService::validatePassword($newPassword);
        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/change-password');
            return;
        }

        $user = User::findById($_SESSION['user_id']);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            App::setFlash('error', 'Das aktuelle Passwort ist falsch.');
            App::redirect('/change-password');
            return;
        }

        User::updatePassword($user['id'], $newPassword);
        session_regenerate_id(true);
        App::setFlash('success', 'Passwort erfolgreich geaendert.');
        App::redirect('/dashboard');
    }

    public function forgotPasswordForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('auth/forgot-password', ['title' => 'Passwort zuruecksetzen'], 'auth');
    }

    public function forgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            App::setFlash('error', 'Bitte geben Sie Ihre E-Mail-Adresse ein.');
            App::redirect('/forgot-password');
            return;
        }

        // Token generieren (gibt null zurueck wenn E-Mail nicht gefunden)
        AuthService::createResetToken($email);

        // Immer gleiche Meldung anzeigen (verhindert User-Enumeration)
        App::setFlash('success', 'Wenn ein Account mit dieser E-Mail existiert, erhalten Sie eine E-Mail mit weiteren Anweisungen.');
        App::redirect('/login');
    }

    public function resetPasswordForm(string $token): void
    {
        $user = User::findByResetToken(hash('sha256', $token));

        if (!$user) {
            App::setFlash('error', 'Ungueltiger oder abgelaufener Link.');
            App::redirect('/login');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('auth/reset-password', [
            'title' => 'Neues Passwort setzen',
            'token' => $token,
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = User::findByResetToken(hash('sha256', $token));

        if (!$user) {
            App::setFlash('error', 'Ungueltiger oder abgelaufener Link.');
            App::redirect('/login');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            App::setFlash('error', 'Die Passwoerter stimmen nicht ueberein.');
            App::redirect('/reset-password/' . $token);
            return;
        }

        $errors = AuthService::validatePassword($newPassword);
        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/reset-password/' . $token);
            return;
        }

        User::updatePassword($user['id'], $newPassword);
        User::clearResetToken($user['id']);

        App::setFlash('success', 'Passwort erfolgreich geaendert. Sie koennen sich jetzt anmelden.');
        App::redirect('/login');
    }

    public function privacy(): void
    {
        $layout = App::isLoggedIn() ? 'main' : 'auth';
        View::render('auth/privacy', ['title' => 'Datenschutzhinweise'], $layout);
    }
}
