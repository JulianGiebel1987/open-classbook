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
        // Auskunft und Audit-Eintrag erzeugen, BEVOR Download-Header gesendet
        // werden. Auf dem dokumentierten Entwicklungsserver (php -S) ist
        // display_errors aktiv, und der zentrale Error-Handler gibt Meldungen
        // an PHP weiter. Eine versehentliche Notice/Warning aus dieser Phase
        // (z.B. nicht beschreibbares Log-Verzeichnis) wuerde sonst in den
        // Download-Stream geschrieben, "headers already sent" ausloesen und den
        // Content-Disposition-Header verschlucken - der Download "funktioniert
        // nicht". Wir puffern die Phase daher und verwerfen jegliche Streu-
        // Ausgabe, bevor die eigentlichen Header gesetzt werden.
        ob_start();
        $data = DataExportService::exportUser($userId);
        if ($data !== null) {
            Logger::audit('data_export', $userId, 'user', $userId, 'DSGVO-Datenauskunft erstellt');
        }
        ob_end_clean();

        if ($data === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $json = self::encode($data);

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $username);
        $filename = 'datenauskunft_' . $safeName . '_' . date('Y-m-d') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        echo $json;
        exit;
    }

    /**
     * Auskunft nach JSON kodieren.
     *
     * JSON_INVALID_UTF8_SUBSTITUTE und JSON_PARTIAL_OUTPUT_ON_ERROR stellen
     * sicher, dass auch bei ungueltigen UTF-8-Bytes (etwa aus Importdaten) ein
     * gueltiges, nicht-leeres Dokument entsteht - statt eines leeren Downloads,
     * wenn json_encode() sonst false zurueckgeben wuerde.
     */
    public static function encode(array $data): string
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        return $json !== false
            ? $json
            : '{"fehler":"Die Datenauskunft konnte nicht vollstaendig erzeugt werden."}';
    }
}
