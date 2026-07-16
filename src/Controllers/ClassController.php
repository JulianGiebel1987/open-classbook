<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\StudentClassHistory;
use OpenClassbook\Services\CsvEscaper;

class ClassController
{
    private const STAFF_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Defense-in-depth: sicherstellen, dass der aktuelle Nutzer berechtigt ist.
     * Zusätzlich zur StaffMiddleware auf Route-Ebene.
     */
    private function requireStaff(): bool
    {
        if (!in_array(App::currentUserRole(), self::STAFF_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren, Schulleitung und Sekretariat dürfen die Klassenverwaltung nutzen.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireStaff()) return;

        $filters = ['school_year' => $_GET['school_year'] ?? ''];
        $classes = SchoolClass::findAll($filters);
        $schoolYears = SchoolClass::getSchoolYears();

        // Schüleranzahl pro Klasse
        foreach ($classes as &$class) {
            $class['student_count'] = Student::countByClassId($class['id']);
        }

        View::render('classes/index', [
            'title' => 'Klassenverwaltung',
            'classes' => $classes,
            'filters' => $filters,
            'schoolYears' => $schoolYears,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung'],
            ]),
        ]);
    }

    public function createForm(): void
    {
        if (!$this->requireStaff()) return;

        CsrfMiddleware::generateToken();
        View::render('classes/create', [
            'title' => 'Neue Klasse',
            'teachers' => Teacher::findAll(),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                ['label' => 'Neue Klasse'],
            ]),
        ]);
    }

    public function create(): void
    {
        if (!$this->requireStaff()) return;

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'school_year' => trim($_POST['school_year'] ?? ''),
            'head_teacher_id' => !empty($_POST['head_teacher_id']) ? (int) $_POST['head_teacher_id'] : null,
        ];

        if (empty($data['name']) || empty($data['school_year'])) {
            App::setFlash('error', 'Name und Schuljahr sind erforderlich.');
            App::redirect('/classes/create');
            return;
        }

        $classId = SchoolClass::create($data);

        // Fachlehrer zuweisen
        $teacherIds = $_POST['teacher_ids'] ?? [];
        if (!empty($teacherIds)) {
            SchoolClass::setTeachers($classId, $teacherIds);
        }

        App::setFlash('success', 'Klasse erfolgreich angelegt.');
        App::redirect('/classes');
    }

    public function editForm(string $id): void
    {
        if (!$this->requireStaff()) return;

        $class = SchoolClass::findById((int) $id);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $assignedTeachers = SchoolClass::getTeachers($class['id']);
        $assignedIds = array_column($assignedTeachers, 'id');

        CsrfMiddleware::generateToken();
        View::render('classes/edit', [
            'title' => 'Klasse bearbeiten',
            'class' => $class,
            'teachers' => Teacher::findAll(),
            'assignedTeacherIds' => $assignedIds,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                ['label' => $class['name'], 'url' => '/classes/' . $class['id']],
                ['label' => 'Bearbeiten'],
            ]),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->requireStaff()) return;

        $classId = (int) $id;
        $class = SchoolClass::findById($classId);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'school_year' => trim($_POST['school_year'] ?? ''),
            'head_teacher_id' => !empty($_POST['head_teacher_id']) ? (int) $_POST['head_teacher_id'] : null,
        ];

        SchoolClass::update($classId, $data);

        $teacherIds = $_POST['teacher_ids'] ?? [];
        SchoolClass::setTeachers($classId, $teacherIds);

        App::setFlash('success', 'Klasse erfolgreich aktualisiert.');
        App::redirect('/classes');
    }

    public function show(string $id): void
    {
        if (!$this->requireStaff()) return;

        $class = SchoolClass::findById((int) $id);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $students = Student::findByClassId($class['id']);
        $archivedStudents = Student::findArchivedByClassId($class['id']);
        $teachers = SchoolClass::getTeachers($class['id']);

        // Alle Klassen für Versetzungs-Dropdown laden (ausser aktuelle)
        $allClasses = SchoolClass::findAll();
        $otherClasses = array_filter($allClasses, fn($c) => $c['id'] !== $class['id']);

        $canTransfer = in_array(App::currentUserRole(), self::STAFF_ROLES, true);
        $isAdmin = App::currentUserRole() === 'admin';

        // Versetzungshistorie (nur fuer Staff relevant)
        $history = $canTransfer ? StudentClassHistory::findByClassId($class['id']) : [];

        // Loeschen nur moeglich, wenn keine Schueler:innen (aktiv oder archiviert) zugeordnet sind
        $canDelete = $isAdmin && empty($students) && empty($archivedStudents);

        CsrfMiddleware::generateToken();
        View::render('classes/show', [
            'title' => 'Klasse ' . $class['name'],
            'class' => $class,
            'students' => $students,
            'archivedStudents' => $archivedStudents,
            'teachers' => $teachers,
            'otherClasses' => $otherClasses,
            'history' => $history,
            'canTransfer' => $canTransfer,
            'isAdmin' => $isAdmin,
            'canDelete' => $canDelete,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Klassenverwaltung', 'url' => '/classes'],
                ['label' => $class['name']],
            ]),
        ]);
    }

    public function transferStudent(string $classId): void
    {
        if (!$this->requireStaff()) return;

        $studentId = (int) ($_POST['student_id'] ?? 0);
        $newClassId = (int) ($_POST['new_class_id'] ?? 0);

        $student = Student::findById($studentId);
        if (!$student || $student['class_id'] !== (int) $classId) {
            App::setFlash('error', 'Schüler:in nicht gefunden.');
            App::redirect('/classes/' . $classId);
            return;
        }

        $newClass = SchoolClass::findById($newClassId);
        if (!$newClass) {
            App::setFlash('error', 'Zielklasse nicht gefunden.');
            App::redirect('/classes/' . $classId);
            return;
        }

        Student::changeClass($studentId, $newClassId);
        StudentClassHistory::record($studentId, (int) $classId, $newClassId, $_SESSION['user_id'] ?? null);

        $name = $student['firstname'] . ' ' . $student['lastname'];
        App::setFlash('success', htmlspecialchars($name) . ' wurde in Klasse ' . htmlspecialchars($newClass['name']) . ' versetzt.');
        App::redirect('/classes/' . $classId);
    }

    /**
     * Sammelversetzung: mehrere Schueler:innen gleichzeitig in eine Zielklasse versetzen.
     */
    public function transferStudentsBulk(string $classId): void
    {
        if (!$this->requireStaff()) return;

        $studentIds = $_POST['student_ids'] ?? [];
        $newClassId = (int) ($_POST['new_class_id'] ?? 0);

        if (!is_array($studentIds) || empty($studentIds)) {
            App::setFlash('error', 'Bitte mindestens eine:n Schüler:in auswählen.');
            App::redirect('/classes/' . $classId);
            return;
        }

        $newClass = SchoolClass::findById($newClassId);
        if (!$newClass) {
            App::setFlash('error', 'Zielklasse nicht gefunden.');
            App::redirect('/classes/' . $classId);
            return;
        }

        if ($newClassId === (int) $classId) {
            App::setFlash('error', 'Die Zielklasse muss sich von der aktuellen Klasse unterscheiden.');
            App::redirect('/classes/' . $classId);
            return;
        }

        $moved = 0;
        foreach ($studentIds as $rawId) {
            $studentId = (int) $rawId;
            $student = Student::findById($studentId);
            if (!$student || $student['class_id'] !== (int) $classId) {
                continue;
            }
            Student::changeClass($studentId, $newClassId);
            StudentClassHistory::record($studentId, (int) $classId, $newClassId, $_SESSION['user_id'] ?? null);
            $moved++;
        }

        if ($moved === 0) {
            App::setFlash('error', 'Es wurden keine Schüler:innen versetzt.');
        } else {
            App::setFlash('success', $moved . ' Schüler:in(nen) wurde(n) in Klasse ' . htmlspecialchars($newClass['name']) . ' versetzt.');
        }
        App::redirect('/classes/' . $classId);
    }

    /**
     * Klassenliste als CSV exportieren.
     */
    public function exportCsv(string $id): void
    {
        if (!$this->requireStaff()) return;

        $class = SchoolClass::findById((int) $id);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $students = Student::findByClassId($class['id']);

        $filename = 'klasse_' . preg_replace('/[^a-z0-9_-]/i', '_', $class['name']) . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM fuer Excel UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, CsvEscaper::escapeRow(['Nachname', 'Vorname', 'Geburtsdatum']), ';');
        foreach ($students as $s) {
            $birthday = $s['birthday'] ? date('d.m.Y', strtotime($s['birthday'])) : '';
            fputcsv($output, CsvEscaper::escapeRow([$s['lastname'], $s['firstname'], $birthday]), ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Klassenliste als PDF exportieren.
     */
    public function exportPdf(string $id): void
    {
        if (!$this->requireStaff()) return;

        $class = SchoolClass::findById((int) $id);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $students = Student::findByClassId($class['id']);
        $teachers = SchoolClass::getTeachers($class['id']);

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle('Klasse ' . $class['name']);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Klasse ' . $class['name'], 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Schuljahr: ' . $class['school_year'], 0, 1, 'C');
        if (!empty($teachers)) {
            $teacherNames = array_map(fn($t) => $t['lastname'] . ' ' . $t['firstname'], $teachers);
            $pdf->Cell(0, 6, 'Lehrkräfte: ' . implode(', ', $teacherNames), 0, 1, 'C');
        }
        $pdf->Ln(4);

        if (empty($students)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, 'Keine Schüler:innen in dieser Klasse.', 0, 1, 'C');
        } else {
            $pageWidth = $pdf->getPageWidth() - 30;
            $numWidth = 12;
            $dateWidth = 40;
            $nameWidth = ($pageWidth - $numWidth - $dateWidth) / 2;

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell($numWidth, 8, '#', 1, 0, 'C', true);
            $pdf->Cell($nameWidth, 8, 'Nachname', 1, 0, 'L', true);
            $pdf->Cell($nameWidth, 8, 'Vorname', 1, 0, 'L', true);
            $pdf->Cell($dateWidth, 8, 'Geburtsdatum', 1, 1, 'L', true);

            $pdf->SetFont('helvetica', '', 10);
            $i = 1;
            foreach ($students as $s) {
                if ($pdf->GetY() + 7 > $pdf->getPageHeight() - 15) {
                    $pdf->AddPage();
                }
                $birthday = $s['birthday'] ? date('d.m.Y', strtotime($s['birthday'])) : '-';
                $pdf->Cell($numWidth, 7, (string) $i, 1, 0, 'C');
                $pdf->Cell($nameWidth, 7, $s['lastname'], 1, 0, 'L');
                $pdf->Cell($nameWidth, 7, $s['firstname'], 1, 0, 'L');
                $pdf->Cell($dateWidth, 7, $birthday, 1, 1, 'L');
                $i++;
            }
        }

        $filename = 'klasse_' . preg_replace('/[^a-z0-9_-]/i', '_', $class['name']) . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Klasse loeschen (nur Admin, nur wenn keine Schueler:innen zugeordnet).
     */
    public function delete(string $id): void
    {
        if (!$this->requireStaff()) return;

        if (App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Nur Administratoren dürfen Klassen löschen.');
            App::redirect('/classes/' . $id);
            return;
        }

        $classId = (int) $id;
        $class = SchoolClass::findById($classId);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        // Schutz vor Datenverlust: Klasse muss leer sein (auch keine archivierten Schueler:innen),
        // da students.class_id per ON DELETE CASCADE sonst Schueler + Fehlzeiten mitloescht.
        if (Student::countByClassId($classId, true) > 0) {
            App::setFlash('error', 'Klasse kann nicht gelöscht werden, solange Schüler:innen zugeordnet sind. Bitte zuerst versetzen oder endgültig löschen.');
            App::redirect('/classes/' . $classId);
            return;
        }

        SchoolClass::delete($classId);
        App::setFlash('success', 'Klasse ' . htmlspecialchars($class['name']) . ' wurde gelöscht.');
        App::redirect('/classes');
    }
}
