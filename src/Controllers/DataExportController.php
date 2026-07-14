<?php

namespace OpenClassbook\Controllers;

use OpenClassbook\App;
use OpenClassbook\View;
use OpenClassbook\Models\User;
use OpenClassbook\Services\DataExportService;
use OpenClassbook\Services\Logger;

/**
 * DSGVO-Datenauskunft / -export (Art. 15 / Art. 20).
 *
 * Liefert alle zu einem Konto gespeicherten personenbezogenen Daten als
 * JSON-Download.
 */
class DataExportController
{
    /**
     * Admin-Export für ein beliebiges Konto (Route ist bereits durch
     * AdminMiddleware geschützt).
     */
    public function exportUser(string $id): void
    {
        $userId = (int) $id;
        $user = User::findById($userId);
        if ($user === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $this->stream($userId, (string) $user['username']);
    }

    /**
     * Self-Service-Export für das eigene Konto (Art. 15).
     */
    public function myData(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            App::redirect('/login');
            return;
        }

        $username = $_SESSION['user']['username'] ?? 'konto';
        $this->stream($userId, (string) $username);
    }

    /**
     * JSON-Auskunft erzeugen und als Download ausliefern.
     */
    private function stream(int $userId, string $username): void
    {
        $data = DataExportService::exportUser($userId);
        if ($data === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        Logger::audit('data_export', $userId, 'user', $userId, 'DSGVO-Datenauskunft erstellt');

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $username);
        $filename = 'datenauskunft_' . $safeName . '_' . date('Y-m-d') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
