<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Database;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\User;
use OpenClassbook\Services\StudentService;

/**
 * Klassenzentrierte Verwaltung von Schueler:innen: anlegen (mit automatischem
 * Benutzerkonto), bearbeiten, archivieren, wiederherstellen und (nur Admin)
 * endgueltig loeschen. Das Verschieben zwischen Klassen bleibt im
 * ClassController (transferStudent).
 */
class StudentController
{
    private const STAFF_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Defense-in-depth zur StaffMiddleware auf Route-Ebene.
     */
    private function requireStaff(): bool
    {
        if (!in_array(App::currentUserRole(), self::STAFF_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren, Schulleitung und Sekretariat dürfen Schüler:innen verwalten.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * Defense-in-depth zur AdminMiddleware (nur fuer hartes Loeschen).
     */
    private function requireAdmin(): bool
    {
        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren dürfen Schüler:innen endgültig löschen.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public function createForm(string $classId): void
    {
        if (!$this->requireStaff()) return;

        $class = SchoolClass::findById((int) $classId);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('students/create', [
            'title' => 'Neue:n Schüler:in anlegen',
            'class' => $class,
            'old' => [],
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                ['label' => $class['name'], 'url' => '/classes/' . $class['id']],
                ['label' => 'Neue:n Schüler:in anlegen'],
            ]),
        ]);
    }

    public function create(string $classId): void
    {
        if (!$this->requireStaff()) return;

        $class = SchoolClass::findById((int) $classId);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        [$data, $errors] = $this->validateInput();

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('students/create', [
                'title' => 'Neue:n Schüler:in anlegen',
                'class' => $class,
                'old' => $_POST,
                'breadcrumbs' => View::breadcrumbs([
                    ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                    ['label' => $class['name'], 'url' => '/classes/' . $class['id']],
                    ['label' => 'Neue:n Schüler:in anlegen'],
                ]),
            ]);
            return;
        }

        $data['class_id'] = $class['id'];
        $created = StudentService::createStudentWithAccount($data);

        // Zugangsdaten einmalig anzeigen (analog zum Import).
        $_SESSION['import_credentials'] = [$created['credentials']];
        $_SESSION['credentials_back_url'] = '/classes/' . $class['id'];

        App::setFlash('success', $data['firstname'] . ' ' . $data['lastname']
            . ' wurde angelegt. Zugangsdaten werden angezeigt – bitte notieren!');
        App::redirect('/students/credentials');
    }

    /**
     * Zugangsdaten eines gerade angelegten Kontos einmalig anzeigen.
     */
    public function credentials(): void
    {
        if (!$this->requireStaff()) return;

        $credentials = $_SESSION['import_credentials'] ?? [];
        $backUrl = $_SESSION['credentials_back_url'] ?? '/classes';
        unset($_SESSION['import_credentials'], $_SESSION['credentials_back_url']);

        if (empty($credentials)) {
            App::redirect('/classes');
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');

        View::render('students/credentials', [
            'title' => 'Zugangsdaten',
            'credentials' => $credentials,
            'backUrl' => $backUrl,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                ['label' => 'Zugangsdaten'],
            ]),
        ]);
    }

    public function editForm(string $id): void
    {
        if (!$this->requireStaff()) return;

        $student = Student::findById((int) $id);
        if (!$student) {
            App::setFlash('error', 'Schüler:in nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('students/edit', [
            'title' => 'Schüler:in bearbeiten',
            'student' => $student,
            'classes' => SchoolClass::findAll(),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                ['label' => 'Klasse', 'url' => '/classes/' . $student['class_id']],
                ['label' => $student['firstname'] . ' ' . $student['lastname']],
            ]),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->requireStaff()) return;

        $student = Student::findById((int) $id);
        if (!$student) {
            App::setFlash('error', 'Schüler:in nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        [$data, $errors] = $this->validateInput();

        $newClassId = (int) ($_POST['class_id'] ?? 0);
        $targetClass = SchoolClass::findById($newClassId);
        if (!$targetClass) {
            $errors[] = 'Zielklasse nicht gefunden.';
        }

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('students/edit', [
                'title' => 'Schüler:in bearbeiten',
                'student' => array_merge($student, $_POST),
                'classes' => SchoolClass::findAll(),
                'breadcrumbs' => View::breadcrumbs([
                    ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                    ['label' => 'Klasse', 'url' => '/classes/' . $student['class_id']],
                    ['label' => $student['firstname'] . ' ' . $student['lastname']],
                ]),
            ]);
            return;
        }

        Student::update((int) $id, [
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'class_id' => $newClassId,
            'birthday' => $data['birthday'],
            'guardian_email' => $data['guardian_email'],
        ]);

        // Guardian-Mail mit dem verknuepften Benutzerkonto synchron halten.
        if (!empty($student['user_id'])) {
            User::update((int) $student['user_id'], ['email' => $data['guardian_email']]);
        }

        App::setFlash('success', $data['firstname'] . ' ' . $data['lastname'] . ' wurde aktualisiert.');
        App::redirect('/classes/' . $newClassId);
    }

