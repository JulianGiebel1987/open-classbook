<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\User;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\SchoolAide;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\AuthService;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\NotificationService;

class UserController
{
    /**
     * Defense-in-depth: sicherstellen, dass der aktuelle Nutzer Admin ist.
     * Zusätzlich zur AdminMiddleware auf Route-Ebene.
     */
    private function requireAdmin(): bool
    {
        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren dürfen die Benutzerverwaltung aufrufen.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireAdmin()) return;

        $filters = [
            'role' => $_GET['role'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $users = User::findAll($filters);
        $roles = ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter'];

        View::render('users/index', [
            'title' => 'Benutzerverwaltung',
            'users' => $users,
            'filters' => $filters,
            'roles' => $roles,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer'],
            ]),
        ]);
    }

    public function createForm(): void
    {
        if (!$this->requireAdmin()) return;

        CsrfMiddleware::generateToken();
        View::render('users/create', [
            'title' => 'Neuer Benutzer',
            'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter'],
            'classes' => SchoolClass::findAll(),
            'students' => Student::findAll(),
            'assignedStudentIds' => [],
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Neuer Benutzer'],
            ]),
        ]);
    }

    public function create(): void
    {
        if (!$this->requireAdmin()) return;


        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? '') ?: null,
            'role' => $_POST['role'] ?? '',
            'password' => $_POST['password'] ?? '',
        ];

        // Lehrkräfte melden sich mit ihrer E-Mail an -> Anmeldename = E-Mail
        if ($data['role'] === 'lehrer') {
            $data['username'] = strtolower(trim($_POST['email'] ?? ''));
        }

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

        // Zusätzliche Validierung für Schulbegleiter:innen
        if ($data['role'] === 'schulbegleiter') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist für Schulbegleiter:innen erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist für Schulbegleiter:innen erforderlich.';
            }
        }

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('users/create', [
                'title' => 'Neuer Benutzer',
                'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter'],
                'classes' => SchoolClass::findAll(),
                'students' => Student::findAll(),
                'assignedStudentIds' => array_map('intval', (array) ($_POST['student_ids'] ?? [])),
                'old' => $_POST,
                'breadcrumbs' => View::breadcrumbs([
                    ['label' => 'Benutzer', 'url' => '/users'],
                    ['label' => 'Neuer Benutzer'],
                ]),
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
                'birthday' => trim($_POST['birthday'] ?? '') ?: null,
                'guardian_email' => trim($_POST['guardian_email'] ?? '') ?: null,
            ]);
        }

        // Schulbegleiter:innen-Profil anlegen
        if ($data['role'] === 'schulbegleiter') {
            $aideId = SchoolAide::create([
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'comment' => trim($_POST['comment'] ?? '') ?: null,
            ]);
            SchoolAide::setStudents($aideId, array_map('intval', (array) ($_POST['student_ids'] ?? [])));
        }

        App::setFlash('success', 'Benutzer erfolgreich angelegt.');
        App::redirect('/users');
    }

    public function editForm(string $id): void
    {
        if (!$this->requireAdmin()) return;

        $user = User::findById((int) $id);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        // Profildaten laden (Lehrkraft, Schüler:in oder Schulbegleiter:in)
        $profile = null;
        $assignedStudentIds = [];
        if ($user['role'] === 'lehrer') {
            $profile = Teacher::findByUserId($user['id']);
        } elseif ($user['role'] === 'schueler') {
            $profile = Student::findByUserId($user['id']);
        } elseif ($user['role'] === 'schulbegleiter') {
            $profile = SchoolAide::findByUserId($user['id']);
            if ($profile) {
                $assignedStudentIds = array_column(SchoolAide::getStudents((int) $profile['id']), 'id');
            }
        }

        $twoFactorData = User::getTwoFactorData($user['id']);

        CsrfMiddleware::generateToken();
        View::render('users/edit', [
            'title' => 'Benutzer bearbeiten',
            'user' => $user,
            'profile' => $profile,
            'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter'],
            'classes' => SchoolClass::findAll(),
            'students' => Student::findAll(),
            'assignedStudentIds' => $assignedStudentIds,
            'twoFactorData' => $twoFactorData,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => $user['username']],
            ]),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->requireAdmin()) return;

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

        // Lehrkräfte melden sich mit ihrer E-Mail an -> Anmeldename = E-Mail
        if ($data['role'] === 'lehrer') {
            $data['username'] = strtolower(trim($_POST['email'] ?? ''));
        }

        $errors = [];

        // Selbst-Deprivilegierung/Eskalation nur kontrolliert zulassen
        if ($userId === (int) $_SESSION['user_id'] && $data['role'] !== $user['role']) {
            $errors[] = 'Sie können Ihre eigene Rolle nicht ändern.';
        }

        // Letzten Admin nicht degradieren
        if ($user['role'] === 'admin' && $data['role'] !== 'admin' && User::countActiveAdmins() <= 1) {
            $errors[] = 'Der letzte aktive Administrator kann nicht degradiert werden.';
        }

        // Username-Duplikat prüfen
        if ($data['username'] !== $user['username'] && User::usernameExists($data['username'], $userId)) {
            $errors[] = ($data['role'] === 'lehrer')
                ? 'Diese E-Mail-Adresse ist bereits als Anmeldename vergeben.'
                : 'Dieser Benutzername ist bereits vergeben.';
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

        // Validierung Schulbegleiter:innen-Profil
        if ($data['role'] === 'schulbegleiter') {
            if (empty(trim($_POST['firstname'] ?? ''))) {
                $errors[] = 'Vorname ist für Schulbegleiter:innen erforderlich.';
            }
            if (empty(trim($_POST['lastname'] ?? ''))) {
                $errors[] = 'Nachname ist für Schulbegleiter:innen erforderlich.';
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
                    'comment'   => $_POST['comment'] ?? '',
                ],
                'roles' => ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter'],
                'classes' => SchoolClass::findAll(),
                'students' => Student::findAll(),
                'assignedStudentIds' => array_map('intval', (array) ($_POST['student_ids'] ?? [])),
                'old' => $_POST,
                'breadcrumbs' => View::breadcrumbs([
                    ['label' => 'Benutzer', 'url' => '/users'],
                    ['label' => $user['username']],
                ]),
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
                'birthday' => trim($_POST['birthday'] ?? '') ?: null,
                'guardian_email' => trim($_POST['guardian_email'] ?? '') ?: null,
            ];
            $existingStudent = Student::findByUserId($userId);
            if ($existingStudent) {
                Student::update($existingStudent['id'], $studentData);
            } else {
                Student::create($studentData);
            }
        }

        // Schulbegleiter:innen-Profil erstellen oder aktualisieren
        if ($data['role'] === 'schulbegleiter') {
            $aideData = [
                'user_id' => $userId,
                'firstname' => trim($_POST['firstname']),
                'lastname' => trim($_POST['lastname']),
                'comment' => trim($_POST['comment'] ?? '') ?: null,
            ];
            $existingAide = SchoolAide::findByUserId($userId);
            if ($existingAide) {
                SchoolAide::update((int) $existingAide['id'], $aideData);
                $aideId = (int) $existingAide['id'];
            } else {
                $aideId = SchoolAide::create($aideData);
            }
            SchoolAide::setStudents($aideId, array_map('intval', (array) ($_POST['student_ids'] ?? [])));
        }

        App::setFlash('success', 'Benutzer erfolgreich aktualisiert.');
        App::redirect('/users');
    }

    public function toggleActive(string $id): void
    {
        if (!$this->requireAdmin()) return;

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

        // Letzten Admin nicht deaktivieren
        if ($user['role'] === 'admin' && $user['active'] && User::countActiveAdmins() <= 1) {
            App::setFlash('error', 'Der letzte aktive Administrator kann nicht deaktiviert werden.');
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
        if (!$this->requireAdmin()) return;

        $userId = (int) $id;
        $user = User::findById($userId);
        if (!$user) {
            App::setFlash('error', 'Benutzer nicht gefunden.');
            App::redirect('/users');
            return;
        }

        // Admin-initiierter Reset: Reset-Token erzeugen (keine Klartext-Passwoerter mehr)
        $token = AuthService::createResetTokenForUserId($userId);
        $resetUrl = rtrim(AuthController::baseUrl(), '/') . '/reset-password/' . $token;

        // Aktive Sessions des Nutzers invalidieren (verhindert Weiterarbeit mit altem Login)
        User::incrementSessionVersion($userId);

        Logger::audit(
            'admin_reset_password',
            $_SESSION['user_id'] ?? null,
            'User',
            $userId,
            'Passwort-Reset ausgeloest fuer: ' . $user['username']
        );

        // Link einmalig in der Session speichern fuer Info-Seite bzw. optionalen Mail-Versand
        $_SESSION['reset_password_info'] = [
            'user_id'  => $userId,
            'username' => $user['username'],
            'email'    => $user['email'] ?? '',
            'reset_url' => $resetUrl,
        ];
        App::redirect('/users/reset-password-info');
    }

    public function resetPasswordInfo(): void
    {
        if (!$this->requireAdmin()) return;

        $info = $_SESSION['reset_password_info'] ?? null;
        unset($_SESSION['reset_password_info']);

        if (!$info) {
            App::redirect('/users');
            return;
        }

        // Reset-Link einmalig fuer optionalen E-Mail-Versand aufbewahren
        $_SESSION['reset_link_for_email'] = $info;

        $mailEnabled = (bool) App::config('mail.enabled') && !empty($info['email']);

        // Seite nie cachen (Browser-Verlauf / Proxy-Schutz)
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');

        View::render('users/reset-password-info', [
            'title'       => 'Passwort-Reset eingeleitet',
            'info'        => $info,
            'mailEnabled' => $mailEnabled,
            'csrfToken'   => CsrfMiddleware::generateToken(),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Passwort zurücksetzen'],
            ]),
        ]);
    }

    /**
     * Passwort-Reset-Link direkt per E-Mail an den Nutzer senden.
     * Einschritt-Aktion aus der Benutzerliste (kein Umweg ueber Info-Seite).
     * Aus Sicherheitsgruenden wird kein Klartext-Passwort mehr versendet.
     */
    public function emailNewPassword(string $id): void
    {
        if (!$this->requireAdmin()) return;

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

        // Reset-Token erzeugen (Klartext-Passwort wird nie erzeugt oder versendet)
        $token = AuthService::createResetTokenForUserId($userId);
        $resetUrl = rtrim(AuthController::baseUrl(), '/') . '/reset-password/' . $token;

        // Aktive Sessions des Nutzers invalidieren
        User::incrementSessionVersion($userId);

        $sent = NotificationService::sendPasswordResetMail($user['email'], $user['username'], $resetUrl);

        if (!$sent) {
            App::setFlash('error', 'Reset-Link konnte nicht an ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . ' gesendet werden. Bitte E-Mail-Konfiguration pruefen.');
            App::redirect('/users');
            return;
        }

        Logger::audit(
            'email_password_reset_link',
            $_SESSION['user_id'] ?? null,
            'User',
            $userId,
            'Passwort-Reset-Link per E-Mail gesendet an: ' . $user['username']
        );

        App::setFlash('success', 'Passwort-Reset-Link wurde per E-Mail an ' . htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') . ' gesendet.');
        App::redirect('/users');
    }

    public function sendTempPassword(string $id): void
    {
        if (!$this->requireAdmin()) return;

        $userId = (int) $id;

        $info = $_SESSION['reset_link_for_email'] ?? null;
        unset($_SESSION['reset_link_for_email']);

        if (!$info || (int) ($info['user_id'] ?? 0) !== $userId) {
            App::setFlash('error', 'Reset-Link nicht mehr verfügbar. Bitte Passwort erneut zurücksetzen.');
            App::redirect('/users');
            return;
        }

        if (empty($info['email'])) {
            App::setFlash('error', 'Für diesen Benutzer ist keine E-Mail-Adresse hinterlegt.');
            App::redirect('/users');
            return;
        }

        $sent = NotificationService::sendPasswordResetMail($info['email'], $info['username'], $info['reset_url']);

        if ($sent) {
            Logger::audit(
                'send_password_reset_link',
                $_SESSION['user_id'] ?? null,
                'User',
                $userId,
                'Reset-Link per E-Mail gesendet an: ' . $info['username']
            );
            App::setFlash('success', 'Reset-Link wurde per E-Mail an ' . htmlspecialchars($info['email'], ENT_QUOTES, 'UTF-8') . ' gesendet.');
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

        // Letzten Admin nicht löschen
        if ($user['role'] === 'admin' && $user['active'] && User::countActiveAdmins() <= 1) {
            App::setFlash('error', 'Der letzte aktive Administrator kann nicht gelöscht werden.');
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
            // Für Lehrkräfte ist der Anmeldename die E-Mail; die fehlende
            // E-Mail wird weiter unten separat gemeldet.
            if (($data['role'] ?? '') !== 'lehrer') {
                $errors[] = 'Benutzername ist erforderlich.';
            }
        } elseif (User::usernameExists($data['username'], $excludeId)) {
            $errors[] = (($data['role'] ?? '') === 'lehrer')
                ? 'Diese E-Mail-Adresse ist bereits als Anmeldename vergeben.'
                : 'Dieser Benutzername ist bereits vergeben.';
        }

        if (!in_array($data['role'], ['admin', 'schulleitung', 'sekretariat', 'lehrer', 'schueler', 'schulbegleiter'])) {
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

        // RBAC: Nur Administratoren dürfen privilegierte Rollen vergeben.
        // Andere Rollen sollten die Benutzerverwaltung ohnehin nicht erreichen
        // (AdminMiddleware + requireAdmin()), dieser Check dient als Defense-in-Depth.
        $currentRole = App::currentUserRole();
        if ($currentRole !== 'admin' && in_array($data['role'], ['admin', 'schulleitung', 'sekretariat'], true)) {
            $errors[] = 'Sie haben keine Berechtigung, diese Rolle zuzuweisen.';
        }

        return $errors;
    }
}
