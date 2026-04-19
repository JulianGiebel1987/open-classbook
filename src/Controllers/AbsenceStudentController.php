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
    /**
     * Liefert die IDs der Klassen, auf die der aktuelle Nutzer zugreifen darf.
     * - Lehrkraefte: nur zugewiesene Klassen
     * - Admin/Schulleitung/Sekretariat: null (= alle)
     * - Schueler: leeres Array (kein Zugriff auf Fehlzeiten-Verwaltung)
     */
    private function accessibleClassIds(): ?array
    {
        $role = App::currentUserRole();
        if (in_array($role, ['admin', 'schulleitung', 'sekretariat'], true)) {
            return null;
        }
        if ($role === 'lehrer') {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            if (!$teacherId) {
                return [];
            }
            return array_map('intval', array_column(Teacher::getClassesForTeacher($teacherId), 'id'));
        }
        return [];
    }

    /**
     * Prueft, ob der aktuelle Nutzer auf die Fehlzeiten dieser/s Schueler:in
     * zugreifen darf (anhand der Klassenzuordnung).
     */
    private function canAccessStudent(int $studentId): bool
    {
        $student = Student::findById($studentId);
        if (!$student) {
            return false;
        }
        $allowed = $this->accessibleClassIds();
        if ($allowed === null) {
            return true; // Staff: Vollzugriff
        }
        return in_array((int) $student['class_id'], $allowed, true);
    }

    public function index(): void
    {
        // Schüler: nur eigene Fehlzeiten anzeigen
        if (App::currentUserRole() === 'schueler') {
            App::redirect('/absences/students/mine');
            return;
        }

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
            $allowedIds = array_map('intval', array_column($accessibleClasses, 'id'));
            // Nutzer-Filter gegen Whitelist pruefen
            if (!empty($filters['class_id']) && !in_array((int) $filters['class_id'], $allowedIds, true)) {
                $filters['class_id'] = '';
            }
            // Harter Tenant-Scope im Model erzwingen
            $filters['class_ids'] = $allowedIds;
        } else {
            $accessibleClasses = SchoolClass::findAll();
        }

        $absences = AbsenceStudent::findAll($filters);
        $currentRole = App::currentUserRole();

        View::render('absences/students-index', [
            'title' => 'Schüler:innen-Fehlzeiten',
            'absences' => $absences,
            'classes' => $accessibleClasses,
            'filters' => $filters,
            // Fehlzeitengruende nur für Sekretariat/Admin sichtbar (Art. 5 Abs. 1 lit. c DSGVO)
            'canViewReason' => in_array($currentRole, ['admin', 'schulleitung', 'sekretariat'], true),
        ]);
    }

    public function createForm(): void
    {
        $role = App::currentUserRole();
        if ($role === 'schueler') {
            App::redirect('/absences/students/self');
            return;
        }
        if ($role === 'lehrer') {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            $classes = $teacherId ? Teacher::getClassesForTeacher($teacherId) : [];
        } else {
            $classes = SchoolClass::findAll();
        }

        CsrfMiddleware::generateToken();
        View::render('absences/students-create', [
            'title' => 'Fehlzeit eintragen',
            'classes' => $classes,
            'selectedClassId' => $_GET['class_id'] ?? '',
        ]);
    }

    public function create(): void
    {
        if (App::currentUserRole() === 'schueler') {
            App::redirect('/absences/students/self');
            return;
        }

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
            App::setFlash('error', 'Schüler, Von-Datum und Bis-Datum sind erforderlich.');
            App::redirect('/absences/students/create');
            return;
        }

        // Tenant-Scope: Lehrkraft darf nur fuer Schueler:innen ihrer Klassen erfassen
        if (!$this->canAccessStudent($data['student_id'])) {
            App::setFlash('error', 'Sie haben keinen Zugriff auf diese/n Schüler:in.');
            App::redirect('/absences/students');
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
        if (App::currentUserRole() === 'schueler') {
            App::redirect('/absences/students/mine');
            return;
        }

        $absence = AbsenceStudent::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Fehlzeit nicht gefunden.');
            App::redirect('/absences/students');
            return;
        }

        if (!$this->canAccessStudent((int) $absence['student_id'])) {
            App::setFlash('error', 'Sie haben keinen Zugriff auf diese Fehlzeit.');
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
        if (App::currentUserRole() === 'schueler') {
            App::redirect('/absences/students/mine');
            return;
        }

        $absence = AbsenceStudent::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Fehlzeit nicht gefunden.');
            App::redirect('/absences/students');
            return;
        }

        if (!$this->canAccessStudent((int) $absence['student_id'])) {
            App::setFlash('error', 'Sie haben keinen Zugriff auf diese Fehlzeit.');
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
        if (App::currentUserRole() === 'schueler') {
            App::redirect('/absences/students/mine');
            return;
        }

        $absence = AbsenceStudent::findById((int) $id);
        if (!$absence) {
            App::setFlash('error', 'Fehlzeit nicht gefunden.');
            App::redirect('/absences/students');
            return;
        }

        if (!$this->canAccessStudent((int) $absence['student_id'])) {
            App::setFlash('error', 'Sie haben keinen Zugriff auf diese Fehlzeit.');
            App::redirect('/absences/students');
            return;
        }

        AbsenceStudent::delete((int) $absence['id']);
        App::setFlash('success', 'Fehlzeit gelöscht.');
        App::redirect('/absences/students');
    }

    /**
     * API: Schüler einer Klasse als JSON zurückgeben
     */
    public function studentsByClass(string $classId): void
    {
        $role = App::currentUserRole();

        // Lehrer: nur eigene Klassen erlauben
        if ($role === 'lehrer') {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            $allowed = $teacherId ? array_column(Teacher::getClassesForTeacher($teacherId), 'id') : [];
            if (!in_array((int) $classId, $allowed)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Zugriff verweigert']);
                return;
            }
        }

        $students = Student::findByClassId((int) $classId);
        header('Content-Type: application/json');
        echo json_encode($students);
    }

    /**
     * Schüler-Selbst-Krankmeldung: Formular anzeigen
     */
    public function selfReportForm(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        if (!$student) {
            App::setFlash('error', 'Kein Schüler-Profil gefunden. Bitte wenden Sie sich an das Sekretariat.');
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
     * Schüler-Selbst-Krankmeldung: absenden
     */
    public function selfReport(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        if (!$student) {
            App::setFlash('error', 'Kein Schüler-Profil gefunden.');
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
     * Eigene Fehlzeiten anzeigen (für Schüler)
     */
    public function myAbsences(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        if (!$student) {
            App::setFlash('error', 'Kein Schüler-Profil gefunden.');
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