    public function archive(string $id): void
    {
        if (!$this->requireStaff()) return;

        $student = Student::findById((int) $id);
        if (!$student) {
            App::setFlash('error', 'Schüler:in nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        Student::archive((int) $id);
        // Zugehoerigen Login deaktivieren.
        if (!empty($student['user_id'])) {
            User::update((int) $student['user_id'], ['active' => 0]);
        }

        App::setFlash('success', $student['firstname'] . ' ' . $student['lastname'] . ' wurde archiviert.');
        App::redirect('/classes/' . $student['class_id']);
    }

    public function restore(string $id): void
    {
        if (!$this->requireStaff()) return;

        $student = Student::findById((int) $id);
        if (!$student) {
            App::setFlash('error', 'Schüler:in nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        Student::restore((int) $id);
        if (!empty($student['user_id'])) {
            User::update((int) $student['user_id'], ['active' => 1]);
        }

        App::setFlash('success', $student['firstname'] . ' ' . $student['lastname'] . ' wurde wiederhergestellt.');
        App::redirect('/classes/' . $student['class_id']);
    }

    public function delete(string $id): void
    {
        if (!$this->requireAdmin()) return;

        $student = Student::findById((int) $id);
        if (!$student) {
            App::setFlash('error', 'Schüler:in nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $classId = (int) $student['class_id'];

        // Schueler-Datensatz (inkl. Fehlzeiten/Bemerkungen via Cascade) und
        // verknuepftes Benutzerkonto gemeinsam und atomar entfernen.
        Database::beginTransaction();
        try {
            Student::delete((int) $id);
            if (!empty($student['user_id'])) {
                User::delete((int) $student['user_id']);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            App::setFlash('error', 'Löschen fehlgeschlagen. Bitte erneut versuchen.');
            App::redirect('/classes/' . $classId);
            return;
        }

        App::setFlash('success', $student['firstname'] . ' ' . $student['lastname']
            . ' wurde endgültig gelöscht (inkl. Konto, Fehlzeiten und Bemerkungen).');
        App::redirect('/classes/' . $classId);
    }

    /**
     * Gemeinsame Validierung/Sanitierung fuer create und update.
     *
     * @return array{0:array{firstname:string,lastname:string,birthday:?string,guardian_email:?string},1:string[]}
     */
    private function validateInput(): array
    {
        $errors = [];

        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $birthdayRaw = trim($_POST['birthday'] ?? '');
        $guardianEmailRaw = trim($_POST['guardian_email'] ?? '');

        if ($firstname === '') {
            $errors[] = 'Vorname ist erforderlich.';
        }
        if ($lastname === '') {
            $errors[] = 'Nachname ist erforderlich.';
        }
        if (mb_strlen($firstname) > 100 || mb_strlen($lastname) > 100) {
            $errors[] = 'Vor- und Nachname dürfen höchstens 100 Zeichen lang sein.';
        }

        $birthday = null;
        if ($birthdayRaw !== '') {
            $date = \DateTime::createFromFormat('Y-m-d', $birthdayRaw);
            if ($date === false || $date->format('Y-m-d') !== $birthdayRaw) {
                $errors[] = 'Ungültiges Geburtsdatum.';
            } else {
                $birthday = $birthdayRaw;
            }
        }

        $guardianEmail = null;
        if ($guardianEmailRaw !== '') {
            if (!filter_var($guardianEmailRaw, FILTER_VALIDATE_EMAIL) || mb_strlen($guardianEmailRaw) > 255) {
                $errors[] = 'Ungültige Erziehungsberechtigten-E-Mail.';
            } else {
                $guardianEmail = $guardianEmailRaw;
            }
        }

        return [
            [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'birthday' => $birthday,
                'guardian_email' => $guardianEmail,
            ],
            $errors,
        ];
    }
}
