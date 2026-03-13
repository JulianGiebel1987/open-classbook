<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\User;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\AuthService;

class UserController
{
    public function index(): void
    {
        $filters = [
            'role' => $_GET['role'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $users = User::findAll($filters);
        $roles = ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'];

        View::render('users/index', [
            'title' => 'Benutzerverwaltung',
            'users' => $users,
            'filters' => $filters,
            'roles' => $roles,
        ]);
    }

    public function createForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('users/create', [
            'title' => 'Neuer Benutzer',
            'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'],
        ]);
    }

    public function create(): void
    {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? '') ?: null,
            'role' => $_POST['role'] ?? '',
            'password' => $_POST['password'] ?? '',
        ];

        // Validierung
        $errors = $this->validateUser($data);
        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            App::redirect('/users/create');
            return;
        }

        User::create($data);
        App::setFlash('success', 'Benutzer erfolgreich angelegt.');
        App::redirect('/users');
    }

    public function editForm(string $id): void
    {
        $user = User::findById((int) $id);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('users/edit', [
            'title' => 'Benutzer bearbeiten',
            'user' => $user,
            'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'],
        ]);
    }

    public function update(string $id): void
    {
        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? '') ?: null,
            'role' => $_POST['role'] ?? $user['role'],
        ];

        // Username-Duplikat pruefen
        if ($data['username'] !== $user['username'] && User::usernameExists($data['username'], $userId)) {
            App::setFlash('error', 'Dieser Benutzername ist bereits vergeben.');
            App::redirect('/users/' . $userId . '/edit');
            return;
        }

        // E-Mail-Pflicht fuer Lehrer
        if ($data['role'] === 'lehrer' && empty($data['email'])) {
            App::setFlash('error', 'Fuer Lehrer-Accounts ist eine E-Mail-Adresse erforderlich.');
            App::redirect('/users/' . $userId . '/edit');
            return;
        }

        User::update($userId, $data);
        App::setFlash('success', 'Benutzer erfolgreich aktualisiert.');
        App::redirect('/users');
    }

    public function toggleActive(string $id): void
    {
        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        // Eigenen Account nicht deaktivieren
        if ($userId === (int) $_SESSION['user_id']) {
            App::setFlash('error', 'Sie koennen Ihren eigenen Account nicht deaktivieren.');
            App::redirect('/users');
            return;
        }

        User::update($userId, ['active' => $user['active'] ? 0 : 1]);
        $status = $user['active'] ? 'deaktiviert' : 'aktiviert';
        App::setFlash('success', "Benutzer erfolgreich {$status}.");
        App::redirect('/users');
    }

    public function resetPassword(string $id): void
    {
        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        $tempPassword = bin2hex(random_bytes(5));
        User::updatePassword($userId, $tempPassword);
        User::update($userId, ['must_change_password' => 1]);

        App::setFlash('success', 'Passwort zurueckgesetzt. Temporaeres Passwort: ' . $tempPassword);
        App::redirect('/users');
    }

    private function validateUser(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors[] = 'Benutzername ist erforderlich.';
        } elseif (User::usernameExists($data['username'], $excludeId)) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }

        if (!in_array($data['role'], ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'])) {
            $errors[] = 'Ungueltige Rolle.';
        }

        if ($data['role'] === 'lehrer' && empty($data['email'])) {
            $errors[] = 'Fuer Lehrer-Accounts ist eine E-Mail-Adresse erforderlich.';
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $pwErrors = AuthService::validatePassword($data['password']);
            $errors = array_merge($errors, $pwErrors);
        } elseif ($excludeId === null) {
            $errors[] = 'Passwort ist erforderlich.';
        }

        // RBAC: Sekretariat darf keine Admin/Schulleitung/Sekretariat-Accounts anlegen
        $currentRole = App::currentUserRole();
        if ($currentRole === 'sekretariat' && in_array($data['role'], ['admin', 'schulleitung', 'sekretariat'])) {
            $errors[] = 'Sie haben keine Berechtigung, diese Rolle zuzuweisen.';
        }

        return $errors;
    }
}
