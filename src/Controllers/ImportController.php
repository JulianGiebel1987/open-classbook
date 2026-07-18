<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Middleware\CsrfMiddleware;
use OpenClassbook\Services\ImportService;
use OpenClassbook\Services\ModuleSettings;
use OpenClassbook\Services\AuthService;
use OpenClassbook\Services\NotificationService;

class ImportController
{
    private const STAFF_ROLES = ['admin', 'schulleitung', 'sekretariat'];

    /**
     * Fuer eine Liste importierter E-Mail-Login-Konten Einladungen versenden bzw.
     * (bei deaktiviertem/fehlgeschlagenem Mailversand) die Einladungslinks zum
     * einmaligen Anzeigen sammeln.
     *
     * @param array<int,array{user_id:int,email:string,name:string}> $invitations
     * @return array{sent:int, links:array<int,array{name:string,email:string,link:string}>}
     */
    private function processInvitations(array $invitations): array
    {
        $sent = 0;
        $links = [];

        foreach ($invitations as $inv) {
            $link = AuthService::createOnboardingLink((int) $inv['user_id']);
            $email = (string) ($inv['email'] ?? '');
            $name = (string) ($inv['name'] ?? '');

            if (App::config('mail.enabled') && $email !== ''
                && NotificationService::sendInvitationMail($email, $name, $link)) {
                $sent++;
            } else {
                $links[] = [
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                    'link' => $link,
                ];
            }
        }

        return ['sent' => $sent, 'links' => $links];
    }

    /**
     * Defense-in-depth: sicherstellen, dass der aktuelle Nutzer berechtigt ist.
     * Zusätzlich zur StaffMiddleware auf Route-Ebene.
     */
    private function requireStaff(): bool
    {
        if (!in_array(App::currentUserRole(), self::STAFF_ROLES, true)) {
            App::setFlash('error', 'Zugriff verweigert. Nur Administratoren, Schulleitung und Sekretariat dürfen Daten importieren.');
            App::redirect('/dashboard');
            return false;
        }
        return true;
    }

