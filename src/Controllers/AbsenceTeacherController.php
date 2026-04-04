<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\AbsenceTeacher;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Services\NotificationService;
use OpenClassbook\Services\ModuleSettings;

class AbsenceTeacherController
{
    private const MANAGER_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    private function requireTeacherAbsenceAccess(): void
    {
        $role = App::currentUserRole();
        if (!in_array($role, self::MANAGER_ROLES)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/dashboard');
            exit;
        }
        if (!ModuleSettings::isRoleModuleAccessible('teacher_absences', $role)) {
            App::setFlash('error', 'Das Modul Lehrerfehlzeiten ist fuer Ihre Rolle nicht zugaenglich.');
            App::redirect('/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $this->requireTeacherAbsenceAccess();

        $filters = [
            'type' => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $absences = AbsenceTeacher::findAll($filters);
        $teachers = Teacher::findAll();

        View::render('absences/teachers-index', [
            'title' => 'Lehrer-Abwesenheiten',
            'absences' => $absences,
            'teachers' => $teachers,
            'filters' => $filters,
        ]);
    }

    public function createForm(): void
    {
        $this->requireTeacherAbsenceAccess();

        $teachers = Teacher::findAll();

        CsrfMiddleware::generateToken();
        View::render('absences/teachers-create', [
            'title' => 'Lehrer-Abwesenheit eintragen',
            'teachers' => $teachers,
        ]);
    }

    public function create(): void
    {
        $this->requireTeacherAbsenceAccess();

        $data = [
            'teacher_id' => (int) ($_POST['teacher_id'] ?? 0),
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'type' => $_POST['type'] ?? 'krank',
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => $_SESSION['user_id'],
        ];

        if (empty($data['teacher_id']) || empty($data['date_from']) || empty($data['date_to'])) {
            App::setFlash('error', 'Lehrkraft, Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/teachers/create');
            return;
        }

        AbsenceTeacher::create($data);

        // E-Mail-Benachrichtigung bei Krankmeldung
        if ($data['type'] === 'krank') {
            $teacher = Teacher::findById($data['teacher_id']);
            if ($teacher) {
                NotificationService::notifyTeacherAbsence($teacher, $data);
            }
        }

        App::setFlash('success', 'Abwesenheit erfolgreich eingetragen.');
        App::redirect('/absences/teachers');
    }

    public function selfReportForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('absences/teachers-self', [
            'title' => 'Krankmeldung',
        ]);
    }

    public function selfReport(): void
    {
        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId) {
            App::setFlash('error', 'Kein Lehrer-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        $data = [
            'teacher_id' => $teacherId,
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'type' => $_POST['type'] ?? 'krank',
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => $_SESSION['user_id'],
        ];

        if (empty($data['date_from']) || empty($data['date_to'])) {
            App::setFlash('error', 'Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/teachers/self');
            return;
        }

        AbsenceTeacher::create($data);

        // E-Mail-Benachrichtigung bei Krankmeldung
        if ($data['type'] === 'krank') {
            $teacher = Teacher::findById($teacherId);
            if ($teacher) {
                NotificationService::notifyTeacherAbsence($teacher, $data);
            }
        }

        App::setFlash('success', 'Ihre Krankmeldung wurde erfolgreich eingetragen.');
        App::redirect('/dashboard');
    }

    public function editForm(string $id): void
    {
        $this->requireTeacherAbsenceAccess();

        $absence = AbsenceTeacher::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Abwesenheit nicht gefunden.');
            App::redirect('/absences/teachers');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('absences/teachers-edit', [
            'title' => 'Abwesenheit bearbeiten',
            'absence' => $absence,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireTeacherAbsenceAccess();

        $absence = AbsenceTeacher::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Abwesenheit nicht gefunden.');
            App::redirect('/absences/teachers');
            return;
        }

        $data = [
            'date_from' => $_POST['date_from'] ?? $absence['date_from'],
            'date_to' => $_POST['date_to'] ?? $absence['date_to'],
            'type' => $_POST['type'] ?? $absence['type'],
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        AbsenceTeacher::update($absence['id'], $data);
        App::setFlash('success', 'Abwesenheit aktualisiert.');
        App::redirect('/absences/teachers');
    }

    public function delete(string $id): void
    {
        $this->requireTeacherAbsenceAccess();

        AbsenceTeacher::delete((int) $id);
        App::setFlash('success', 'Abwesenheit geloescht.');
        App::redirect('/absences/teachers');
    }
}
