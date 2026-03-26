<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\ClassbookEntry;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\StudentRemark;
use OpenClassbook\Models\Teacher;

class ClassbookController
{
    public function index(): void
    {
        $classes = $this->getAccessibleClasses();

        View::render('classbook/index', [
            'title' => 'Klassenbuch',
            'classes' => $classes,
        ]);
    }

    public function show(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->hasAccessToClass((int) $classId)) {
            App::setFlash('error', 'Klasse nicht gefunden oder kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'teacher_id' => $_GET['teacher_id'] ?? '',
        ];

        $entries = ClassbookEntry::findByClass($class['id'], $filters);
        $teachers = SchoolClass::getTeachers($class['id']);

        View::render('classbook/show', [
            'title' => 'Klassenbuch ' . $class['name'],
            'class' => $class,
            'entries' => $entries,
            'teachers' => $teachers,
            'filters' => $filters,
        ]);
    }

    public function createForm(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->hasAccessToClass((int) $classId)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('classbook/create', [
            'title' => 'Neuer Klassenbucheintrag',
            'class' => $class,
        ]);
    }

    public function create(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->canCreateEntry((int) $classId)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId && App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Kein Lehrer-Profil gefunden.');
            App::redirect('/classbook/' . $classId);
            return;
        }

        // Admin: Falls kein Lehrer-Profil, verwende den ersten zugewiesenen Lehrer
        if (!$teacherId) {
            $teachers = SchoolClass::getTeachers($class['id']);
            $teacherId = $teachers[0]['id'] ?? null;
            if (!$teacherId) {
                App::setFlash('error', 'Kein Lehrer der Klasse zugewiesen.');
                App::redirect('/classbook/' . $classId);
                return;
            }
        }