    /**
     * Sicherstellen, dass das Schulbegleiter:innen-Modul aktiviert ist,
     * bevor Import-Funktionen für Schulbegleiter:innen genutzt werden.
     */
    private function requireSchoolAidesModule(): bool
    {
        if (!ModuleSettings::canAccess('school_aides', App::currentUserRole())) {
            App::setFlash('error', 'Das Modul Schulbegleiter:innen ist deaktiviert.');
            App::redirect('/import');
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireStaff()) return;

        CsrfMiddleware::generateToken();
        $uploadsDir = __DIR__ . '/../../storage/uploads';
        if (!is_writable($uploadsDir)) {
            App::setFlash('error', 'Import nicht möglich: Das Verzeichnis storage/uploads/ ist nicht beschreibbar. Bitte Berechtigungen prüfen (z.B.: chmod 775 storage/uploads/).');
        }
        View::render('import/index', [
            'title' => 'Daten importieren',
            'schoolAidesEnabled' => ModuleSettings::canAccess('school_aides', App::currentUserRole()),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Daten importieren'],
            ]),
        ]);
    }

    public function uploadTeachers(): void
    {
        if (!$this->requireStaff()) return;

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            App::setFlash('error', 'Bitte wählen Sie eine Datei aus.');
            App::redirect('/import');
            return;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            App::setFlash('error', 'Nur .xlsx- und .csv-Dateien werden unterstützt.');
            App::redirect('/import');
            return;
        }

        $tmpPath = $file['tmp_name'];

        // Vorschau anzeigen
        $preview = ImportService::previewTeachers($tmpPath, $ext);

        // Datei temporär speichern für den tatsächlichen Import
        $storedPath = __DIR__ . '/../../storage/uploads/' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmpPath, $storedPath)) {
            App::setFlash('error', 'Datei konnte nicht gespeichert werden. Bitte Schreibrechte für storage/uploads/ prüfen.');
            App::redirect('/import');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('import/preview-teachers', [
            'title' => 'Import-Vorschau: Lehrkräfte',
            'preview' => $preview,
            'storedFile' => basename($storedPath),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Daten importieren', 'url' => '/import'],
                ['label' => 'Vorschau: Lehrkräfte'],
            ]),
        ]);
    }

    public function confirmTeachers(): void
    {
        if (!$this->requireStaff()) return;

        $storedFile = $_POST['stored_file'] ?? '';

        // Nur hex-generierte Dateinamen (32 Hex-Zeichen + .xlsx/.csv) akzeptieren
        if (!preg_match('/^[0-9a-f]{32}\.(xlsx|csv)$/', $storedFile)) {
            App::setFlash('error', 'Ungültige Import-Datei. Bitte erneut hochladen.');
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
        unlink($storedPath); // Temporaere Datei löschen

        $msg = "{$result['imported']} Lehrkraft/Lehrkräfte erfolgreich importiert.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} Zeile(n) übersprungen.";
        }

        $this->finishWithInvitations($result['invitations'] ?? [], $msg);
    }

    /**
     * Import mit anschliessendem Einladungsversand abschliessen: Meldung setzen
     * und je nach Ergebnis zur Benutzerliste oder zur Einladungslink-Anzeige
     * weiterleiten.
     *
     * @param array<int,array{user_id:int,email:string,name:string}> $invitations
     */
    private function finishWithInvitations(array $invitations, string $msg): void
    {
        $result = $this->processInvitations($invitations);

        if ($result['sent'] > 0) {
            $msg .= " {$result['sent']} Einladung(en) per E-Mail versendet.";
        }

        if (!empty($result['links'])) {
            $_SESSION['invite_links'] = $result['links'];
            $_SESSION['invite_back_url'] = '/users';
            App::setFlash('success', $msg . ' Einladungslinks werden einmalig angezeigt.');
            App::redirect('/users/invite-info');
            return;
        }

        App::setFlash('success', $msg);
        App::redirect('/users');
    }

    public function uploadStudents(): void
    {
        if (!$this->requireStaff()) return;

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            App::setFlash('error', 'Bitte wählen Sie eine Datei aus.');
            App::redirect('/import');
            return;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            App::setFlash('error', 'Nur .xlsx- und .csv-Dateien werden unterstützt.');
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
        $preview = ImportService::previewStudents($tmpPath, $schoolYear, $ext);

        $storedPath = __DIR__ . '/../../storage/uploads/' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmpPath, $storedPath)) {
            App::setFlash('error', 'Datei konnte nicht gespeichert werden. Bitte Schreibrechte für storage/uploads/ prüfen.');
            App::redirect('/import');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('import/preview-students', [
            'title' => 'Import-Vorschau: Schüler',
            'preview' => $preview,
            'storedFile' => basename($storedPath),
            'schoolYear' => $schoolYear,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Daten importieren', 'url' => '/import'],
                ['label' => 'Vorschau: Schüler'],
            ]),
        ]);
    }

    public function confirmStudents(): void
    {
        if (!$this->requireStaff()) return;

        $storedFile = $_POST['stored_file'] ?? '';
        $schoolYear = $_POST['school_year'] ?? '';

        // Nur hex-generierte Dateinamen (32 Hex-Zeichen + .xlsx/.csv) akzeptieren
        if (!preg_match('/^[0-9a-f]{32}\.(xlsx|csv)$/', $storedFile)) {
            App::setFlash('error', 'Ungültige Import-Datei. Bitte erneut hochladen.');
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

        $msg = "{$result['imported']} Schüler:innen erfolgreich importiert.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} Zeile(n) übersprungen.";
        }

        // Zugangsdaten in Session speichern für Anzeige
        if (!empty($result['credentials'])) {
            $_SESSION['import_credentials'] = $result['credentials'];
            $msg .= ' Zugangsdaten werden unten angezeigt - bitte notieren!';
        }

        App::setFlash('success', $msg);
        App::redirect('/import/students/credentials');
    }

    public function uploadSchoolAides(): void
    {
        if (!$this->requireStaff()) return;
        if (!$this->requireSchoolAidesModule()) return;

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            App::setFlash('error', 'Bitte wählen Sie eine Datei aus.');
            App::redirect('/import');
            return;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            App::setFlash('error', 'Nur .xlsx- und .csv-Dateien werden unterstützt.');
            App::redirect('/import');
            return;
        }

        $tmpPath = $file['tmp_name'];
        $preview = ImportService::previewSchoolAides($tmpPath, $ext);

        $storedPath = __DIR__ . '/../../storage/uploads/' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmpPath, $storedPath)) {
            App::setFlash('error', 'Datei konnte nicht gespeichert werden. Bitte Schreibrechte für storage/uploads/ prüfen.');
            App::redirect('/import');
            return;
        }

        CsrfMiddleware::generateToken();
        View::render('import/preview-aides', [
            'title' => 'Import-Vorschau: Schulbegleiter:innen',
            'preview' => $preview,
            'storedFile' => basename($storedPath),
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Daten importieren', 'url' => '/import'],
                ['label' => 'Vorschau: Schulbegleiter:innen'],
            ]),
        ]);
    }

    public function confirmSchoolAides(): void
    {
        if (!$this->requireStaff()) return;
        if (!$this->requireSchoolAidesModule()) return;

        $storedFile = $_POST['stored_file'] ?? '';

        if (!preg_match('/^[0-9a-f]{32}\.(xlsx|csv)$/', $storedFile)) {
            App::setFlash('error', 'Ungültige Import-Datei. Bitte erneut hochladen.');
            App::redirect('/import');
            return;
        }

        $storedPath = __DIR__ . '/../../storage/uploads/' . $storedFile;

        if (!file_exists($storedPath)) {
            App::setFlash('error', 'Import-Datei nicht gefunden. Bitte erneut hochladen.');
            App::redirect('/import');
            return;
        }

        $result = ImportService::importSchoolAides($storedPath);
        unlink($storedPath);

        $msg = "{$result['imported']} Schulbegleiter:in(nen) erfolgreich importiert.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} Zeile(n) übersprungen.";
        }

        $this->finishWithInvitations($result['invitations'] ?? [], $msg);
    }

    public function studentCredentials(): void
    {
        if (!$this->requireStaff()) return;

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
            'title' => 'Schüler-Zugangsdaten',
            'credentials' => $credentials,
            'breadcrumbs' => View::breadcrumbs([
                ['label' => 'Benutzer', 'url' => '/users'],
                ['label' => 'Daten importieren', 'url' => '/import'],
                ['label' => 'Zugangsdaten'],
            ]),
        ]);
    }

    public function downloadTemplate(string $type): void
    {
        if (!$this->requireStaff()) return;

        // CSV-Vorlagen dynamisch generieren
        if ($type === 'lehrer-csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Lehrer-Import.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM für Excel-Kompatibilität
            echo "Vorname;Nachname;Kürzel;E-Mail;Fächer;Klassen\n";
            echo "Max;Mustermann;MUS;m.mustermann@schule.de;Mathematik,Physik;5a,6b\n";
            exit;
        }

        if ($type === 'schueler-csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Schüler-Import.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM für Excel-Kompatibilität
            echo "Vorname;Nachname;Klasse;Geburtsdatum;Erziehungsberechtigten-Email\n";
            echo "Anna;Musterfrau;5a;15.03.2013;musterfrau@example.de\n";
            exit;
        }

        if (($type === 'schulbegleiter' || $type === 'schulbegleiter-csv')
            && !$this->requireSchoolAidesModule()) {
            return;
        }

        if ($type === 'schulbegleiter-csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="Schulbegleiter-Import.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM für Excel-Kompatibilität
            echo "Vorname;Nachname;E-Mail;Kommentar\n";
            echo "Erika;Beispiel;e.beispiel@schule.de;Begleitet vormittags\n";
            exit;
        }

        $templates = [
            'lehrer' => 'Lehrer-Import.xlsx',
            'schueler' => 'Schüler-Import.xlsx',
            'schulbegleiter' => 'Schulbegleiter-Import.xlsx',
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
