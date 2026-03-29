<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\ZeugnisTemplate;
use OpenClassbook\Models\ZeugnisInstance;
use OpenClassbook\Models\ZeugnisImage;
use OpenClassbook\Models\Student;
use OpenClassbook\Models\SchoolClass;
use OpenClassbook\Models\User;
use OpenClassbook\Models\Teacher;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\ZeugnisExportService;
use OpenClassbook\Services\ZeugnisPlaceholderService;

class ZeugnisController
{
    private const ALLOWED_ROLES = ['admin', 'schulleitung', 'sekretariat', 'lehrer'];
    private const ADMIN_ROLES   = ['admin', 'schulleitung', 'sekretariat'];

    private function checkRole(): bool
    {
        if (!in_array(App::currentUserRole(), self::ALLOWED_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * List all certificates the current user owns or has access to.
     */
    public function index(): void
    {
        if (!$this->checkRole()) return;

        $instances = ZeugnisInstance::findByUser($_SESSION['user_id']);

        View::render('zeugnis/index', [
            'title'     => 'Meine Zeugnisse',
            'instances' => $instances,
            'userId'    => $_SESSION['user_id'],
        ]);
    }

    /**
     * Browse published templates to start a new certificate.
     */
    public function browse(): void
    {
        if (!$this->checkRole()) return;

        $templates = ZeugnisTemplate::findPublished();

        View::render('zeugnis/browse', [
            'title'     => 'Zeugnisvorlagen',
            'templates' => $templates,
        ]);
    }

    /**
     * Show fill-in form for creating a certificate from a template (single student).
     */
    public function createForm(string $templateId): void
    {
        if (!$this->checkRole()) return;

        $template = ZeugnisTemplate::findById((int) $templateId);
        if (!$template || $template['status'] !== 'published') {
            App::setFlash('error', 'Vorlage nicht verfügbar.');
            App::redirect('/zeugnis/browse');
            return;
        }

        $students = $this->getAccessibleStudents();

        CsrfMiddleware::generateToken();

        View::render('zeugnis/create', [
            'title'    => 'Zeugnis erstellen: ' . htmlspecialchars($template['name']),
            'template' => $template,
            'students' => $students,
        ]);
    }

    /**
     * Create a new certificate instance.
     */
    public function create(string $templateId): void
    {
        if (!$this->checkRole()) return;

        $template = ZeugnisTemplate::findById((int) $templateId);
        if (!$template || $template['status'] !== 'published') {
            App::setFlash('error', 'Vorlage nicht verfügbar.');
            App::redirect('/zeugnis/browse');
            return;
        }

        $studentId = (int) ($_POST['student_id'] ?? 0);
        if (!$studentId) {
            App::setFlash('error', 'Bitte wählen Sie einen Schüler/eine Schülerin aus.');
            App::redirect('/zeugnis/create/' . $templateId);
            return;
        }

        $student = Student::findById($studentId);
        if (!$student) {
            App::setFlash('error', 'Schüler/in nicht gefunden.');
            App::redirect('/zeugnis/create/' . $templateId);
            return;
        }

        try {
            $instanceId = ZeugnisInstance::create([
                'template_id' => (int) $templateId,
                'student_id'  => $studentId,
                'created_by'  => $_SESSION['user_id'],
                'title'       => trim($_POST['title'] ?? '') ?: null,
                'status'      => 'draft',
                'field_values' => '{}',
            ]);

            Logger::audit('create_zeugnis_instance', $_SESSION['user_id'] ?? null, 'zeugnis_instance', $instanceId);
            App::setFlash('success', 'Zeugnis angelegt. Jetzt ausfüllen.');
            App::redirect('/zeugnis/' . $instanceId . '/edit');
        } catch (\PDOException $e) {
            Logger::error('ZeugnisInstance create failed: ' . $e->getMessage());
            App::setFlash('error', 'Fehler beim Anlegen des Zeugnisses.');
            App::redirect('/zeugnis/create/' . $templateId);
        }
    }

    /**
     * Show student selection for batch creation.
     */
    public function batchForm(string $templateId): void
    {
        if (!$this->checkRole()) return;

        $template = ZeugnisTemplate::findById((int) $templateId);
        if (!$template || $template['status'] !== 'published') {
            App::setFlash('error', 'Vorlage nicht verfügbar.');
            App::redirect('/zeugnis/browse');
            return;
        }

        $role = App::currentUserRole();
        if (in_array($role, self::ADMIN_ROLES, true)) {
            $classes = SchoolClass::findAll();
        } else {
            $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
            $classes = $teacherId ? Teacher::getClassesForTeacher($teacherId) : [];
        }

        $students = [];
        $selectedClassId = (int) ($_GET['class_id'] ?? 0);
        if ($selectedClassId) {
            $students = Student::findByClassId($selectedClassId);
        }

        CsrfMiddleware::generateToken();

        View::render('zeugnis/batch', [
            'title'           => 'Zeugnisse für Klasse erstellen',
            'template'        => $template,
            'classes'         => $classes,
            'students'        => $students,
            'selectedClassId' => $selectedClassId,
        ]);
    }

    /**
     * Create certificate instances for multiple students.
     */
    public function batchCreate(string $templateId): void
    {
        if (!$this->checkRole()) return;

        $template = ZeugnisTemplate::findById((int) $templateId);
        if (!$template || $template['status'] !== 'published') {
            App::setFlash('error', 'Vorlage nicht verfügbar.');
            App::redirect('/zeugnis/browse');
            return;
        }

        $studentIds = array_map('intval', (array) ($_POST['student_ids'] ?? []));
        $studentIds = array_filter($studentIds);

        if (empty($studentIds)) {
            App::setFlash('error', 'Bitte wählen Sie mindestens einen Schüler/eine Schülerin aus.');
            App::redirect('/zeugnis/batch/' . $templateId);
            return;
        }

        $created = 0;
        foreach ($studentIds as $studentId) {
            $student = Student::findById($studentId);
            if (!$student) continue;

            try {
                ZeugnisInstance::create([
                    'template_id'  => (int) $templateId,
                    'student_id'   => $studentId,
                    'created_by'   => $_SESSION['user_id'],
                    'status'       => 'draft',
                    'field_values' => '{}',
                ]);
                $created++;
            } catch (\PDOException $e) {
                Logger::error('ZeugnisInstance batch create failed for student ' . $studentId . ': ' . $e->getMessage());
            }
        }

        Logger::audit('batch_create_zeugnis_instances', $_SESSION['user_id'] ?? null, 'zeugnis_template', (int) $templateId, $created . ' instances created');
        App::setFlash('success', $created . ' Zeugnis(se) angelegt.');
        App::redirect('/zeugnis');
    }

    /**
     * Show fill-in form for an existing certificate instance.
     */
    public function editForm(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::hasAccess((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/zeugnis');
            return;
        }

        $canEdit = ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole());
        $fieldValues = json_decode($instance['field_values'] ?? '{}', true) ?: [];
        $canvas = json_decode($instance['template_canvas'] ?? '{"pages":[]}', true)
            ?? ['pages' => [['id' => 'page-1', 'elements' => []]]];

        // Pre-resolve student placeholders for read-only display
        $student = [
            'firstname'     => $instance['student_first_name'],
            'lastname'      => $instance['student_last_name'],
            'birthday'      => $instance['student_birthday'] ?? null,
            'class_name'    => $instance['class_name'] ?? '',
        ];
        $tokens = ZeugnisPlaceholderService::getStudentTokens($student);

        CsrfMiddleware::generateToken();

        View::render('zeugnis/edit', [
            'title'       => 'Zeugnis: ' . htmlspecialchars($instance['student_first_name'] . ' ' . $instance['student_last_name']),
            'instance'    => $instance,
            'canvas'      => $canvas,
            'fieldValues' => $fieldValues,
            'tokens'      => $tokens,
            'canEdit'     => $canEdit,
        ]);
    }

    /**
     * Save full field values for a certificate.
     */
    public function update(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Keine Berechtigung zum Bearbeiten.');
            App::redirect('/zeugnis');
            return;
        }

        // Accept field values as JSON string or as POST array
        $fieldValues = [];
        if (!empty($_POST['field_values'])) {
            $decoded = json_decode($_POST['field_values'], true);
            if (is_array($decoded)) {
                $fieldValues = $decoded;
            }
        } else {
            // Fallback: collect field_* POST params
            foreach ($_POST as $key => $val) {
                if (str_starts_with($key, 'field_')) {
                    $fieldId = substr($key, 6);
                    $fieldValues[$fieldId] = $val;
                }
            }
        }

        $status = ($_POST['status'] ?? 'draft') === 'final' ? 'final' : 'draft';

        ZeugnisInstance::update((int) $id, [
            'title'        => trim($_POST['title'] ?? '') ?: null,
            'status'       => $status,
            'field_values' => json_encode($fieldValues, JSON_UNESCAPED_UNICODE),
        ]);

        Logger::audit('update_zeugnis_instance', $_SESSION['user_id'] ?? null, 'zeugnis_instance', (int) $id);
        App::setFlash('success', 'Zeugnis gespeichert.');
        App::redirect('/zeugnis/' . $id . '/edit');
    }

    /**
     * AJAX: Save a single field value. Expects JSON body: {field_id, value}.
     * Returns JSON {ok: true} or {error: '...'}.
     */
    public function saveField(string $id): void
    {
        header('Content-Type: application/json');

        if (!in_array(App::currentUserRole(), self::ALLOWED_ROLES, true)) {
            echo json_encode(['error' => 'Zugriff verweigert.']);
            return;
        }

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            echo json_encode(['error' => 'Nicht gefunden.']);
            return;
        }

        if (!ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            echo json_encode(['error' => 'Keine Berechtigung.']);
            return;
        }

        // Validate CSRF token from header against session token
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($sessionToken, $csrfToken)) {
            echo json_encode(['error' => 'Ungültiges CSRF-Token.']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $fieldId = $body['field_id'] ?? '';
        $value   = $body['value'] ?? '';

        if ($fieldId === '') {
            echo json_encode(['error' => 'field_id fehlt.']);
            return;
        }

        // Sanitize field_id: only allow alphanumeric, dash, underscore
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $fieldId)) {
            echo json_encode(['error' => 'Ungültige field_id.']);
            return;
        }

        try {
            ZeugnisInstance::updateSingleField((int) $id, $fieldId, $value);
            echo json_encode(['ok' => true]);
        } catch (\Exception $e) {
            Logger::error('ZeugnisInstance saveField failed: ' . $e->getMessage());
            echo json_encode(['error' => 'Speichern fehlgeschlagen.']);
        }
    }

