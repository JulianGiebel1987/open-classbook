<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\ImportService;

class ImportController
{
    public function index(): void
    {
        CsrfMiddleware::generateToken();
        View::render('import/index', ['title' => 'Daten importieren']);
    }

    public function uploadTeachers(): void
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            App::setFlash('error', 'Bitte waehlen Sie eine Excel-Datei aus.');
            App::redirect('/import');
            return;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            App::setFlash('error', 'Nur .xlsx-Dateien werden unterstuetzt.');
            App::redirect('/import');
            return;
        }

        $tmpPath = $file['tmp_name'];

        if (isset($_POST['confirm']) && $_POST['confirm'] === '1') {
            // Tatsaechlicher Import
            $result = ImportService::importTeachers($tmpPath);
            App::setFlash('success', "{$result['imported']} Lehrkraft/Lehrkraefte importiert, {$result['skipped']} uebersprungen.");
            App::redirect('/users');
            return;
        }

        // Vorschau anzeigen
        $preview = ImportService::previewTeachers($tmpPath);

        // Datei temporaer speichern fuer den tatsaechlichen Import
        $storedPath = __DIR__ . '/../../storage/uploads/' . bin2hex(random_bytes(16)) . '.xlsx';
        move_uploaded_file($tmpPath, $storedPath);

        CsrfMiddleware::generateToken();
        View::render('import/preview-teachers', [
            'title' => 'Import-Vorschau: Lehrkraefte',
            'preview' => $preview,
            'storedFile' => basename($storedPath),
        ]);
    }

    public function confirmTeachers(): void
    {
        $storedFile = $_POST['stored_file'] ?? '';

        // Nur hex-generierte Dateinamen (32 Hex-Zeichen + .xlsx) akzeptieren
        if (!preg_match('/^[0-9a-f]{32}\.xlsx$/', $storedFile)) {
            App::setFlash('error', 'Ungueltige Import-Datei. Bitte erneut hochladen.');
            App::redirect('/import');
            return;
        }

        $storedPath = __DIR__ . '/../../storage/uploads/' . $storedFile;

        if (!file_exists($storedPath)) {
            App::setFlash('error', 'Import-Datei nicht gefunden. Bitte erneut hochladen.');
            App::redirect('/import');
            return;
        }

        $result = ImportService::importTeachers($storedPath);
        unlink($storedPath); // Temporaere Datei loeschen

        $msg = "{$result['imported']} Lehrkraft/Lehrkraefte erfolgreich importiert.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} Zeile(n) uebersprungen.";
        }

        App::setFlash('success', $msg);
        App::redirect('/users');
    }

    public function uploadStudents(): void
    {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            App::setFlash('error', 'Bitte waehlen Sie eine Excel-Datei aus.');
            App::redirect('/import');
            return;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            App::setFlash('error', 'Nur .xlsx-Dateien werden unterstuetzt.');
            App::redirect('/import');
            return;
        }

        $schoolYear = trim($_POST['school_year'] ?? '');
        if (empty($schoolYear)) {
            App::setFlash('error', 'Bitte Schuljahr angeben.');
            App::redirect('/import');
            return;
        }

        $tmpPath = $file['tmp_name'];
        $preview = ImportService::previewStudents($tmpPath, $schoolYear);

        $storedPath = __DIR__ . '/../../storage/uploads/' . bin2hex(random_bytes(16)) . '.xlsx';
        move_uploaded_file($tmpPath, $storedPath);

        CsrfMiddleware::generateToken();
        View::render('import/preview-students', [
            'title' => 'Import-Vorschau: Schueler',
            'preview' => $preview,
            'storedFile' => basename($storedPath),
            'schoolYear' => $schoolYear,
        ]);
    }

    public function confirmStudents(): void
    {
        $storedFile = $_POST['stored_file'] ?? '';
        $schoolYear = $_POST['school_year'] ?? '';

        // Nur hex-generierte Dateinamen (32 Hex-Zeichen + .xlsx) akzeptieren
        if (!preg_match('/^[0-9a-f]{32}\.xlsx$/', $storedFile)) {
            App::setFlash('error', 'Ungueltige Import-Datei. Bitte erneut hochladen.');
            App::redirect('/import');
            return;
        }

        $storedPath = __DIR__ . '/../../storage/uploads/' . $storedFile;

        if (!file_exists($storedPath)) {
            App::setFlash('error', 'Import-Datei nicht gefunden. Bitte erneut hochladen.');
            App::redirect('/import');
            return;
        }

        $result = ImportService::importStudents($storedPath, $schoolYear);
        unlink($storedPath);

        $msg = "{$result['imported']} Schueler/in(nen) erfolgreich importiert.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} Zeile(n) uebersprungen.";
        }

        // Zugangsdaten in Session speichern fuer Anzeige
        if (!empty($result['credentials'])) {
            $_SESSION['import_credentials'] = $result['credentials'];
            $msg .= ' Zugangsdaten werden unten angezeigt - bitte notieren!';
        }

        App::setFlash('success', $msg);
        App::redirect('/import/students/credentials');
    }

    public function studentCredentials(): void
    {
        $credentials = $_SESSION['import_credentials'] ?? [];
        unset($_SESSION['import_credentials']);

        if (empty($credentials)) {
            App::redirect('/users');
            return;
        }

        // Passwort-Seite nie cachen (Browser-Verlauf / Proxy-Schutz)
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');

        View::render('import/student-credentials', [
            'title' => 'Schueler-Zugangsdaten',
            'credentials' => $credentials,
        ]);
    }

    public function downloadTemplate(string $type): void
    {
        $templates = [
            'lehrer' => 'Lehrer-Import.xlsx',
            'schueler' => 'Schueler-Import.xlsx',
        ];

        if (!isset($templates[$type])) {
            App::setFlash('error', 'Unbekannte Vorlage.');
            App::redirect('/import');
            return;
        }

        $path = __DIR__ . '/../../templates/' . $templates[$type];
        if (!file_exists($path)) {
            App::setFlash('error', 'Vorlage nicht gefunden.');
            App::redirect('/import');
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $templates[$type] . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
