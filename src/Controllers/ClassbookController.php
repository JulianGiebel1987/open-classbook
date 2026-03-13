<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\ClassbookEntry;
use OpenClassbook\Models\SchoolClass;
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
