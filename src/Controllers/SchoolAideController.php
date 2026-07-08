<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Database;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\SchoolAide;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\User;
use OpenClassbook\Services\AideService;
use OpenClassbook\Services\ModuleSettings;

/**
 * Modul-Kern der Schulbegleiter:innen-Verwaltung: Liste, Einzelanlage (mit
 * automatischem Benutzerkonto), Bearbeiten (inkl. Kommentar und n:m-Zuweisung
 * zu Schueler:innen) sowie endgueltiges Loeschen (nur Admin).
 */
class SchoolAideController
{
    private const STAFF_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Defense-in-depth zur StaffMiddleware auf Route-Ebene, plus Modul-Check.
     */
    private function requireStaff(): bool
    {
        $role = App::currentUserRole();
        if (!in_array($role, self::STAFF_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren, Schulleitung und Sekretariat dürfen Schulbegleiter:innen verwalten.');
            App::redirect('/dashboard');
            return false;
        }
        if (!ModuleSettings::canAccess('school_aides', $role)) {
            App::setFlash('error', 'Das Modul Schulbegleiter:innen ist für Ihre Rolle nicht zugänglich.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    private function requireAdmin(): bool
    {
        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren dürfen Schulbegleiter:innen endgültig löschen.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireStaff()) return;

        $aides = SchoolAide::findAll();
        // Zugewiesene Kinder je Begleitung fuer die Uebersicht anreichern.
        foreach ($aides as &$aide) {
            $aide['students'] = SchoolAide::getStudents((int) $aide['id']);
        }
        unset($aide);

        View::render('aides/index', [
            'title' => 'Schulbegleiter:innen',
            'aides' => $aides,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen'],
            ]),
        ]);
    }

    public function createForm(): void
    {
        if (!$this->requireStaff()) return;

        CsrfMiddleware::generateToken();
        View::render('aides/create', [
            'title' => 'Neue:n Schulbegleiter:in anlegen',
            'students' => Student::findAll(),
            'assignedStudentIds' => [],
            'old' => [],
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen', 'url' => '/aides'],
                ['label' => 'Neu anlegen'],
            ]),
        ]);
    }

    public function create(): void
    {
        if (!$this->requireStaff()) return;

        [$data, $errors] = $this->validateInput();
        $studentIds = array_map('intval', (array) ($_POST['student_ids'] ?? []));

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('aides/create', [
                'title' => 'Neue:n Schulbegleiter:in anlegen',
                'students' => Student::findAll(),
                'assignedStudentIds' => $studentIds,
                'old' => $_POST,
                'breadcrumbs' => View::breadcrumbs([
                    ['label' => 'Schulbegleiter:innen', 'url' => '/aides'],
                    ['label' => 'Neu anlegen'],
                ]),
            ]);
            return;
        }

        $created = AideService::createAideWithAccount($data);
        SchoolAide::setStudents($created['aide_id'], $studentIds);

        // Zugangsdaten einmalig anzeigen (analog Import / StudentController).
        $_SESSION['import_credentials'] = [$created['credentials']];
        $_SESSION['credentials_back_url'] = '/aides';

        App::setFlash('success', $data['firstname'] . ' ' . $data['lastname']
            . ' wurde angelegt. Zugangsdaten werden angezeigt – bitte notieren!');
        App::redirect('/students/credentials');
    }

    public function editForm(string $id): void
    {
        if (!$this->requireStaff()) return;

        $aide = SchoolAide::findById((int) $id);
        if (!$aide) {
            App::setFlash('error', 'Schulbegleiter:in nicht gefunden.');
            App::redirect('/aides');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('aides/edit', [
            'title' => 'Schulbegleiter:in bearbeiten',
            'aide' => $aide,
            'students' => Student::findAll(),
            'assignedStudentIds' => array_column(SchoolAide::getStudents((int) $id), 'id'),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen', 'url' => '/aides'],
                ['label' => $aide['firstname'] . ' ' . $aide['lastname']],
            ]),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->requireStaff()) return;

        $aide = SchoolAide::findById((int) $id);
        if (!$aide) {
            App::setFlash('error', 'Schulbegleiter:in nicht gefunden.');
            App::redirect('/aides');
            return;
        }

        [$data, $errors] = $this->validateInput();
        $studentIds = array_map('intval', (array) ($_POST['student_ids'] ?? []));

        if (!empty($errors)) {
            App::setFlash('error', implode(' ', $errors));
            CsrfMiddleware::generateToken();
            View::render('aides/edit', [
                'title' => 'Schulbegleiter:in bearbeiten',
                'aide' => array_merge($aide, $_POST),
                'students' => Student::findAll(),
                'assignedStudentIds' => $studentIds,
                'breadcrumbs' => View::breadcrumbs([
                    ['label' => 'Schulbegleiter:innen', 'url' => '/aides'],
                    ['label' => $aide['firstname'] . ' ' . $aide['lastname']],
                ]),
            ]);
            return;
        }

        SchoolAide::update((int) $id, $data);
        SchoolAide::setStudents((int) $id, $studentIds);

        App::setFlash('success', $data['firstname'] . ' ' . $data['lastname'] . ' wurde aktualisiert.');
        App::redirect('/aides');
    }

    public function delete(string $id): void
    {
        if (!$this->requireAdmin()) return;

        $aide = SchoolAide::findById((int) $id);
        if (!$aide) {
            App::setFlash('error', 'Schulbegleiter:in nicht gefunden.');
            App::redirect('/aides');
            return;
        }

        // Begleitung (inkl. Zuweisungen/Abwesenheiten via Cascade) und
        // verknuepftes Benutzerkonto gemeinsam und atomar entfernen.
        Database::beginTransaction();
        try {
            SchoolAide::delete((int) $id);
            if (!empty($aide['user_id'])) {
                User::delete((int) $aide['user_id']);
            }
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            App::setFlash('error', 'Löschen fehlgeschlagen. Bitte erneut versuchen.');
            App::redirect('/aides');
            return;
        }

        App::setFlash('success', $aide['firstname'] . ' ' . $aide['lastname']
            . ' wurde endgültig gelöscht (inkl. Konto, Zuweisungen und Abwesenheiten).');
        App::redirect('/aides');
    }

    /**
     * Gemeinsame Validierung/Sanitierung fuer create und update.
     *
     * @return array{0:array{firstname:string,lastname:string,comment:?string,email:?string},1:string[]}
     */
    private function validateInput(): array
    {
        $errors = [];

        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $comment = trim($_POST['comment'] ?? '') ?: null;
        $emailRaw = trim($_POST['email'] ?? '');

        if ($firstname === '') {
            $errors[] = 'Vorname ist erforderlich.';
        }
        if ($lastname === '') {
            $errors[] = 'Nachname ist erforderlich.';
        }
        if (mb_strlen($firstname) > 100 || mb_strlen($lastname) > 100) {
            $errors[] = 'Vor- und Nachname dürfen höchstens 100 Zeichen lang sein.';
        }

        $email = null;
        if ($emailRaw !== '') {
            if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL) || mb_strlen($emailRaw) > 255) {
                $errors[] = 'Ungültige E-Mail-Adresse.';
            } else {
                $email = $emailRaw;
            }
        }

        return [
            [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'comment' => $comment,
                'email' => $email,
            ],
            $errors,
        ];
    }
}
