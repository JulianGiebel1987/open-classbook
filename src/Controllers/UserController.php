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
use OpenClassbook\Services\NotificationService;

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

        // Zusätzliche Validierung für Lehrkräfte
        if ($data['role'] === 'lehrer') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist für Lehrkräfte erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist für Lehrkräfte erforderlich.';
            }
            $abbreviation = trim($_POST['abbreviation'] ?? '');
            if (empty($abbreviation)) {
                $errors[] = 'Kürzel ist für Lehrkräfte erforderlich.';
            } elseif (Teacher::abbreviationExists($abbreviation)) {
                $errors[] = 'Dieses Kürzel ist bereits vergeben.';
            }
        }

        // Zusätzliche Validierung für Schüler:innen
        if ($data['role'] === 'schueler') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist für Schüler:innen erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist für Schüler:innen erforderlich.';
            }
            if (empty($_POST['class_id'] ?? '')) {
                $errors[] = 'Klasse ist für Schüler:innen erforderlich.';
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

        // Lehrkraft-Profil anlegen
        if ($data['role'] === 'lehrer') {
            Teacher::create([
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'abbreviation' => trim($_POST['abbreviation']),
                'subjects' => trim($_POST['subjects'] ?? '') ?: null,
            ]);
        }

        // Schüler:in-Profil anlegen
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

        // Profildaten laden (Lehrkraft oder Schüler:in)
        $profile = null;
        if ($user['role'] === 'lehrer') {
            $profile = Teacher::findByUserId($user['id']);
        } elseif ($user['role'] === 'schueler') {
            $profile = Student::findByUserId($user['id']);
        }

        $twoFactorData = User::getTwoFactorData($user['id']);

        CsrfMiddleware::generateToken();
        View::render('users/edit', [
            'title' => 'Benutzer bearbeiten',
            'user' => $user,
            'profile' => $profile,
            'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler'],
            'classes' => SchoolClass::findAll(),
            'twoFactorData' => $twoFactorData,
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

        // Username-Duplikat prüfen
        if ($data['username'] !== $user['username'] && User::usernameExists($data['username'], $userId)) {
            $errors[] = 'Dieser Benutzername ist bereits vergeben.';
        }

        // E-Mail-Pflicht für Lehrkräfte
        if ($data['role'] === 'lehrer' && empty($data['email'])) {
            $errors[] = 'Für Lehrkräfte-Accounts ist eine E-Mail-Adresse erforderlich.';
        }

        // Validierung Lehrkraft-Profil
        if ($data['role'] === 'lehrer') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist für Lehrkräfte erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist für Lehrkräfte erforderlich.';
            }
            $abbreviation = trim($_POST['abbreviation'] ?? '');
            $existingTeacher = Teacher::findByUserId($userId);
            if (empty($abbreviation)) {
                $errors[] = 'Kürzel ist für Lehrkräfte erforderlich.';
            } elseif (Teacher::abbreviationExists($abbreviation, $existingTeacher['id'] ?? null)) {
                $errors[] = 'Dieses Kürzel ist bereits vergeben.';
            }
        }

        // Validierung Schüler:in-Profil
        if ($data['role'] === 'schueler') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist für Schüler:innen erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist für Schüler:innen erforderlich.';
            }
            if (empty($_POST['class_id'] ?? '')) {
                $errors[] = 'Klasse ist für Schüler:innen erforderlich.';
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

        // Lehrkraft-Profil erstellen oder aktualisieren
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

        // Schüler:in-Profil erstellen oder aktualisieren
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
            App::setFlash('error', 'Sie können Ihren eigenen Account nicht deaktivieren.');
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

        $tempPassword = bin2hex(random_bytes(16));
        User::updatePassword($userId, $tempPassword);
        User::update($userId, ['must_change_password' => 1]);

        // Passwort einmalig in der Session speichern, nicht im Flash-Message (verhindert Browser-Verlauf-Exposition)
        $_SESSION['reset_password_info'] = [
            'user_id'  => $userId,
            'username' => $user['username'],
            'email'    => $user['email'] ?? '',
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

        // Für optionalen E-Mail-Versand in separater Session-Variable aufbewahren
        $_SESSION['temp_password_for_email'] = $info;

        $mailEnabled = (bool) App::config('mail.enabled') && !empty($info['email']);

        View::render('users/reset-password-info', [
            'title'       => 'Passwort zurückgesetzt',
            'info'        => $info,
            'mailEnabled' => $mailEnabled,
            'csrfToken'   => CsrfMiddleware::generateToken(),
        ]);
    }

    /**
     * Neues zufaelliges Passwort generieren und direkt per E-Mail an den Nutzer senden.
     * Einschritt-Aktion aus der Benutzerliste (kein Umweg ueber Info-Seite).
     */
    public function emailNewPassword(string $id): void
    {
        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        if (empty($user['email'])) {
            App::setFlash('error', 'Für diesen Benutzer ist keine E-Mail-Adresse hinterlegt.');
            App::redirect('/users');
            return;
        }

        if (!App::config('mail.enabled')) {
            App::setFlash('error', 'E-Mail-Versand ist nicht konfiguriert. Bitte E-Mail-Einstellungen pruefen.');
            App::redirect('/users');
            return;
        }

        $newPassword = self::generateRandomPassword();
        User::updatePassword($userId, $newPassword);
        User::update($userId, ['must_change_password' => 1]);
        // Aktive Sessions des Nutzers invalidieren
        User::incrementSessionVersion($userId);

        $sent = NotificationService::sendTemporaryPasswordMail($user['email'], $user['username'], $newPassword);

        if (!$sent) {
            App::setFlash('error', 'Passwort wurde zurueckgesetzt, aber E-Mail-Versand an ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . ' ist fehlgeschlagen.');
            App::redirect('/users');
            return;
        }

        Logger::audit(
            'email_new_password',
            $_SESSION['user_id'] ?? null,
            'User',
            $userId,
            'Neues Passwort per E-Mail gesendet an: ' . $user['username']
        );

        App::setFlash('success', 'Neues Passwort wurde per E-Mail an ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . ' gesendet. Nutzer:in muss es beim naechsten Login aendern.');
        App::redirect('/users');
    }

    /**
     * Zufaelliges Passwort erzeugen, das Komplexitaetsanforderungen erfuellt
     * (mind. 1 Grossbuchstabe, 1 Kleinbuchstabe, 1 Ziffer, Laenge 12).
     */
    private static function generateRandomPassword(int $length = 12): string
    {
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // ohne I, O (Verwechslung)
        $lower  = 'abcdefghijkmnpqrstuvwxyz'; // ohne l, o
        $digits = '23456789';                 // ohne 0, 1

        // Garantiere mind. je 1 Zeichen jeder Klasse
        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        $all = $upper . $lower . $digits;
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Fisher-Yates-Shuffle mit random_int (nicht shuffle(), das nutzt mt_rand)
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    public function sendTempPassword(string $id): void
    {
        $userId = (int) $id;

        $info = $_SESSION['temp_password_for_email'] ?? null;
        unset($_SESSION['temp_password_for_email']);

        if (!$info || (int) ($info['user_id'] ?? 0) !== $userId) {
            App::setFlash('error', 'Zugangsdaten nicht mehr verfügbar. Bitte Passwort erneut zurücksetzen.');
            App::redirect('/users');
            return;
        }

        if (empty($info['email'])) {
            App::setFlash('error', 'Für diesen Benutzer ist keine E-Mail-Adresse hinterlegt.');
            App::redirect('/users');
            return;
        }

        $sent = NotificationService::sendTemporaryPasswordMail($info['email'], $info['username'], $info['password']);

        if ($sent) {
            App::setFlash('success', 'Zugangsdaten wurden per E-Mail an ' . htmlspecialchars($info['email'], ENT_QUOTES, 'UTF-8') . ' gesendet.');
        } else {
            App::setFlash('error', 'E-Mail-Versand fehlgeschlagen. Bitte prüfen Sie die E-Mail-Konfiguration.');
        }

        App::redirect('/users');
    }

    public function resetTwoFactor(string $id): void
    {
        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Nur Administratoren dürfen die 2FA eines Benutzers zurücksetzen.');
            App::redirect('/users');
            return;
        }

        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        User::clearTwoFactor($userId);
        Logger::audit(
            'reset_2fa',
            $_SESSION['user_id'] ?? null,
            'User',
            $userId,
            '2FA zurückgesetzt für: ' . $user['username']
        );

        App::setFlash('success', 'Zwei-Faktor-Authentifizierung für "' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . '" wurde zurückgesetzt.');
        App::redirect('/users/' . $userId . '/edit');
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

        // Eigenen Account nicht löschen
        if ($userId === (int) $_SESSION['user_id']) {
            App::setFlash('error', 'Sie können Ihren eigenen Account nicht löschen.');
            App::redirect('/users');
            return;
        }

        // Nur Admins dürfen Nutzer löschen
        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Nur Administratoren dürfen Benutzer löschen.');
            App::redirect('/users');
            return;
        }

        $username = $user['username'];

        Logger::audit(
            'delete_user',
            $_SESSION['user_id'] ?? null,
            'User',
            $userId,
            'Benutzer gelöscht: ' . $username . ' (Rolle: ' . $user['role'] . ')'
        );

        User::delete($userId);

        App::setFlash('success', 'Benutzer "' . $username . '" und alle zugehörigen Daten wurden gelöscht.');
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
            $errors[] = 'Ungültige Rolle.';
        }

        if ($data['role'] === 'lehrer' && empty($data['email'])) {
            $errors[] = 'Für Lehrkräfte-Accounts ist eine E-Mail-Adresse erforderlich.';
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
