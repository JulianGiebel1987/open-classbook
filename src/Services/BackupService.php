<?php

namespace OpenClassbook\Services;

use OpenClassbook\Database;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Vollständiger Instanz-Export und -Import ("Datensicherung").
 *
 * Erzeugt eine einzelne ZIP-Datei, die sämtliche Nutzdaten einer Instanz
 * enthält:
 *   - database.json : Inhalt aller Datenbanktabellen (dynamisch ermittelt)
 *   - storage/...    : alle hochgeladenen Dateien (Dateiablage, Nachrichten-
 *                      Anhänge, Zeugnis-Bilder) sowie der 2FA-Schlüssel,
 *                      damit verschlüsselte 2FA-Geheimnisse nach dem
 *                      Wiederherstellen weiter entschlüsselt werden können
 *   - manifest.json  : Metadaten (Format-/App-Version, Zeitpunkt, Tabellen)
 *
 * Der Import ersetzt die vorhandenen Daten vollständig durch die Daten aus
 * der Sicherung. Er läuft in einer Transaktion; bei einem Fehler bleibt der
 * bisherige Datenbestand erhalten (Rollback).
 *
 * Bewusst ausgenommen sind ausschließlich technische/flüchtige Tabellen
 * (siehe SKIP_TABLES) – z. B. Schema-Migrationen sowie kurzlebige
 * Sicherheitszähler (Login-Versuche, Rate-Limits, 2FA-Einmalcodes). Diese
 * gehören nicht zu den fachlichen Instanzdaten und würden beim Zurückspielen
 * nur veralteten (potenziell sperrenden) Zustand wiederherstellen.
 */
class BackupService
{
    /** Version des Sicherungsformats. Bei inkompatiblen Änderungen erhöhen. */
    public const FORMAT_VERSION = 1;

    /** Kennung zur Erkennung gültiger Sicherungsdateien. */
    public const FORMAT_ID = 'open-classbook-backup';

    /**
     * Tabellen, die weder exportiert noch beim Import geleert/überschrieben
     * werden. Rein technischer/flüchtiger Zustand.
     */
    private const SKIP_TABLES = [
        'migrations',        // Schema-Versionsverwaltung (zielinstanz-eigen)
        'login_attempts',    // flüchtige Brute-Force-Zähler
        'rate_limits',       // flüchtige Rate-Limit-Zähler
        'two_factor_codes',  // kurzlebige 2FA-Einmalcodes
    ];

    /**
     * Verzeichnisse mit hochgeladenen Dateien (relativ zum Projektwurzel-
     * verzeichnis). Werden beim Export eingepackt und beim Import
     * wiederhergestellt.
     */
    private const CONTENT_DIRS = [
        'storage/files',
        'storage/message_attachments',
        'storage/uploads/zeugnis',
    ];

    /**
     * Zusätzlich gesicherte Dateien, die beim Import überschrieben, aber nie
     * gelöscht werden (fehlt die Datei in der Sicherung, bleibt die
     * vorhandene erhalten). Betrifft den 2FA-Schlüssel.
     */
    private const KEY_FILES = [
        'storage/keys/two_factor.key',
    ];

    /** Präfix aller Datei-Einträge innerhalb des ZIP-Archivs. */
    private const ZIP_FILE_PREFIX = 'storage/';

