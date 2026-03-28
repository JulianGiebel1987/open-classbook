<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\User;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\AuthService;
use OpenClassbook\Services\Logger;

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
            'classes' => SchoolClass::findAll(),
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

        // Zusaetzliche Validierung fuer Lehrer
        if ($data['role'] === 'lehrer') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist fuer Lehrer erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist fuer Lehrer erforderlich.';
            }
            $abbreviation = trim($_POST['abbreviation'] ?? '');
            if (empty($abbreviation)) {
                $errors[] = 'Kuerzel ist fuer Lehrer erforderlich.';
            } elseif (Teacher::abbreviationExists($abbreviation)) {
                $errors[] = 'Dieses Kuerzel ist bereits vergeben.';
            }
        }

        // Zusaetzliche Validierung fuer Schueler
        if ($data['role'] === 'schueler') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist fuer Schueler erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist fuer Schueler erforderlich.';
            }
            if (empty($_POST['class_id'] ?? '')) {
                $errors[] = 'Klasse ist fuer Schueler erforderlich.';
            }
        }

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('users/create', [
                'title' => 'Neuer Benutzer',
                'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'],
                'classes' => SchoolClass::findAll(),
                'old' => $_POST,
            ]);
            return;
        }

        $userId = User::create($data);

        // Lehrer-Profil anlegen
        if ($data['role'] === 'lehrer') {
            Teacher::create([
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'abbreviation' => trim($_POST['abbreviation']),
                'subjects' => trim($_POST['subjects'] ?? '') ?: null,
            ]);
        }

        // Schueler-Profil anlegen
        if ($data['role'] === 'schueler') {
            Student::create([
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'class_id' => (int) $_POST['class_id'],
            ]);
        }

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

        // Profildaten laden (Lehrer oder Schueler)
        $profile = null;
        if ($user['role'] === 'lehrer') {
            $profile = Teacher::findByUserId($user['id']);
        } elseif ($user['role'] === 'schueler') {
            $profile = Student::findByUserId($user['id']);
        }

        CsrfMiddleware::generateToken();
        View::render('users/edit', [
            'title' => 'Benutzer bearbeiten',
            'user' => $user,
            'profile' => $profile,
            'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'],
            'classes' => SchoolClass::findAll(),
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

        $errors = [];

        // Username-Duplikat pruefen
        if ($data['username'] !== $user['username'] && User::usernameExists($data['username'], $userId)) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }

        // E-Mail-Pflicht fuer Lehrer
        if ($data['role'] === 'lehrer' && empty($data['email'])) {
            $errors[] = 'Fuer Lehrer-Accounts ist eine E-Mail-Adresse erforderlich.';
        }

        // Validierung Lehrer-Profil
        if ($data['role'] === 'lehrer') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist fuer Lehrer erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist fuer Lehrer erforderlich.';
            }
            $abbreviation = trim($_POST['abbreviation'] ?? '');
            $existingTeacher = Teacher::findByUserId($userId);
            if (empty($abbreviation)) {
                $errors[] = 'Kuerzel ist fuer Lehrer erforderlich.';
            } elseif (Teacher::abbreviationExists($abbreviation, $existingTeacher['id'] ?? null)) {
                $errors[] = 'Dieses Kuerzel ist bereits vergeben.';
            }
        }

        // Validierung Schueler-Profil
        if ($data['role'] === 'schueler') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist fuer Schueler erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist fuer Schueler erforderlich.';
            }
            if (empty($_POST['class_id'] ?? '')) {
                $errors[] = 'Klasse ist fuer Schueler erforderlich.';
            }
        }

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('users/edit', [
                'title' => 'Benutzer bearbeiten',
                'user' => $user,
                'profile' => [
                    'firstname' => $_POST['firstname'] ?? '',
                    'lastname'  => $_POST['lastname'] ?? '',
                    'abbreviation' => $_POST['abbreviation'] ?? '',
                    'subjects'  => $_POST['subjects'] ?? '',
                    'class_id'  => $_POST['class_id'] ?? '',
                ],
                'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'],
                'classes' => SchoolClass::findAll(),
                'old' => $_POST,
            ]);
            return;
        }

        User::update($userId, $data);

        // Lehrer-Profil erstellen oder aktualisieren
        if ($data['role'] === 'lehrer') {
            $teacherData = [
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'abbreviation' => trim($_POST['abbreviation']),
                'subjects' => trim($_POST['subjects'] ?? '') ?: null,
            ];
            $existingTeacher = Teacher::findByUserId($userId);
            if ($existingTeacher) {
                Teacher::update($existingTeacher['id'], $teacherData);
            } else {
                Teacher::create($teacherData);
            }
        }

        // Schueler-Profil erstellen oder aktualisieren
        if ($data['role'] === 'schueler') {
            $studentData = [
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'class_id' => (int) $_POST['class_id'],
            ];
            $existingStudent = Student::findByUserId($userId);
            if ($existingStudent) {
                Student::update($existingStudent['id'], $studentData);
            } else {
                Student::create($studentData);
            }
        }

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

        // Passwort einmalig in der Session speichern, nicht im Flash-Message (verhindert Browser-Verlauf-Exposition)
        $_SESSION['reset_password_info'] = [
            'username' => $user['username'],
            'password' => $tempPassword,
        ];
        App::redirect('/users/reset-password-info');
    }

    public function resetPasswordInfo(): void
    {
        $info = $_SESSION['reset_password_info'] ?? null;
        unset($_SESSION['reset_password_info']);

        if (!$info) {
            App::redirect('/users');
            return;
        }

        View::render('users/reset-password-info', [
            'title' => 'Passwort zurueckgesetzt',
            'info' => $info,
        ]);
    }

    public function delete(string $id): void
    {
        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        // Eigenen Account nicht loeschen
        if ($userId === (int) $_SESSION['user_id']) {
            App::setFlash('error', 'Sie koennen Ihren eigenen Account nicht loeschen.');
            App::redirect('/users');
            return;
        }

        // Nur Admins duerfen Nutzer loeschen
        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Nur Administratoren duerfen Benutzer loeschen.');
            App::redirect('/users');
            return;
        }

        $username = $user['username'];

        Logger::audit(
            'delete_user',
            $_SESSION['user_id'] ?? null,
            'User',
            $userId,
            'Benutzer geloescht: ' . $username . ' (Rolle: ' . $user['role'] . ')'
        );

        User::delete($userId);

        App::setFlash('success', 'Benutzer "' . $username . '" und alle zugehoerigen Daten wurden geloescht.');
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
