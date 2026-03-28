<?php

use OpenClassbook\Router;
use OpenClassbook\Controllers\AuthController;
use OpenClassbook\Controllers\DashboardController;
use OpenClassbook\Controllers\UserController;
use OpenClassbook\Controllers\ClassController;
use OpenClassbook\Controllers\ClassbookController;
use OpenClassbook\Controllers\AbsenceStudentController;
use OpenClassbook\Controllers\AbsenceTeacherController;
use OpenClassbook\Controllers\FileController;
use OpenClassbook\Controllers\ImportController;
use OpenClassbook\Controllers\ListController;
use OpenClassbook\Controllers\MessageController;
use OpenClassbook\Controllers\TimetableController;
use OpenClassbook\Controllers\SubstitutionController;
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
$router->get('/datenschutz', [AuthController::class, 'privacy']);

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
$router->get('/users/reset-password-info', [UserController::class, 'resetPasswordInfo'], [AuthMiddleware::class]);

// === Klassenverwaltung ===
$router->get('/classes', [ClassController::class, 'index'], [AuthMiddleware::class]);
$router->get('/classes/create', [ClassController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/classes', [ClassController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/classes/{id}', [ClassController::class, 'show'], [AuthMiddleware::class]);
$router->get('/classes/{id}/edit', [ClassController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/classes/{id}', [ClassController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/classes/{id}/transfer', [ClassController::class, 'transferStudent'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Klassenbuch ===
$router->get('/classbook', [ClassbookController::class, 'index'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}', [ClassbookController::class, 'show'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}/create', [ClassbookController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/classbook/{classId}', [ClassbookController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/classbook/{classId}/export-csv', [ClassbookController::class, 'exportCsv'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}/export-pdf', [ClassbookController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->get('/classbook/entry/{id}/edit', [ClassbookController::class, 'editForm'], [AuthMiddleware::class]);
$router->post('/classbook/entry/{id}', [ClassbookController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Schuelerbemerkungen
$router->get('/classbook/{classId}/remarks', [ClassbookController::class, 'remarksIndex'], [AuthMiddleware::class]);
$router->get('/classbook/{classId}/remarks/create', [ClassbookController::class, 'remarkCreateForm'], [AuthMiddleware::class]);
$router->post('/classbook/{classId}/remarks', [ClassbookController::class, 'remarkCreate'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/classbook/{classId}/remarks/{id}/delete', [ClassbookController::class, 'remarkDelete'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Schueler-Fehlzeiten ===
$router->get('/absences/students', [AbsenceStudentController::class, 'index'], [AuthMiddleware::class]);
$router->get('/absences/students/self', [AbsenceStudentController::class, 'selfReportForm'], [AuthMiddleware::class]);
$router->post('/absences/students/self', [AbsenceStudentController::class, 'selfReport'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/absences/students/mine', [AbsenceStudentController::class, 'myAbsences'], [AuthMiddleware::class]);
$router->get('/absences/students/create', [AbsenceStudentController::class, 'createForm'], [AuthMiddleware::class]);
$router->get('/absences/students/by-class/{classId}', [AbsenceStudentController::class, 'studentsByClass'], [AuthMiddleware::class]);
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

// === Nachrichten ===
$router->get('/messages', [MessageController::class, 'inbox'], [AuthMiddleware::class]);
$router->get('/messages/new', [MessageController::class, 'newConversation'], [AuthMiddleware::class]);
$router->post('/messages/new', [MessageController::class, 'createConversation'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/messages/{id}', [MessageController::class, 'show'], [AuthMiddleware::class]);
$router->post('/messages/{id}', [MessageController::class, 'send'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/messages/{id}/older', [MessageController::class, 'loadMore'], [AuthMiddleware::class]);

// === Dateiverwaltung ===
$router->get('/files', [FileController::class, 'index'], [AuthMiddleware::class]);
$router->get('/files/private', [FileController::class, 'privateBrowse'], [AuthMiddleware::class]);
$router->get('/files/shared', [FileController::class, 'sharedBrowse'], [AuthMiddleware::class]);
$router->post('/files/upload', [FileController::class, 'upload'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/files/folder', [FileController::class, 'createFolder'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/files/folder/{folderId}', [FileController::class, 'browse'], [AuthMiddleware::class]);
$router->post('/files/folder/{id}/delete', [FileController::class, 'deleteFolder'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/files/{id}/download', [FileController::class, 'download'], [AuthMiddleware::class]);
$router->post('/files/{id}/delete', [FileController::class, 'deleteFile'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Listen ===
$router->get('/lists', [ListController::class, 'index'], [AuthMiddleware::class]);
$router->get('/lists/create', [ListController::class, 'createForm'], [AuthMiddleware::class]);
$router->post('/lists', [ListController::class, 'create'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/cell', [ListController::class, 'saveCell'], [AuthMiddleware::class]);
$router->post('/lists/column/{colId}/delete', [ListController::class, 'deleteColumn'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/row/{rowId}/delete', [ListController::class, 'deleteRow'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/lists/{id}', [ListController::class, 'show'], [AuthMiddleware::class]);
$router->post('/lists/{id}', [ListController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/delete', [ListController::class, 'delete'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/column', [ListController::class, 'addColumn'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/row', [ListController::class, 'addRow'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/lists/{id}/export-csv', [ListController::class, 'exportCsv'], [AuthMiddleware::class]);
$router->get('/lists/{id}/export-pdf', [ListController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->get('/lists/{id}/share', [ListController::class, 'shareForm'], [AuthMiddleware::class]);
$router->post('/lists/{id}/share', [ListController::class, 'share'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/lists/{id}/unshare', [ListController::class, 'removeShare'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Stundenplanung ===
$router->get('/timetable', [TimetableController::class, 'index'], [AuthMiddleware::class]);
$router->get('/timetable/settings', [TimetableController::class, 'settingsForm'], [AuthMiddleware::class]);
$router->post('/timetable/settings', [TimetableController::class, 'saveSettings'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/timetable/my-schedule', [TimetableController::class, 'teacherView'], [AuthMiddleware::class]);
$router->post('/timetable/slot', [TimetableController::class, 'saveSlot'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/timetable/check-conflict', [TimetableController::class, 'checkConflict'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/timetable/slot/{id}/delete', [TimetableController::class, 'deleteSlot'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/timetable/{settingId}/class/select', [TimetableController::class, 'selectClass'], [AuthMiddleware::class]);
$router->get('/timetable/{settingId}/class/{classId}', [TimetableController::class, 'editClass'], [AuthMiddleware::class]);
$router->get('/timetable/{settingId}/class/{classId}/pdf', [TimetableController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->post('/timetable/{settingId}/publish', [TimetableController::class, 'publish'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/timetable/{settingId}/unpublish', [TimetableController::class, 'unpublish'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/timetable/teacher/{teacherId}', [TimetableController::class, 'teacherSchedule'], [AuthMiddleware::class]);
$router->get('/timetable/{settingId}/teacher/{teacherId}/pdf', [TimetableController::class, 'exportTeacherPdf'], [AuthMiddleware::class]);

// === Vertretungsplan ===
$router->get('/substitution', [SubstitutionController::class, 'index'], [AuthMiddleware::class]);
$router->get('/substitution/plan', [SubstitutionController::class, 'plan'], [AuthMiddleware::class]);
$router->get('/substitution/my-substitutions', [SubstitutionController::class, 'teacherView'], [AuthMiddleware::class]);
$router->post('/substitution/assign', [SubstitutionController::class, 'assign'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/substitution/cancel', [SubstitutionController::class, 'cancel'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/substitution/check-conflict', [SubstitutionController::class, 'checkConflict'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/substitution/available-teachers', [SubstitutionController::class, 'availableTeachers'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/substitution/publish', [SubstitutionController::class, 'publish'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/substitution/unpublish', [SubstitutionController::class, 'unpublish'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/substitution/pdf', [SubstitutionController::class, 'exportPdf'], [AuthMiddleware::class]);
$router->post('/substitution/{id}', [SubstitutionController::class, 'update'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/substitution/{id}/delete', [SubstitutionController::class, 'delete'], [AuthMiddleware::class, CsrfMiddleware::class]);

// === Import ===
$router->get('/import', [ImportController::class, 'index'], [AuthMiddleware::class]);
$router->post('/import/teachers', [ImportController::class, 'uploadTeachers'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/import/teachers/confirm', [ImportController::class, 'confirmTeachers'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/import/students', [ImportController::class, 'uploadStudents'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/import/students/confirm', [ImportController::class, 'confirmStudents'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/import/students/credentials', [ImportController::class, 'studentCredentials'], [AuthMiddleware::class]);
$router->get('/import/template/{type}', [ImportController::class, 'downloadTemplate'], [AuthMiddleware::class]);
