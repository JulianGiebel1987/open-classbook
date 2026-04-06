<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\ZeugnisTemplate;
use OpenClassbook\Models\ZeugnisImage;
use OpenClassbook\Services\Logger;
use OpenClassbook\Services\ZeugnisPlaceholderService;
use OpenClassbook\Services\ModuleSettings;

class ZeugnisTemplateController
{
    private const ADMIN_ROLES = ['admin', 'schulleitung', 'sekretariat'];
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5 MB

    private function requireAdminRole(): bool
    {
        if (!in_array(App::currentUserRole(), self::ADMIN_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return false;
        }
        if (!ModuleSettings::canAccess('templates', App::currentUserRole())) {
            App::setFlash('error', 'Das Modul Vorlagen ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * List all templates.
     */
    public function index(): void
    {
        if (!$this->requireAdminRole()) return;

        $filters = [];
        if (!empty($_GET['status']) && in_array($_GET['status'], ['draft', 'published'], true)) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['school_year'])) {
            $filters['school_year'] = trim($_GET['school_year']);
        }

        $templates = ZeugnisTemplate::findAll($filters);

        View::render('zeugnis/templates/index', [
            'title'     => 'Zeugnisvorlagen',
            'templates' => $templates,
            'filters'   => $filters,
        ]);
    }

    /**
     * Create a blank draft template and redirect to editor.
     * This ensures template_id exists immediately so image upload works.
     */
    public function createForm(): void
    {
        if (!$this->requireAdminRole()) return;

        $id = ZeugnisTemplate::create([
            'name'             => 'Neue Vorlage',
            'template_canvas'  => json_encode(['pages' => [['id' => 'page-1', 'elements' => []]]]),
            'status'           => 'draft',
            'page_orientation' => 'P',
            'page_format'      => 'A4',
            'created_by'       => $_SESSION['user_id'],
        ]);

        App::redirect('/zeugnis/templates/' . $id . '/edit');
    }

    /**
     * Save a new template (POST, JSON body from editor).
     */
    public function create(): void
    {
        if (!$this->requireAdminRole()) return;

        $data = $this->extractTemplateData();
        if ($data === null) return;

        try {
            $id = ZeugnisTemplate::create(array_merge($data, ['created_by' => $_SESSION['user_id']]));
            Logger::audit('create_zeugnis_template', $_SESSION['user_id'] ?? null, 'zeugnis_template', $id);
            App::setFlash('success', 'Vorlage gespeichert.');
            App::redirect('/zeugnis/templates/' . $id . '/edit');
        } catch (\PDOException $e) {
            Logger::error('ZeugnisTemplate create failed: ' . $e->getMessage());
            App::setFlash('error', 'Fehler beim Speichern. Bitte prüfen Sie, ob die Datenbank-Migrationen ausgeführt wurden.');
            App::redirect('/zeugnis/templates/create');
        }
    }

    /**
     * Load template into editor.
     */
    public function editForm(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        CsrfMiddleware::generateToken();

        $defaultCanvas = ['pages' => [['id' => 'page-1', 'elements' => []]]];
        $canvasData = null;

        $rawCanvas = $template['template_canvas'] ?? '';
        if ($rawCanvas !== '' && $rawCanvas !== null) {
            $canvasData = json_decode($rawCanvas, true);
        }

        // Ensure canvas has valid pages structure
        if (!is_array($canvasData) || !isset($canvasData['pages']) || !is_array($canvasData['pages'])) {
            Logger::warning('Zeugnis template canvas invalid or empty, using default', [
                'template_id' => (int) $id,
                'raw_value_length' => is_string($rawCanvas) ? strlen($rawCanvas) : 0,
                'json_error' => json_last_error_msg(),
            ]);
            $canvasData = $defaultCanvas;
        }

        View::render('zeugnis/templates/editor', [
            'title'      => 'Vorlage bearbeiten: ' . htmlspecialchars($template['name']),
            'template'   => $template,
            'canvasData' => $canvasData,
            'tokens'     => ZeugnisPlaceholderService::getAvailableTokens(),
            'images'     => ZeugnisImage::findByTemplate((int) $id),
            'formAction' => '/zeugnis/templates/' . $id,
        ]);
    }

    /**
     * Update an existing template.
     */
    public function update(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        $data = $this->extractTemplateData();
        if ($data === null) return;

        try {
            ZeugnisTemplate::update((int) $id, array_merge($data, ['updated_by' => $_SESSION['user_id']]));
            Logger::audit('update_zeugnis_template', $_SESSION['user_id'] ?? null, 'zeugnis_template', (int) $id);
            App::setFlash('success', 'Vorlage aktualisiert.');
            App::redirect('/zeugnis/templates/' . $id . '/edit');
        } catch (\PDOException $e) {
            Logger::error('ZeugnisTemplate update failed: ' . $e->getMessage());
            App::setFlash('error', 'Fehler beim Aktualisieren.');
            App::redirect('/zeugnis/templates/' . $id . '/edit');
        }
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        try {
            $newId = ZeugnisTemplate::duplicate((int) $id, $_SESSION['user_id']);
            Logger::audit('duplicate_zeugnis_template', $_SESSION['user_id'] ?? null, 'zeugnis_template', $newId);
            App::setFlash('success', 'Vorlage dupliziert.');
            App::redirect('/zeugnis/templates/' . $newId . '/edit');
        } catch (\Exception $e) {
            App::setFlash('error', 'Fehler beim Duplizieren.');
            App::redirect('/zeugnis/templates');
        }
    }

    /**
     * Delete a template.
     */
    public function delete(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        // Delete associated images from filesystem
        $images = ZeugnisImage::findByTemplate((int) $id);
        foreach ($images as $image) {
            ZeugnisImage::delete($image['id']);
        }

        ZeugnisTemplate::delete((int) $id);
        Logger::audit('delete_zeugnis_template', $_SESSION['user_id'] ?? null, 'zeugnis_template', (int) $id);
        App::setFlash('success', 'Vorlage gelöscht.');
        App::redirect('/zeugnis/templates');
    }

    /**
     * Publish a template for teachers.
     */
    public function publish(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        ZeugnisTemplate::publish((int) $id, $_SESSION['user_id']);
        Logger::audit('publish_zeugnis_template', $_SESSION['user_id'] ?? null, 'zeugnis_template', (int) $id);
        App::setFlash('success', 'Vorlage veröffentlicht und steht Lehrkräften zur Verfügung.');
        App::redirect('/zeugnis/templates');
    }

    /**
     * Unpublish a template (back to draft).
     */
    public function unpublish(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        ZeugnisTemplate::unpublish((int) $id);
        Logger::audit('unpublish_zeugnis_template', $_SESSION['user_id'] ?? null, 'zeugnis_template', (int) $id);
        App::setFlash('success', 'Vorlage zurück in Entwurf gesetzt.');
        App::redirect('/zeugnis/templates');
    }

    /**
     * Render a read-only preview of the template.
     */
    public function preview(string $id): void
    {
        $role = App::currentUserRole();
        if (!in_array($role, ['admin', 'schulleitung', 'sekretariat', 'lehrer'], true)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return;
        }

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        // Teachers can only preview published templates
        if ($role === 'lehrer' && $template['status'] !== 'published') {
            App::setFlash('error', 'Diese Vorlage ist nicht veröffentlicht.');
            App::redirect('/zeugnis/browse');
            return;
        }

        View::render('zeugnis/templates/preview', [
            'title'    => 'Vorschau: ' . htmlspecialchars($template['name']),
            'template' => $template,
            'tokens'   => ZeugnisPlaceholderService::getAvailableTokens(),
        ]);
    }

    /**
     * Handle image upload for a template. Returns JSON.
     */
    public function uploadImage(string $id): void
    {
        if (!$this->requireAdminRole()) return;

        header('Content-Type: application/json');

        $template = ZeugnisTemplate::findById((int) $id);
        if (!$template) {
            echo json_encode(['error' => 'Vorlage nicht gefunden.']);
            return;
        }

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Kein gültiges Bild hochgeladen.']);
            return;
        }

        $file = $_FILES['image'];

        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            echo json_encode(['error' => 'Bild ist zu groß (max. 5 MB).']);
            return;
        }

        // Validate MIME type using finfo (not extension)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES, true)) {
            echo json_encode(['error' => 'Ungültiger Dateityp. Erlaubt: JPG, PNG, GIF, WebP.']);
            return;
        }

