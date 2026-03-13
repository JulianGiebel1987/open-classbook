<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Models\Student;

class ClassController
{
    public function index(): void
    {
        $filters = ['school_year' => $_GET['school_year'] ?? ''];
        $classes = SchoolClass::findAll($filters);
        $schoolYears = SchoolClass::getSchoolYears();

        // Schueleranzahl pro Klasse
        foreach ($classes as &$class) {
            $class['student_count'] = Student::countByClassId($class['id']);
        }

        View::render('classes/index', [
            'title' => 'Klassenverwaltung',
            'classes' => $classes,
            'filters' => $filters,
            'schoolYears' => $schoolYears,
        ]);
    }

    public function createForm(): void
    {
        CsrfMiddleware::generateToken();
        View::render('classes/create', [
            'title' => 'Neue Klasse',
            'teachers' => Teacher::findAll(),
        ]);
    }

    public function create(): void
    {
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
        ]);
    }

    public function update(string $id): void
    {
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
        $class = SchoolClass::findById((int) $id);
        if (!$class) {
            App::setFlash('error', 'Klasse nicht gefunden.');
            App::redirect('/classes');
            return;
        }

        $students = Student::findByClassId($class['id']);
        $teachers = SchoolClass::getTeachers($class['id']);

        View::render('classes/show', [
            'title' => 'Klasse ' . $class['name'],
            'class' => $class,
            'students' => $students,
            'teachers' => $teachers,
        ]);
    }
}