        $data = [
            'class_id' => $class['id'],
            'teacher_id' => $teacherId,
            'entry_date' => $_POST['entry_date'] ?? date('Y-m-d'),
            'lesson' => (int) ($_POST['lesson'] ?? 1),
            'topic' => trim($_POST['topic'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        if (empty($data['topic'])) {
            App::setFlash('error', 'Thema ist erforderlich.');
            App::redirect('/classbook/' . $classId . '/create');
            return;
        }

        if ($data['lesson'] < 1 || $data['lesson'] > 10) {
            App::setFlash('error', 'Unterrichtsstunde muss zwischen 1 und 10 liegen.');
            App::redirect('/classbook/' . $classId . '/create');
            return;
        }

        ClassbookEntry::create($data);
        App::setFlash('success', 'Eintrag erfolgreich erstellt.');
        App::redirect('/classbook/' . $classId);
    }

    public function editForm(string $id): void
    {
        $entry = ClassbookEntry::findById((int) $id);
        if (!$entry) {
            App::setFlash('error', 'Eintrag nicht gefunden.');
            App::redirect('/classbook');
            return;
        }

        if (!ClassbookEntry::canEdit($entry, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Sie koennen diesen Eintrag nicht mehr bearbeiten.');
            App::redirect('/classbook/' . $entry['class_id']);
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('classbook/edit', [
            'title' => 'Eintrag bearbeiten',
            'entry' => $entry,
        ]);
    }

    public function update(string $id): void
    {
        $entry = ClassbookEntry::findById((int) $id);
        if (!$entry || !ClassbookEntry::canEdit($entry, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Keine Berechtigung.');
            App::redirect('/classbook');
            return;
        }

        $data = [
            'entry_date' => $_POST['entry_date'] ?? $entry['entry_date'],
            'lesson' => (int) ($_POST['lesson'] ?? $entry['lesson']),
            'topic' => trim($_POST['topic'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ];

        ClassbookEntry::update($entry['id'], $data);
        App::setFlash('success', 'Eintrag aktualisiert.');
        App::redirect('/classbook/' . $entry['class_id']);
    }

    public function exportPdf(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->hasAccessToClass((int) $classId)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $entries = ClassbookEntry::findByClass($class['id'], $filters);

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Open-Classbook');
        $pdf->SetAuthor('Open-Classbook');
        $pdf->SetTitle('Klassenbuch ' . $class['name']);
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Klassenbuch: ' . $class['name'], 0, 1, 'C');

        $dateRange = '';
        if (!empty($filters['date_from'])) {
            $dateRange .= 'Von: ' . date('d.m.Y', strtotime($filters['date_from']));
        }
        if (!empty($filters['date_to'])) {
            $dateRange .= ($dateRange ? '  ' : '') . 'Bis: ' . date('d.m.Y', strtotime($filters['date_to']));
        }
        if ($dateRange) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, $dateRange, 0, 1, 'C');
        }

        $pdf->Ln(5);

        // Tabellenkopf
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(25, 8, 'Datum', 1, 0, 'C', true);
        $pdf->Cell(15, 8, 'Std.', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Lehrkraft', 1, 0, 'C', true);
        $pdf->Cell(110, 8, 'Thema', 1, 0, 'C', true);
        $pdf->Cell(87, 8, 'Notizen', 1, 1, 'C', true);

        // Tabelleninhalt
        $pdf->SetFont('helvetica', '', 9);
        foreach ($entries as $e) {
            $startY = $pdf->GetY();
            $startPage = $pdf->getPage();

            // Berechne benoetigte Hoehe fuer mehrzeilige Zellen
            $topicHeight = $pdf->getStringHeight(110, $e['topic']);
            $notesHeight = $pdf->getStringHeight(87, $e['notes'] ?? '');
            $rowHeight = max(8, $topicHeight, $notesHeight);

            // Seitenumbruch pruefen
            if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();
            }

            $y = $pdf->GetY();
            $pdf->MultiCell(25, $rowHeight, date('d.m.Y', strtotime($e['entry_date'])), 1, 'C', false, 0);
            $pdf->MultiCell(15, $rowHeight, (string) $e['lesson'], 1, 'C', false, 0);
            $pdf->MultiCell(40, $rowHeight, $e['teacher_lastname'] . ', ' . $e['teacher_firstname'], 1, 'L', false, 0);
            $pdf->MultiCell(110, $rowHeight, $e['topic'], 1, 'L', false, 0);
            $pdf->MultiCell(87, $rowHeight, $e['notes'] ?? '', 1, 'L', false, 1);
        }

        // Export auditieren (DSGVO Art. 5 Abs. 2 - Rechenschaftspflicht)
        \OpenClassbook\Services\Logger::audit(
            'export_classbook_pdf',
            $_SESSION['user_id'] ?? null,
            'SchoolClass',
            $class['id'],
            'PDF-Export Klassenbuch: ' . $class['name']
        );

        $filename = 'klassenbuch_' . $class['name'] . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public function exportCsv(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->hasAccessToClass((int) $classId)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        $filters = [
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $entries = ClassbookEntry::findByClass($class['id'], $filters);

        // Export auditieren (DSGVO Art. 5 Abs. 2 - Rechenschaftspflicht)
        \OpenClassbook\Services\Logger::audit(
            'export_classbook_csv',
            $_SESSION['user_id'] ?? null,
            'SchoolClass',
            $class['id'],
            'CSV-Export Klassenbuch: ' . $class['name']
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="klassenbuch_' . $class['name'] . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        // BOM fuer Excel UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Datum', 'Stunde', 'Lehrkraft', 'Thema', 'Notizen'], ';');

        foreach ($entries as $e) {
            fputcsv($output, [
                date('d.m.Y', strtotime($e['entry_date'])),
                $e['lesson'],
                $e['teacher_lastname'] . ', ' . $e['teacher_firstname'],
                $e['topic'],
                $e['notes'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    // === Schuelerbemerkungen ===

    public function remarksIndex(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->hasAccessToClass((int) $classId)) {
            App::setFlash('error', 'Klasse nicht gefunden oder kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        $filters = [
            'student_id' => $_GET['student_id'] ?? '',
            'date_from'  => $_GET['date_from']  ?? '',
            'date_to'    => $_GET['date_to']    ?? '',
        ];

        $remarks  = StudentRemark::findByClass((int) $classId, $filters);
        $students = Student::findByClassId((int) $classId);

        View::render('classbook/remarks-index', [
            'title'    => 'Schuelerbemerkungen – ' . $class['name'],
            'class'    => $class,
            'remarks'  => $remarks,
            'students' => $students,
            'filters'  => $filters,
        ]);
    }

    public function remarkCreateForm(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->canCreateEntry((int) $classId)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/classbook/' . $classId . '/remarks');
            return;
        }

        $students = Student::findByClassId((int) $classId);

        CsrfMiddleware::generateToken();
        View::render('classbook/remarks-create', [
            'title'    => 'Neue Bemerkung',
            'class'    => $class,
            'students' => $students,
        ]);
    }

    public function remarkCreate(string $classId): void
    {
        $class = SchoolClass::findById((int) $classId);
        if (!$class || !$this->canCreateEntry((int) $classId)) {
            App::setFlash('error', 'Kein Zugriff.');
            App::redirect('/classbook');
            return;
        }

        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId && App::currentUserRole() !== 'admin') {
            App::setFlash('error', 'Kein Lehrer-Profil gefunden.');
            App::redirect('/classbook/' . $classId . '/remarks');
            return;
        }

        // Admin ohne Lehrer-Profil: ersten zugewiesenen Lehrer verwenden
        if (!$teacherId) {
            $teachers  = SchoolClass::getTeachers($class['id']);
            $teacherId = $teachers[0]['id'] ?? null;
            if (!$teacherId) {
                App::setFlash('error', 'Kein Lehrer der Klasse zugewiesen.');
                App::redirect('/classbook/' . $classId . '/remarks');
                return;
            }
        }

        $studentId  = (int) ($_POST['student_id'] ?? 0);
        $remark     = trim($_POST['remark'] ?? '');
        $remarkDate = $_POST['remark_date'] ?? date('Y-m-d');

        // Schueler muss zur Klasse gehoeren
        $student = Student::findById($studentId);
        if (!$student || (int) $student['class_id'] !== (int) $classId) {
            App::setFlash('error', 'Schueler nicht in dieser Klasse.');
            App::redirect('/classbook/' . $classId . '/remarks/create');
            return;
        }

        if (empty($remark)) {
            App::setFlash('error', 'Bemerkung darf nicht leer sein.');
            App::redirect('/classbook/' . $classId . '/remarks/create');
            return;
        }

        if (mb_strlen($remark) > 2000) {
            App::setFlash('error', 'Bemerkung darf hoechstens 2000 Zeichen enthalten.');
            App::redirect('/classbook/' . $classId . '/remarks/create');
            return;
        }

        StudentRemark::create([
            'student_id'  => $studentId,
            'class_id'    => $class['id'],
            'teacher_id'  => $teacherId,
            'remark'      => $remark,
            'remark_date' => $remarkDate,
        ]);

        \OpenClassbook\Services\Logger::audit(
            'create_student_remark',
            $_SESSION['user_id'] ?? null,
            'Student',
            $studentId,
            'Bemerkung fuer Schueler-ID ' . $studentId . ' in Klasse ' . $class['name']
        );

        App::setFlash('success', 'Bemerkung gespeichert.');
        App::redirect('/classbook/' . $classId . '/remarks');
    }

    public function remarkDelete(string $classId, string $id): void
    {
        $remark = StudentRemark::findById((int) $id);
        if (!$remark) {
            App::setFlash('error', 'Bemerkung nicht gefunden.');
            App::redirect('/classbook/' . $classId . '/remarks');
            return;
        }

        if (!StudentRemark::canDelete($remark, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Keine Berechtigung zum Loeschen.');
            App::redirect('/classbook/' . $classId . '/remarks');
            return;
        }

        StudentRemark::delete((int) $id);

        \OpenClassbook\Services\Logger::audit(
            'delete_student_remark',
            $_SESSION['user_id'] ?? null,
            'Student',
            $remark['student_id'],
            'Bemerkung geloescht, Schueler-ID ' . $remark['student_id']
        );

        App::setFlash('success', 'Bemerkung geloescht.');
        App::redirect('/classbook/' . $classId . '/remarks');
    }

    private function getAccessibleClasses(): array
    {
        $role = App::currentUserRole();

        if (in_array($role, ['admin', 'schulleitung', 'sekretariat'])) {
            return SchoolClass::findAll();
        }

        // Lehrer: nur eigene Klassen
        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if ($teacherId) {
            return Teacher::getClassesForTeacher($teacherId);
        }

        return [];
    }

    private function hasAccessToClass(int $classId): bool
    {
        $role = App::currentUserRole();

        if (in_array($role, ['admin', 'schulleitung', 'sekretariat'])) {
            return true;
        }

        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId) {
            return false;
        }

        $classes = Teacher::getClassesForTeacher($teacherId);
        foreach ($classes as $class) {
            if ($class['id'] === $classId) {
                return true;
            }
        }

        return false;
    }

    private function canCreateEntry(int $classId): bool
    {
        $role = App::currentUserRole();
        if ($role === 'admin') {
            return true;
        }
        if ($role === 'lehrer') {
            return $this->hasAccessToClass($classId);
        }
        return false;
    }
}