    /**
     * Erzeugt die Sicherungs-ZIP-Datei am angegebenen Zielpfad.
     *
     * @return array{tables: array<string,int>, file_count: int, file_bytes: int}
     */
    public function createArchive(string $destZipPath, ?string $createdBy = null): array
    {
        $database = $this->exportDatabase();

        $zip = new ZipArchive();
        if ($zip->open($destZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Sicherungsdatei konnte nicht angelegt werden.');
        }

        // --- Datenbankinhalt ---
        $tableCounts = [];
        foreach ($database as $table => $rows) {
            $tableCounts[$table] = count($rows);
        }
        $dbJson = json_encode($database, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($dbJson === false) {
            $zip->close();
            @unlink($destZipPath);
            throw new RuntimeException('Datenbankinhalt konnte nicht serialisiert werden: ' . json_last_error_msg());
        }
        $zip->addFromString('database.json', $dbJson);

        // --- Hochgeladene Dateien ---
        $fileCount = 0;
        $fileBytes = 0;
        $root = $this->projectRoot();

        foreach (self::CONTENT_DIRS as $relDir) {
            $absDir = $root . '/' . $relDir;
            if (!is_dir($absDir)) {
                continue;
            }
            foreach ($this->listFilesRecursive($absDir) as $absFile) {
                $relFile = ltrim(str_replace($root, '', $absFile), '/');
                $zip->addFile($absFile, $relFile);
                $fileCount++;
                $fileBytes += (int) @filesize($absFile);
            }
        }

        foreach (self::KEY_FILES as $relFile) {
            $absFile = $root . '/' . $relFile;
            if (is_file($absFile)) {
                $zip->addFile($absFile, $relFile);
                $fileCount++;
                $fileBytes += (int) @filesize($absFile);
            }
        }

        // --- Manifest ---
        $manifest = [
            'format'         => self::FORMAT_ID,
            'format_version' => self::FORMAT_VERSION,
            'app_version'    => $this->appVersion(),
            'created_at'     => date('c'),
            'created_by'     => $createdBy,
            'php_version'    => PHP_VERSION,
            'driver'         => $this->driverName(),
            'tables'         => $tableCounts,
            'files'          => ['count' => $fileCount, 'bytes' => $fileBytes],
        ];
        $zip->addFromString(
            'manifest.json',
            (string) json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        if ($zip->close() !== true) {
            @unlink($destZipPath);
            throw new RuntimeException('Sicherungsdatei konnte nicht geschrieben werden.');
        }

        return [
            'tables'     => $tableCounts,
            'file_count' => $fileCount,
            'file_bytes' => $fileBytes,
        ];
    }

    /**
     * Liest das Manifest einer Sicherungsdatei aus, ohne sie einzuspielen.
     * Dient der Validierung und der Vorschau vor dem Import.
     *
     * @return array<string,mixed>
     */
    public function readManifest(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Die Datei ist keine gültige ZIP-Sicherung.');
        }

        $manifestRaw = $zip->getFromName('manifest.json');
        $hasDatabase = $zip->locateName('database.json') !== false;
        $zip->close();

        if ($manifestRaw === false || !$hasDatabase) {
            throw new RuntimeException('Die Datei ist keine gültige Open-Classbook-Sicherung.');
        }

        $manifest = json_decode($manifestRaw, true);
        if (!is_array($manifest) || ($manifest['format'] ?? null) !== self::FORMAT_ID) {
            throw new RuntimeException('Die Datei ist keine gültige Open-Classbook-Sicherung.');
        }

        if ((int) ($manifest['format_version'] ?? 0) > self::FORMAT_VERSION) {
            throw new RuntimeException(
                'Die Sicherung wurde mit einer neueren Programmversion erstellt und kann nicht eingespielt werden.'
            );
        }

        return $manifest;
    }

    /**
     * Spielt eine Sicherungsdatei vollständig ein: ersetzt die
     * Datenbankinhalte und die hochgeladenen Dateien.
     *
     * @return array{tables: array<string,int>, file_count: int}
     */
    public function restoreArchive(string $zipPath): array
    {
        // Validiert Format/Version und wirft bei Problemen.
        $this->readManifest($zipPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Die Sicherungsdatei konnte nicht geöffnet werden.');
        }

        $dbJson = $zip->getFromName('database.json');
        if ($dbJson === false) {
            $zip->close();
            throw new RuntimeException('Die Sicherung enthält keine Datenbankdaten.');
        }

        $database = json_decode($dbJson, true);
        if (!is_array($database)) {
            $zip->close();
            throw new RuntimeException('Die Datenbankdaten in der Sicherung sind beschädigt.');
        }

        // 1) Datenbank wiederherstellen (transaktional).
        $counts = $this->importDatabase($database);

        // 2) Dateien wiederherstellen (nach erfolgreichem DB-Import).
        $fileCount = $this->restoreFiles($zip);

        $zip->close();

        return ['tables' => $counts, 'file_count' => $fileCount];
    }

    // ==================================================================
    // Datenbank
    // ==================================================================

    /**
     * Liest alle relevanten Tabellen aus und liefert sie als
     * [tabelle => [zeile, ...]] zurück.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function exportDatabase(): array
    {
        $pdo = Database::getConnection();
        $data = [];
        foreach ($this->listTables() as $table) {
            $stmt = $pdo->query('SELECT * FROM ' . $this->quoteIdent($table));
            $data[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    }

    /**
     * Ersetzt den Inhalt aller in $database enthaltenen Tabellen.
     * Läuft in einer Transaktion mit deaktivierten Fremdschlüsselprüfungen.
     *
     * @param array<string, array<int, array<string, mixed>>> $database
     * @return array<string, int> Anzahl eingespielter Zeilen je Tabelle
     */
    public function importDatabase(array $database): array
    {
        $pdo = Database::getConnection();
        $existing = $this->listTables();

        // Nur bekannte, nicht ausgenommene Tabellen berücksichtigen.
        $tables = [];
        foreach ($database as $table => $rows) {
            if (in_array($table, $existing, true) && is_array($rows)) {
                $tables[$table] = $rows;
            }
        }

        $counts = [];
        $this->setForeignKeyChecks($pdo, false);
        $pdo->beginTransaction();
        try {
            // Zuerst alle Zieltabellen leeren ...
            foreach (array_keys($tables) as $table) {
                $pdo->exec('DELETE FROM ' . $this->quoteIdent($table));
            }
            // ... dann neu befüllen.
            foreach ($tables as $table => $rows) {
                $counts[$table] = 0;
                foreach ($rows as $row) {
                    if (!is_array($row) || $row === []) {
                        continue;
                    }
                    $this->insertRow($pdo, $table, $row);
                    $counts[$table]++;
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->setForeignKeyChecks($pdo, true);
            throw new RuntimeException('Der Import ist fehlgeschlagen: ' . $e->getMessage(), 0, $e);
        }
        $this->setForeignKeyChecks($pdo, true);

        return $counts;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insertRow(PDO $pdo, string $table, array $row): void
    {
        $columns = array_keys($row);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $quotedCols = implode(', ', array_map([$this, 'quoteIdent'], $columns));
        $sql = 'INSERT INTO ' . $this->quoteIdent($table)
            . ' (' . $quotedCols . ') VALUES (' . $placeholders . ')';
        $pdo->prepare($sql)->execute(array_values($row));
    }

    /**
     * Ermittelt die zu sichernden Tabellen (alle vorhandenen minus
     * SKIP_TABLES), treiberabhängig.
     *
     * @return array<int, string>
     */
    private function listTables(): array
    {
        $pdo = Database::getConnection();
        $driver = $this->driverName();

        if ($driver === 'sqlite') {
            $rows = $pdo->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
            )->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        }

        $tables = [];
        foreach ($rows as $name) {
            if (!in_array($name, self::SKIP_TABLES, true)) {
                $tables[] = $name;
            }
        }
        sort($tables);
        return $tables;
    }

    private function setForeignKeyChecks(PDO $pdo, bool $enabled): void
    {
        if ($this->driverName() === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ' . ($enabled ? 'ON' : 'OFF'));
        } else {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = ' . ($enabled ? '1' : '0'));
        }
    }

    private function quoteIdent(string $ident): string
    {
        // Nur erlaubte Bezeichner zulassen (Tabellen-/Spaltennamen stammen aus
        // dem Schema bzw. der Sicherung – defensiv dennoch validieren).
        if (!preg_match('/^[A-Za-z0-9_]+$/', $ident)) {
            throw new RuntimeException('Ungültiger Bezeichner: ' . $ident);
        }
        return '`' . $ident . '`';
    }

    private function driverName(): string
    {
        return (string) Database::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    // ==================================================================
    // Dateien
    // ==================================================================

    /**
     * Stellt die hochgeladenen Dateien aus dem Archiv wieder her. Vorhandene
     * Inhalte der Ablageverzeichnisse werden zuvor geleert.
     */
    private function restoreFiles(ZipArchive $zip): int
    {
        $root = $this->projectRoot();

        // Inhaltsverzeichnisse leeren (nur Dateien, Struktur bleibt bestehen).
        foreach (self::CONTENT_DIRS as $relDir) {
            $absDir = $root . '/' . $relDir;
            if (is_dir($absDir)) {
                foreach ($this->listFilesRecursive($absDir) as $absFile) {
                    @unlink($absFile);
                }
            }
        }

        $restored = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || substr($name, -1) === '/') {
                continue;
            }
            // Nur Datei-Einträge unterhalb von storage/ zulassen; kein Zip-Slip.
            if (strncmp($name, self::ZIP_FILE_PREFIX, strlen(self::ZIP_FILE_PREFIX)) !== 0) {
                continue;
            }
            $relPath = $this->sanitizeRelPath($name);
            if ($relPath === null) {
                continue;
            }

            $target = $root . '/' . $relPath;
            $targetDir = dirname($target);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0770, true) && !is_dir($targetDir)) {
                throw new RuntimeException('Verzeichnis konnte nicht angelegt werden: ' . $targetDir);
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                continue;
            }
            $out = fopen($target, 'wb');
            if ($out === false) {
                fclose($stream);
                throw new RuntimeException('Datei konnte nicht geschrieben werden: ' . $relPath);
            }
            stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
            $restored++;
        }

        return $restored;
    }

    /**
     * Wandelt einen Archiv-Eintragsnamen in einen sicheren relativen Pfad um
     * oder gibt null zurück, wenn er nicht zulässig ist (Zip-Slip-Schutz).
     */
    private function sanitizeRelPath(string $name): ?string
    {
        $name = str_replace('\\', '/', $name);
        if ($name === '' || $name[0] === '/' || strpos($name, '../') !== false || strpos($name, "\0") !== false) {
            return null;
        }
        return $name;
    }

    /**
     * @return array<int, string> Absolute Pfade aller Dateien unterhalb von $dir
     */
    private function listFilesRecursive(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile()) {
                // .gitkeep/.htaccess-Platzhalter nicht mitsichern.
                $base = $file->getFilename();
                if ($base === '.gitkeep' || $base === '.htaccess') {
                    continue;
                }
                $files[] = $file->getPathname();
            }
        }
        return $files;
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function appVersion(): string
    {
        $versionFile = $this->projectRoot() . '/VERSION';
        if (is_file($versionFile)) {
            return trim((string) file_get_contents($versionFile));
        }
        return 'unbekannt';
    }
}
