<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\AbsenceSchoolAide;
use OpenClassbook\Models\SchoolAide;
use OpenClassbook\Services\ModuleSettings;

/**
 * Abwesenheiten/Krankmeldungen von Schulbegleiter:innen (analog
 * AbsenceTeacherController): Staff verwaltet, die Begleitung selbst meldet
 * sich per Self-Service krank.
 */
class AbsenceSchoolAideController
{
    private const MANAGER_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    private function requireAideAbsenceAccess(): void
    {
        $role = App::currentUserRole();
        if (!in_array($role, self::MANAGER_ROLES)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            exit;
        }
        if (!ModuleSettings::canAccess('school_aides', $role)) {
            App::setFlash('error', 'Das Modul Schulbegleiter:innen ist für Ihre Rolle nicht zugänglich.');
            App::redirect('/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $this->requireAideAbsenceAccess();

        $filters = [
            'type' => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        View::render('absences/aides-index', [
            'title' => 'Schulbegleiter:innen-Abwesenheiten',
            'absences' => AbsenceSchoolAide::findAll($filters),
            'aides' => SchoolAide::findAll(),
            'filters' => $filters,
            'canAccessSubstitution' => ModuleSettings::canAccess('school_aides', App::currentUserRole()),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen-Abwesenheiten'],
            ]),
        ]);
    }

    public function createForm(): void
    {
        $this->requireAideAbsenceAccess();

        CsrfMiddleware::generateToken();
        View::render('absences/aides-create', [
            'title' => 'Schulbegleiter:innen-Abwesenheit eintragen',
            'aides' => SchoolAide::findAll(),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen-Abwesenheiten', 'url' => '/absences/aides'],
                ['label' => 'Abwesenheit eintragen'],
            ]),
        ]);
    }

    public function create(): void
    {
        $this->requireAideAbsenceAccess();

        $data = [
            'aide_id' => (int) ($_POST['aide_id'] ?? 0),
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'type' => $_POST['type'] ?? 'krank',
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => $_SESSION['user_id'],
        ];

        if (empty($data['aide_id']) || empty($data['date_from']) || empty($data['date_to'])) {
            App::setFlash('error', 'Schulbegleiter:in, Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/aides/create');
            return;
        }

        AbsenceSchoolAide::create($data);

        App::setFlash('success', 'Abwesenheit erfolgreich eingetragen.');
        App::redirect('/absences/aides');
    }

    public function selfReportForm(): void
    {
        if (!SchoolAide::getAideIdByUserId((int) $_SESSION['user_id'])) {
            App::setFlash('error', 'Kein Schulbegleiter:innen-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('absences/aides-self', [
            'title' => 'Krankmeldung',
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Krankmeldung'],
            ]),
        ]);
    }

    public function selfReport(): void
    {
        $aideId = SchoolAide::getAideIdByUserId((int) $_SESSION['user_id']);
        if (!$aideId) {
            App::setFlash('error', 'Kein Schulbegleiter:innen-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        $data = [
            'aide_id' => $aideId,
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'type' => $_POST['type'] ?? 'krank',
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => $_SESSION['user_id'],
        ];

        if (empty($data['date_from']) || empty($data['date_to'])) {
            App::setFlash('error', 'Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/aides/self');
            return;
        }

        AbsenceSchoolAide::create($data);

        App::setFlash('success', 'Ihre Krankmeldung wurde erfolgreich eingetragen.');
        App::redirect('/dashboard');
    }

    public function editForm(string $id): void
    {
        $this->requireAideAbsenceAccess();

        $absence = AbsenceSchoolAide::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Abwesenheit nicht gefunden.');
            App::redirect('/absences/aides');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('absences/aides-edit', [
            'title' => 'Abwesenheit bearbeiten',
            'absence' => $absence,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Schulbegleiter:innen-Abwesenheiten', 'url' => '/absences/aides'],
                ['label' => 'Abwesenheit bearbeiten'],
            ]),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAideAbsenceAccess();

        $absence = AbsenceSchoolAide::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Abwesenheit nicht gefunden.');
            App::redirect('/absences/aides');
            return;
        }

        $data = [
            'date_from' => $_POST['date_from'] ?? $absence['date_from'],
            'date_to' => $_POST['date_to'] ?? $absence['date_to'],
            'type' => $_POST['type'] ?? $absence['type'],
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        AbsenceSchoolAide::update($absence['id'], $data);
        App::setFlash('success', 'Abwesenheit aktualisiert.');
        App::redirect('/absences/aides');
    }

    public function delete(string $id): void
    {
        $this->requireAideAbsenceAccess();

        AbsenceSchoolAide::delete((int) $id);
        App::setFlash('success', 'Abwesenheit gelöscht.');
        App::redirect('/absences/aides');
    }
}
