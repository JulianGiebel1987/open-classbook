<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Models\Folder;
use OpenClassbook\Models\FileEntry;
use OpenClassbook\Services\ModuleSettings;

class FileController
{
    private const ALLOWED_ROLES = ['admin', 'schulleitung', 'sekretariat', 'lehrer'];
    private const MAX_FILE_SIZE = 15 * 1024 * 1024; // 15 MB
    private const MAX_USER_STORAGE = 100 * 1024 * 1024; // 100 MB

    private const ALLOWED_MIME_TYPES = [
        // Bilder
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        // Dokumente
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Text
        'text/plain', 'text/csv',
        // Archiv
        'application/zip',
    ];

    private function checkRole(): bool
    {
        if (!in_array(App::currentUserRole(), self::ALLOWED_ROLES)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/dashboard');
            return false;
        }
        if (!ModuleSettings::canAccess('files', App::currentUserRole())) {
            App::setFlash('error', 'Das Modul Dateien ist derzeit deaktiviert.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * Übersicht: Meine Dateien + Gemeinschaftliche Dateien
     */
    public function index(): void
    {
        if (!$this->checkRole()) return;

        $userId = $_SESSION['user_id'];
        $usedStorage = FileEntry::getTotalSizeByUser($userId);

        View::render('files/index', [
            'title' => 'Dateien',
            'usedStorage' => $usedStorage,
            'maxStorage' => self::MAX_USER_STORAGE,
            'usedFormatted' => FileEntry::formatSize($usedStorage),
            'maxFormatted' => FileEntry::formatSize(self::MAX_USER_STORAGE),
        ]);
    }

    /**
     * Privater Root-Ordner
     */
    public function privateBrowse(): void
    {
        if (!$this->checkRole()) return;
        $this->renderBrowse(null, false);
    }

    /**
     * Gemeinschaftlicher Root-Ordner
     */
    public function sharedBrowse(): void
    {
        if (!$this->checkRole()) return;
        $this->renderBrowse(null, true);
    }

    /**
     * Unterordner anzeigen
     */
    public function browse(string $folderId): void
    {
        if (!$this->checkRole()) return;

        $folder = Folder::findById((int) $folderId);
        if (!$folder) {
            App::setFlash('error', 'Ordner nicht gefunden.');
            App::redirect('/files');
            return;
        }

        $userId = $_SESSION['user_id'];
        if (!Folder::hasAccess((int) $folderId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/files');
            return;
        }

        $this->renderBrowse((int) $folderId, (bool) $folder['is_shared']);
    }

    /**
     * Datei hochladen
     */
    public function upload(): void
    {
        if (!$this->checkRole()) return;

        $userId = $_SESSION['user_id'];
        $folderId = !empty($_POST['folder_id']) ? (int) $_POST['folder_id'] : null;
        $isShared = (bool) ($_POST['is_shared'] ?? 0);

        // Zugriffspruefung auf Zielordner
        if ($folderId && !Folder::hasAccess($folderId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/files');
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->flashAndRedirect('error', 'Fehler beim Hochladen der Datei.', $folderId, $isShared);
            return;
        }

        $file = $_FILES['file'];

        // Dateigroesse prüfen
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->flashAndRedirect('error', 'Die Datei ist zu gross (max. 15 MB).', $folderId, $isShared);
            return;
        }

        // Quota prüfen
        $usedStorage = FileEntry::getTotalSizeByUser($userId);
        if ($usedStorage + $file['size'] > self::MAX_USER_STORAGE) {
            $remaining = FileEntry::formatSize(self::MAX_USER_STORAGE - $usedStorage);
            $this->flashAndRedirect('error', "Speicherlimit erreicht. Verbleibend: $remaining.", $folderId, $isShared);
            return;
        }

        // MIME-Typ prüfen (gegen Whitelist)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $this->flashAndRedirect('error', 'Dieser Dateityp ist nicht erlaubt.', $folderId, $isShared);
            return;
        }

        // Dateiname sanitisieren
        $originalName = basename($file['name']);
        $originalName = preg_replace('/[^\w\.\-\(\) ]/', '_', $originalName);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = bin2hex(random_bytes(16)) . ($extension ? '.' . strtolower($extension) : '');

        $storagePath = FileEntry::getStoragePath($storedName);
        if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
            $this->flashAndRedirect('error', 'Fehler beim Speichern der Datei.', $folderId, $isShared);
            return;
        }

        FileEntry::create([
            'folder_id' => $folderId,
            'owner_id' => $userId,
            'is_shared' => $isShared,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => $file['size'],
        ]);

        $this->flashAndRedirect('success', 'Datei erfolgreich hochgeladen.', $folderId, $isShared);
    }

    /**
     * Datei herunterladen
     */
    public function download(string $id): void
    {
        if (!$this->checkRole()) return;

        $file = FileEntry::findById((int) $id);
        if (!$file) {
            App::setFlash('error', 'Datei nicht gefunden.');
            App::redirect('/files');
            return;
        }

        if (!FileEntry::hasAccess((int) $id, $_SESSION['user_id'])) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/files');
            return;
        }

        $path = FileEntry::getStoragePath($file['stored_name']);
        if (!file_exists($path)) {
            App::setFlash('error', 'Datei nicht auf dem Server gefunden.');
            App::redirect('/files');
            return;
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . addcslashes($file['original_name'], '"') . '"');
        header('Content-Length: ' . $file['file_size']);
        header('Cache-Control: no-cache');
        readfile($path);
        exit;
    }

    /**
     * Neuen Unterordner erstellen
     */
    public function createFolder(): void
    {
        if (!$this->checkRole()) return;

        $userId = $_SESSION['user_id'];
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $isShared = (bool) ($_POST['is_shared'] ?? 0);
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $this->flashAndRedirect('error', 'Ordnername darf nicht leer sein.', $parentId, $isShared);
            return;
        }

        // Ordnername sanitisieren
        $name = preg_replace('/[^\w\.\-\(\) äöüÄÖÜß]/', '_', $name);
        $name = mb_substr($name, 0, 100);

        // Zugriffspruefung auf Elternordner
        if ($parentId && !Folder::hasAccess($parentId, $userId)) {
            App::setFlash('error', 'Zugriff verweigert.');
            App::redirect('/files');
            return;
        }

        try {
            Folder::create([
                'name' => $name,
                'parent_id' => $parentId,
                'owner_id' => $isShared ? null : $userId,
                'is_shared' => $isShared,
                'created_by' => $userId,
            ]);
            $this->flashAndRedirect('success', 'Ordner erstellt.', $parentId, $isShared);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->flashAndRedirect('error', 'Ein Ordner mit diesem Namen existiert bereits.', $parentId, $isShared);
            } else {
                $this->flashAndRedirect('error', 'Fehler beim Erstellen des Ordners.', $parentId, $isShared);
            }
        }
    }

    /**
     * Datei löschen
     */
    public function deleteFile(string $id): void
    {
        if (!$this->checkRole()) return;

        $file = FileEntry::findById((int) $id);
        if (!$file) {
            App::setFlash('error', 'Datei nicht gefunden.');
            App::redirect('/files');
            return;
        }

        $userId = $_SESSION['user_id'];
        $role = App::currentUserRole();

        // Nur Besitzer oder Admin dürfen löschen
        if ((int) $file['owner_id'] !== $userId && $role !== 'admin') {
            App::setFlash('error', 'Nur der Besitzer oder ein Admin kann diese Datei löschen.');
            App::redirect('/files');
            return;
        }

        $folderId = $file['folder_id'];
        $isShared = (bool) $file['is_shared'];

        FileEntry::delete((int) $id);

        $this->flashAndRedirect('success', 'Datei gelöscht.', $folderId, $isShared);
    }

    /**
     * Ordner löschen (inkl. Inhalt)
     */
    public function deleteFolder(string $id): void
    {
        if (!$this->checkRole()) return;

        $folder = Folder::findById((int) $id);
        if (!$folder) {
            App::setFlash('error', 'Ordner nicht gefunden.');
            App::redirect('/files');
            return;
        }

        $userId = $_SESSION['user_id'];
        $role = App::currentUserRole();

        // Nur Ersteller oder Admin dürfen löschen
        if ((int) $folder['created_by'] !== $userId && $role !== 'admin') {
            App::setFlash('error', 'Nur der Ersteller oder ein Admin kann diesen Ordner löschen.');
            App::redirect('/files');
            return;
        }

        // Physische Dateien sammeln und löschen
        $storedNames = Folder::collectStoredNames((int) $id);
        foreach ($storedNames as $storedName) {
            $path = FileEntry::getStoragePath($storedName);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $parentId = $folder['parent_id'];
        $isShared = (bool) $folder['is_shared'];

        Folder::delete((int) $id);

        $this->flashAndRedirect('success', 'Ordner und Inhalt gelöscht.', $parentId, $isShared);
    }

    /**
     * Ordnerinhalt rendern (wiederverwendbar für privat/shared/subfolder)
     */
    private function renderBrowse(?int $folderId, bool $shared): void
    {
        $userId = $_SESSION['user_id'];

        $folders = Folder::findByParent($folderId, $userId, $shared);
        $files = FileEntry::findByFolder($folderId, $userId, $shared);

        $breadcrumbs = [];
        if ($folderId) {
            $breadcrumbs = Folder::getPath($folderId);
        }

        $usedStorage = FileEntry::getTotalSizeByUser($userId);

        CsrfMiddleware::generateToken();
        View::render('files/browse', [
            'title' => $shared ? 'Gemeinschaftliche Dateien' : 'Meine Dateien',
            'folders' => $folders,
            'files' => $files,
            'currentFolderId' => $folderId,
            'isShared' => $shared,
            'breadcrumbs' => $breadcrumbs,
            'usedStorage' => $usedStorage,
            'maxStorage' => self::MAX_USER_STORAGE,
            'usedFormatted' => FileEntry::formatSize($usedStorage),
            'maxFormatted' => FileEntry::formatSize(self::MAX_USER_STORAGE),
            'remainingFormatted' => FileEntry::formatSize(max(0, self::MAX_USER_STORAGE - $usedStorage)),
        ]);
    }

    /**
     * Flash-Nachricht setzen und zum richtigen Ordner zurückleiten.
     */
    private function flashAndRedirect(string $type, string $message, ?int $folderId, bool $isShared): void
    {
        App::setFlash($type, $message);
        if ($folderId) {
            App::redirect('/files/folder/' . $folderId);
        } else {
            App::redirect($isShared ? '/files/shared' : '/files/private');
        }
    }
}