        $storagePath = dirname(__DIR__, 2) . '/storage/uploads/zeugnis/';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0750, true);
        }

        $ext = match ($mimeType) {
            'image/jpeg'   => 'jpg',
            'image/png'    => 'png',
            'image/gif'    => 'gif',
            'image/webp'   => 'webp',
            default        => 'bin',
        };

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $storagePath . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['error' => 'Fehler beim Speichern der Datei.']);
            return;
        }

        $imageId = ZeugnisImage::create([
            'template_id'   => (int) $id,
            'original_name' => basename($file['name']),
            'stored_name'   => $storedName,
            'mime_type'     => $mimeType,
            'file_size'     => $file['size'],
            'uploaded_by'   => $_SESSION['user_id'],
        ]);

        echo json_encode([
            'id'  => $imageId,
            'src' => 'zeugnis-img:' . $imageId,
            'url' => '/zeugnis/images/' . $imageId,
        ]);
    }

    /**
     * Serve an uploaded template image with access control.
     */
    public function serveImage(string $imageId): void
    {
        if (!App::isLoggedIn()) {
            http_response_code(403);
            exit;
        }

        $image = ZeugnisImage::findById((int) $imageId);
        if (!$image) {
            http_response_code(404);
            exit;
        }

        // Zugriffspruefung: Admins immer, andere nur bei veroeffentlichten Vorlagen
        $role = App::currentUserRole();
        if (!in_array($role, self::ADMIN_ROLES, true)) {
            $template = ZeugnisTemplate::findById($image['template_id']);
            if (!$template || $template['status'] !== 'published') {
                http_response_code(403);
                exit;
            }
        }

        $filePath = ZeugnisImage::storagePath($image['stored_name']);
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $image['mime_type']);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: default-src \'none\'');
        readfile($filePath);
        exit;
    }

    /**
     * Delete an uploaded image.
     */
    public function deleteImage(string $imageId): void
    {
        if (!$this->requireAdminRole()) return;

        $image = ZeugnisImage::findById((int) $imageId);
        if (!$image) {
            App::setFlash('error', 'Bild nicht gefunden.');
            App::redirect('/zeugnis/templates');
            return;
        }

        ZeugnisImage::delete((int) $imageId);
        App::setFlash('success', 'Bild gelöscht.');

        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $ownHost = $_SERVER['HTTP_HOST'] ?? '';
            if ($refererHost === $ownHost) {
                header('Location: ' . $referer);
                exit;
            }
        }
        App::redirect('/zeugnis/templates');
    }

    // -------------------------------------------------------------------------

    /**
     * Extract and validate template POST data.
     * Returns null and sets flash on validation error.
     */
    private function extractTemplateData(): ?array
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/zeugnis/templates';
        $parsed = parse_url($referer);
        if (!empty($parsed['host']) && $parsed['host'] !== ($_SERVER['HTTP_HOST'] ?? '')) {
            $referer = '/zeugnis/templates';
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            App::setFlash('error', 'Name der Vorlage ist erforderlich.');
            App::redirect($referer);
            return null;
        }

        $orientation = $_POST['page_orientation'] ?? 'P';
        if (!in_array($orientation, ['P', 'L'], true)) {
            $orientation = 'P';
        }

        $format = $_POST['page_format'] ?? 'A4';
        if (!in_array($format, ['A4', 'A3'], true)) {
            $format = 'A4';
        }

        $canvasJson = $_POST['template_canvas'] ?? '';
        if ($canvasJson === '') {
            Logger::warning('Zeugnis template canvas POST field was empty, using default empty canvas');
            $canvasJson = json_encode(['pages' => [['id' => 'page-1', 'elements' => []]]]);
        } else {
            // Validate JSON
            $decoded = json_decode($canvasJson, true);
            if ($decoded === null) {
                App::setFlash('error', 'Ungültige Canvas-Daten.');
                App::redirect($referer);
                return null;
            }

            // Ensure valid pages structure
            if (!isset($decoded['pages']) || !is_array($decoded['pages'])) {
                Logger::warning('Zeugnis template canvas missing pages structure', [
                    'canvas_length' => strlen($canvasJson),
                ]);
                $decoded = ['pages' => [['id' => 'page-1', 'elements' => []]]];
            }

            $canvasJson = json_encode($decoded, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($canvasJson === false) {
                Logger::error('json_encode failed for zeugnis canvas: ' . json_last_error_msg());
                App::setFlash('error', 'Fehler beim Verarbeiten der Canvas-Daten.');
                App::redirect($referer);
                return null;
            }
        }

        return [
            'name'             => mb_substr($name, 0, 255),
            'description'      => trim($_POST['description'] ?? '') ?: null,
            'school_year'      => trim($_POST['school_year'] ?? '') ?: null,
            'grade_levels'     => trim($_POST['grade_levels'] ?? '') ?: null,
            'page_orientation' => $orientation,
            'page_format'      => $format,
            'template_canvas'  => $canvasJson,
            'status'           => 'draft',
        ];
    }
}