    /**
     * Show share form.
     */
    public function shareForm(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Nur der Ersteller kann Zeugnisse freigeben.');
            App::redirect('/zeugnis');
            return;
        }

        $shares = ZeugnisInstance::getShares((int) $id);
        // Collect users from relevant roles
        $allUsers = array_merge(
            User::findAll(['role' => 'lehrer']),
            User::findAll(['role' => 'schulleitung']),
            User::findAll(['role' => 'sekretariat'])
        );
        // Exclude current user and already-shared users
        $sharedUserIds = array_map('intval', array_column($shares, 'user_id'));
        $users = array_filter($allUsers, fn($u) => (int) $u['id'] !== $_SESSION['user_id'] && !in_array((int) $u['id'], $sharedUserIds));

        CsrfMiddleware::generateToken();

        View::render('zeugnis/share', [
            'title'    => 'Zeugnis freigeben',
            'instance' => $instance,
            'shares'   => $shares,
            'users'    => array_values($users),
        ]);
    }

    /**
     * Add a share.
     */
    public function share(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Keine Berechtigung.');
            App::redirect('/zeugnis');
            return;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        if (!$userId) {
            App::setFlash('error', 'Bitte Benutzer auswählen.');
            App::redirect('/zeugnis/' . $id . '/share');
            return;
        }

        $canEdit = ($_POST['can_edit'] ?? '0') === '1';

        ZeugnisInstance::addShare((int) $id, $userId, $canEdit);
        Logger::audit('share_zeugnis_instance', $_SESSION['user_id'] ?? null, 'zeugnis_instance', (int) $id, 'shared with user ' . $userId);
        App::setFlash('success', 'Zeugnis freigegeben.');
        App::redirect('/zeugnis/' . $id . '/share');
    }

    /**
     * Remove a share.
     */
    public function unshare(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Keine Berechtigung.');
            App::redirect('/zeugnis');
            return;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        ZeugnisInstance::removeShare((int) $id, $userId);
        App::setFlash('success', 'Freigabe entfernt.');
        App::redirect('/zeugnis/' . $id . '/share');
    }

    /**
     * Delete a certificate instance.
     */
    public function delete(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::canEdit((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Keine Berechtigung.');
            App::redirect('/zeugnis');
            return;
        }

        ZeugnisInstance::delete((int) $id);
        Logger::audit('delete_zeugnis_instance', $_SESSION['user_id'] ?? null, 'zeugnis_instance', (int) $id);
        App::setFlash('success', 'Zeugnis gelöscht.');
        App::redirect('/zeugnis');
    }

    /**
     * Export a single certificate as PDF.
     */
    public function exportPdf(string $id): void
    {
        if (!$this->checkRole()) return;

        $instance = ZeugnisInstance::findById((int) $id);
        if (!$instance) {
            App::setFlash('error', 'Zeugnis nicht gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        if (!ZeugnisInstance::hasAccess((int) $id, $_SESSION['user_id'], App::currentUserRole())) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/zeugnis');
            return;
        }

        $template = ZeugnisTemplate::findById((int) $instance['template_id']);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht mehr vorhanden.');
            App::redirect('/zeugnis');
            return;
        }

        $student = [
            'firstname'     => $instance['student_first_name'],
            'lastname'      => $instance['student_last_name'],
            'birthday'      => $instance['student_birthday'] ?? null,
        ];

        $service = new ZeugnisExportService();
        $service->exportSingle($template, $instance, $student);
        exit;
    }

    /**
     * Export multiple certificates as ZIP of PDFs.
     */
    public function batchExportPdf(): void
    {
        if (!$this->checkRole()) return;

        $instanceIds = array_map('intval', (array) ($_POST['instance_ids'] ?? []));
        $instanceIds = array_filter($instanceIds);

        if (empty($instanceIds)) {
            App::setFlash('error', 'Keine Zeugnisse ausgewählt.');
            App::redirect('/zeugnis');
            return;
        }

        $instances = [];
        foreach ($instanceIds as $instanceId) {
            $inst = ZeugnisInstance::findById($instanceId);
            if ($inst && ZeugnisInstance::hasAccess($instanceId, $_SESSION['user_id'], App::currentUserRole())) {
                $instances[] = $inst;
            }
        }

        if (empty($instances)) {
            App::setFlash('error', 'Keine zugänglichen Zeugnisse gefunden.');
            App::redirect('/zeugnis');
            return;
        }

        $service = new ZeugnisExportService();
        $service->exportBatch($instances);
        exit;
    }

    // -------------------------------------------------------------------------

    private function getAccessibleStudents(): array
    {
        $role = App::currentUserRole();
        if (in_array($role, self::ADMIN_ROLES, true)) {
            return Student::findAll();
        }

        // Teachers: only students from their classes
        $teacherId = Teacher::getTeacherIdByUserId($_SESSION['user_id']);
        if (!$teacherId) {
            return [];
        }
        $classes = Teacher::getClassesForTeacher($teacherId);
        $students = [];
        foreach ($classes as $class) {
            $classStudents = Student::findByClassId((int) $class['id']);
            $students = array_merge($students, $classStudents);
        }
        return $students;
    }
}
