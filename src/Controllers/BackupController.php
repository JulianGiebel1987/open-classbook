<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\AuthService;
use OpenClassbook\Services\BackupService;
use OpenClassbook\Services\Logger;

/**
 * Vollständige Datensicherung einer Instanz (Export/Import).
 *
 * Nur für Administratoren. Der Export liefert eine ZIP-Datei mit allen
 * Datenbankinhalten und hochgeladenen Dateien. Der Import ersetzt die
 * vorhandenen Daten vollständig durch die Sicherung.
 */
class BackupController
{
    /**
     * Defense-in-depth: zusätzlich zur AdminMiddleware auf Route-Ebene.
     */
    private function requireAdmin(): bool
    {
        if (App::currentUserRole() !== 'admin') {
            http_response_code(403);
            View::render('errors/403');
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        CsrfMiddleware::generateToken();

        $uploadsWritable = is_writable(dirname(__DIR__, 2) . '/storage/uploads');

        View::render('backup/index', [
            'title'           => 'Datensicherung',
            'uploadsWritable' => $uploadsWritable,
            'maxUploadSize'   => $this->formatBytes($this->maxUploadBytes()),
            'breadcrumbs'     => View::breadcrumbs([
                ['label' => 'Einstellungen', 'url' => '/settings'],
                ['label' => 'Datensicherung'],
            ]),
        ]);
    }

    /**
     * Erzeugt die Sicherung und liefert sie als Download aus.
     */
    public function export(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'ocb_backup_');
        if ($tmpPath === false) {
            App::setFlash('error', 'Sicherung konnte nicht erstellt werden (temporäre Datei).');
            App::redirect('/backup');
            return;
        }

        try {
            $user = App::currentUser();
            $createdBy = $user
                ? ($user['username'] ?? 'unbekannt') . ' (ID ' . ($user['id'] ?? '?') . ')'
                : null;

            $service = new BackupService();
            $service->createArchive($tmpPath, $createdBy);

            Logger::audit('backup_export', isset($user['id']) ? (int) $user['id'] : null);
        } catch (\Throwable $e) {
            @unlink($tmpPath);
            Logger::error('Datensicherung fehlgeschlagen', ['error' => $e->getMessage()]);
            App::setFlash('error', 'Die Sicherung konnte nicht erstellt werden: ' . $e->getMessage());
            App::redirect('/backup');
            return;
        }

        $filename = 'open-classbook-backup_' . date('Y-m-d_His') . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmpPath));
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        readfile($tmpPath);
        @unlink($tmpPath);
        exit;
    }

    /**
     * Nimmt eine hochgeladene Sicherungsdatei entgegen, validiert sie und
     * zeigt eine Vorschau mit Bestätigungsschritt an.
     */
    public function uploadImport(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            App::setFlash('error', $this->uploadErrorMessage($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE));
            App::redirect('/backup');
            return;
        }

        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            App::setFlash('error', 'Bitte eine Sicherungsdatei im ZIP-Format hochladen.');
            App::redirect('/backup');
            return;
        }

        $uploadsDir = dirname(__DIR__, 2) . '/storage/uploads';
        $storedName = bin2hex(random_bytes(16)) . '.zip';
        $storedPath = $uploadsDir . '/' . $storedName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $storedPath)) {
            App::setFlash('error', 'Die Datei konnte nicht gespeichert werden. Bitte Schreibrechte für storage/uploads/ prüfen.');
            App::redirect('/backup');
            return;
        }

        try {
            $service = new BackupService();
            $manifest = $service->readManifest($storedPath);
        } catch (\Throwable $e) {
            @unlink($storedPath);
            App::setFlash('error', $e->getMessage());
            App::redirect('/backup');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('backup/import-preview', [
            'title'       => 'Sicherung einspielen – Vorschau',
            'manifest'    => $manifest,
            'storedFile'  => $storedName,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Einstellungen', 'url' => '/settings'],
                ['label' => 'Datensicherung', 'url' => '/backup'],
                ['label' => 'Einspielen'],
            ]),
        ]);
    }

    /**
     * Spielt die zuvor hochgeladene und bestätigte Sicherung ein.
     * Meldet den Administrator anschließend ab, da sich sein Konto durch die
     * Wiederherstellung geändert haben kann.
     */
    public function confirmImport(): void
    {
        if (!$this->requireAdmin()) {
            return;
        }

        if (($_POST['confirm'] ?? '') !== '1') {
            App::setFlash('error', 'Bitte bestätigen Sie das Einspielen der Sicherung.');
            App::redirect('/backup');
            return;
        }

        $storedFile = $_POST['stored_file'] ?? '';
        if (!preg_match('/^[0-9a-f]{32}\.zip$/', $storedFile)) {
            App::setFlash('error', 'Ungültige Sicherungsdatei. Bitte erneut hochladen.');
            App::redirect('/backup');
            return;
        }

        $storedPath = dirname(__DIR__, 2) . '/storage/uploads/' . $storedFile;
        if (!file_exists($storedPath)) {
            App::setFlash('error', 'Sicherungsdatei nicht gefunden. Bitte erneut hochladen.');
            App::redirect('/backup');
            return;
        }

        $user = App::currentUser();

        try {
            $service = new BackupService();
            $service->restoreArchive($storedPath);
            Logger::audit('backup_import', isset($user['id']) ? (int) $user['id'] : null);
        } catch (\Throwable $e) {
            @unlink($storedPath);
            Logger::error('Wiederherstellung fehlgeschlagen', ['error' => $e->getMessage()]);
            App::setFlash('error', 'Die Wiederherstellung ist fehlgeschlagen. Der bisherige Datenbestand wurde nicht verändert. Details: ' . $e->getMessage());
            App::redirect('/backup');
            return;
        }

        @unlink($storedPath);

        // Sicherheitshalber abmelden: Das eigene Konto (inkl. Passwort) kann
        // durch die Wiederherstellung ersetzt worden sein.
        AuthService::logout();
        session_start();
        App::setFlash('success', 'Die Sicherung wurde erfolgreich eingespielt. Bitte melden Sie sich erneut an.');
        App::redirect('/login');
    }

    // ------------------------------------------------------------------

    private function maxUploadBytes(): int
    {
        $toBytes = static function (string $val): int {
            $val = trim($val);
            if ($val === '') {
                return 0;
            }
            $unit = strtolower($val[strlen($val) - 1]);
            $num = (int) $val;
            return match ($unit) {
                'g' => $num * 1024 * 1024 * 1024,
                'm' => $num * 1024 * 1024,
                'k' => $num * 1024,
                default => (int) $val,
            };
        };

        $post = $toBytes((string) ini_get('post_max_size'));
        $upload = $toBytes((string) ini_get('upload_max_filesize'));
        $candidates = array_filter([$post, $upload], static fn ($v) => $v > 0);
        return $candidates === [] ? 0 : (int) min($candidates);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return 'unbekannt';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));
        return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß für den Upload. Bitte serverseitige Limits (upload_max_filesize / post_max_size) prüfen.',
            UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur unvollständig hochgeladen. Bitte erneut versuchen.',
            UPLOAD_ERR_NO_FILE => 'Bitte wählen Sie eine Sicherungsdatei aus.',
            default => 'Der Upload ist fehlgeschlagen. Bitte erneut versuchen.',
        };
    }
}
