<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Database;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\AbsenceStudent;

class DashboardController
{
    public function index(): void
    {
        $role = App::currentUserRole();

        switch ($role) {
            case 'admin':
            case 'schulleitung':
            case 'sekretariat':
                $this->adminDashboard();
                break;
            case 'lehrer':
                $this->teacherDashboard();
                break;
            case 'schueler':
                $this->studentDashboard();
                break;
            default:
                View::render('dashboard/index', ['title' => 'Dashboard']);
        }
    }

    private function adminDashboard(): void
    {
        $stats = [
            'teachers' => Database::queryOne('SELECT COUNT(*) as cnt FROM teachers')['cnt'] ?? 0,
            'students' => Database::queryOne('SELECT COUNT(*) as cnt FROM students')['cnt'] ?? 0,
            'classes' => Database::queryOne('SELECT COUNT(*) as cnt FROM classes')['cnt'] ?? 0,
            'absent_teachers_today' => Database::queryOne(
                'SELECT COUNT(*) as cnt FROM absences_teachers WHERE CURDATE() BETWEEN date_from AND date_to'
            )['cnt'] ?? 0,
            'absent_students_today' => Database::queryOne(
                'SELECT COUNT(*) as cnt FROM absences_students WHERE CURDATE() BETWEEN date_from AND date_to'
            )['cnt'] ?? 0,
            'unexcused_absences' => Database::queryOne(
                'SELECT COUNT(*) as cnt FROM absences_students WHERE excused = "offen"'
            )['cnt'] ?? 0,
        ];

        View::render('dashboard/admin', [
            'title' => 'Dashboard',
            'stats' => $stats,
        ]);
    }

    private function teacherDashboard(): void
    {
        $userId = $_SESSION['user_id'];

        // Klassen des Lehrers laden
        $classes = Database::query(
            'SELECT c.* FROM classes c
             JOIN teachers t ON t.user_id = ?
             JOIN class_teacher ct ON ct.class_id = c.id AND ct.teacher_id = t.id',
            [$userId]
        );

        // Auch Klassen laden, bei denen der Lehrer Klassenlehrer ist
        $headClasses = Database::query(
            'SELECT c.* FROM classes c
             JOIN teachers t ON t.user_id = ? AND c.head_teacher_id = t.id',
            [$userId]
        );

        // Zusammenfuehren und Duplikate entfernen
        $allClasses = [];
        foreach (array_merge($classes, $headClasses) as $class) {
            $allClasses[$class['id']] = $class;
        }

        View::render('dashboard/teacher', [
            'title' => 'Mein Dashboard',
            'classes' => array_values($allClasses),
        ]);
    }

    private function studentDashboard(): void
    {
        $student = Student::findByUserId($_SESSION['user_id']);
        $absences = $student ? AbsenceStudent::findAll(['student_id' => $student['id']]) : [];

        View::render('dashboard/student', [
            'title' => 'Mein Dashboard',
            'student' => $student,
            'absences' => $absences,
        ]);
    }
}
