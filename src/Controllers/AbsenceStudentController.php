<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\AbsenceStudent;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\Teacher;

class AbsenceStudentController
{
    public function index(): void
    {
        $filters = [
            'class_id' => $_GET['class_id'] ?? '',
            'excused' => $_GET['excused'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        // Lehrer: nur eigene Klassen
        if (App::currentUserRole() === 'lehrer') {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            $accessibleClasses = $teacherId ? Teacher::getClassesForTeacher($teacherId) : [];
            if (!empty($filters['class_id'])) {
                $allowed = array_column($accessibleClasses, 'id');
                if (!in_array((int) $filters['class_id'], $allowed)) {
                    $filters['class_id'] = '';
                }
            }
        } else {
            $accessibleClasses = SchoolClass::findAll();
        }

        $absences = AbsenceStudent::findAll($filters);

        View::render('absences/students-index', [
            'title' => 'Schueler-Fehlzeiten',
            'absences' => $absences,
            'classes' => $accessibleClasses,
            'filters' => $filters,
        ]);
    }

    public function createForm(): void
    {
        $role = App::currentUserRole();
        if ($role === 'lehrer') {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            $classes = $teacherId ? Teacher::getClassesForTeacher($teacherId) : [];
        } else {
            $classes = SchoolClass::findAll();
        }

        $classId = $_GET['class_id'] ?? '';
        $students = $classId ? Student::findByClassId((int) $classId) : [];

        CsrfMiddleware::generateToken();
        View::render('absences/students-create', [
            'title' => 'Fehlzeit eintragen',
            'classes' => $classes,
            'students' => $students,
            'selectedClassId' => $classId,
        ]);
    }

    public function create(): void
    {
        $data = [
            'student_id' => (int) ($_POST['student_id'] ?? 0),
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'excused' => $_POST['excused'] ?? 'offen',
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => $_SESSION['user_id'],
        ];

        if (empty($data['student_id']) || empty($data['date_from']) || empty($data['date_to'])) {
            App::setFlash('error', 'Schueler, Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/students/create');
            return;
        }

        if ($data['date_to'] < $data['date_from']) {
            App::setFlash('error', 'Das Bis-Datum darf nicht vor dem Von-Datum liegen.');
            App::redirect('/absences/students/create');
            return;
        }

        AbsenceStudent::create($data);
        App::setFlash('success', 'Fehlzeit erfolgreich eingetragen.');
        App::redirect('/absences/students');
    }

    public function editForm(string $id): void
    {
        $absence = AbsenceStudent::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Fehlzeit nicht gefunden.');
            App::redirect('/absences/students');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('absences/students-edit', [
            'title' => 'Fehlzeit bearbeiten',
            'absence' => $absence,
        ]);
    }

    public function update(string $id): void
    {
        $absence = AbsenceStudent::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Fehlzeit nicht gefunden.');
            App::redirect('/absences/students');
            return;
        }

        $data = [
            'date_from' => $_POST['date_from'] ?? $absence['date_from'],
            'date_to' => $_POST['date_to'] ?? $absence['date_to'],
            'excused' => $_POST['excused'] ?? $absence['excused'],
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        AbsenceStudent::update($absence['id'], $data);
        App::setFlash('success', 'Fehlzeit aktualisiert.');
        App::redirect('/absences/students');
    }

    public function delete(string $id): void
    {
        AbsenceStudent::delete((int) $id);
        App::setFlash('success', 'Fehlzeit geloescht.');
        App::redirect('/absences/students');
    }

    /**
     * Schueler-Selbst-Krankmeldung: Formular anzeigen
     */
    public function selfReportForm(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        if (!$student) {
            App::setFlash('error', 'Kein Schueler-Profil gefunden. Bitte wenden Sie sich an das Sekretariat.');
            App::redirect('/dashboard');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('absences/students-self', [
            'title' => 'Krankmeldung',
            'student' => $student,
        ]);
    }

    /**
     * Schueler-Selbst-Krankmeldung: absenden
     */
    public function selfReport(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        if (!$student) {
            App::setFlash('error', 'Kein Schueler-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        $data = [
            'student_id' => $student['id'],
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'excused' => 'offen',
            'reason' => trim($_POST['reason'] ?? '') ?: null,
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => $_SESSION['user_id'],
        ];

        if (empty($data['date_from']) || empty($data['date_to'])) {
            App::setFlash('error', 'Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/students/self');
            return;
        }

        if ($data['date_to'] < $data['date_from']) {
            App::setFlash('error', 'Das Bis-Datum darf nicht vor dem Von-Datum liegen.');
            App::redirect('/absences/students/self');
            return;
        }

        AbsenceStudent::create($data);
        App::setFlash('success', 'Krankmeldung erfolgreich eingereicht.');
        App::redirect('/absences/students/mine');
    }

    /**
     * Eigene Fehlzeiten anzeigen (fuer Schueler)
     */
    public function myAbsences(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        if (!$student) {
            App::setFlash('error', 'Kein Schueler-Profil gefunden.');
            App::redirect('/dashboard');
            return;
        }

        $absences = AbsenceStudent::findAll(['student_id' => $student['id']]);

        View::render('absences/students-mine', [
            'title' => 'Meine Fehlzeiten',
            'absences' => $absences,
            'student' => $student,
        ]);
    }
}
