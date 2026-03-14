<?php

use OpenClassbook\Router;
use OpenClassbook\Controllers\AuthController;
use OpenClassbook\Controllers\DashboardController;
use OpenClassbook\Controllers\UserController;
use OpenClassbook\Controllers\ClassController;
use OpenClassbook\Controllers\ClassbookController;
use OpenClassbook\Controllers\AbsenceStudentController;
use OpenClassbook\Controllers\AbsenceTeacherController;
use OpenClassbook\Controllers\ImportController;
use OpenClassbook\Middleware\AuthMiddleware;
use OpenClassbook\Middleware\CsrfMiddleware;

/** @var Router $router */

// === Oeffentliche Routen (kein Login erforderlich) ===
$router->get('/', [AuthController::class, 'loginForm']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], [CsrfMiddleware::class]);
$router->get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [CsrfMiddleware::class]);

// === Geschuetzte Routen (Login erforderlich) ===
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/change-password', [AuthController::class, 'changePasswordForm'], [AuthMiddleware::class]);
$router->post('/change-password', [AuthController::class, 'changePassword'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Benutzerverwaltung ===
$router->get('/users', [UserController::class, 'index'], [AuthMiddleware::class]);
$router->get('/users/create', [UserController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/users', [UserController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/users/{id}/edit', [UserController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/users/{id}', [UserController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/users/{id}/toggle', [UserController::class, 'toggleActive'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/users/{id}/reset-password', [UserController::class, 'resetPassword'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Klassenverwaltung ===
$router->get('/classes', [ClassController::class, 'index'], [AuthMiddleware::class]);
$router->get('/classes/create', [ClassController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/classes', [ClassController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/classes/{id}', [ClassController::class, 'show'], [AuthMiddleware::class]);
$router->get('/classes/{id}/edit', [ClassController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/classes/{id}', [ClassController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Klassenbuch ===
$router->get('/classbook', [ClassbookController::class, 'index'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}', [ClassbookController::class, 'show'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}/create', [ClassbookController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/classbook/{classId}', [ClassbookController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/classbook/{classId}/export-csv', [ClassbookController::class, 'exportCsv'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}/export-pdf', [ClassbookController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->get('/classbook/entry/{id}/edit', [ClassbookController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/classbook/entry/{id}', [ClassbookController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Schueler-Fehlzeiten ===
$router->get('/absences/students', [AbsenceStudentController::class, 'index'], [AuthMiddleware::class]);
$router->get('/absences/students/create', [AbsenceStudentController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/absences/students', [AbsenceStudentController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/absences/students/{id}/edit', [AbsenceStudentController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/absences/students/{id}', [AbsenceStudentController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/absences/students/{id}/delete', [AbsenceStudentController::class, 'delete'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Lehrer-Fehlzeiten ===
$router->get('/absences/teachers', [AbsenceTeacherController::class, 'index'], [AuthMiddleware::class]);
$router->get('/absences/teachers/self', [AbsenceTeacherController::class, 'selfReportForm'], [AuthMiddleware::class]);
$router->post('/absences/teachers/self', [AbsenceTeacherController::class, 'selfReport'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/absences/teachers/create', [AbsenceTeacherController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/absences/teachers', [AbsenceTeacherController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/absences/teachers/{id}/edit', [AbsenceTeacherController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/absences/teachers/{id}', [AbsenceTeacherController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/absences/teachers/{id}/delete', [AbsenceTeacherController::class, 'delete'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Import ===
$router->get('/import', [ImportController::class, 'index'], [AuthMiddleware::class]);
$router->post('/import/teachers', [ImportController::class, 'uploadTeachers'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/import/teachers/confirm', [ImportController::class, 'confirmTeachers'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/import/students', [ImportController::class, 'uploadStudents'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/import/students/confirm', [ImportController::class, 'confirmStudents'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/import/template/{type}', [ImportController::class, 'downloadTemplate'], [AuthMiddleware::class]);
