<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\Database;
use OpenClassbook\Services\BackupService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests für die vollständige Instanz-Sicherung (Export/Import).
 *
 * Nutzt eine eigene In-Memory-SQLite-Verbindung (nicht DatabaseTestCase),
 * da der Import bewusst mit einer echten Transaktion arbeitet und daher nicht
 * in die transaktionale Test-Isolation von DatabaseTestCase eingebettet werden
 * kann.
 */
class BackupServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        Database::setConnection($this->pdo);

        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, role TEXT)');
        $this->pdo->exec('CREATE TABLE classes (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, school_year TEXT)');
        // Technische/flüchtige Tabellen, die von der Sicherung ausgenommen sein müssen.
        $this->pdo->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT)');
        $this->pdo->exec('CREATE TABLE login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT)');

        $this->pdo->exec("INSERT INTO users (username, role) VALUES ('admin','admin'),('lehrer1','lehrer')");
        $this->pdo->exec("INSERT INTO classes (name, school_year) VALUES ('5a','2025/2026')");
        $this->pdo->exec("INSERT INTO migrations (filename) VALUES ('001_create_users.sql')");
        $this->pdo->exec("INSERT INTO login_attempts (username) VALUES ('hacker')");
    }

    protected function tearDown(): void
    {
        Database::resetConnection();
        parent::tearDown();
    }

    public function testExportDatabaseExcludesTechnicalTablesAndKeepsData(): void
    {
        $export = (new BackupService())->exportDatabase();

        $tables = array_keys($export);
        sort($tables);
        $this->assertSame(['classes', 'users'], $tables, 'Technische Tabellen dürfen nicht exportiert werden.');

        $this->assertCount(2, $export['users']);
        $this->assertSame('admin', $export['users'][0]['username']);
        $this->assertArrayNotHasKey('migrations', $export);
        $this->assertArrayNotHasKey('login_attempts', $export);
    }

    public function testImportDatabaseReplacesDataAndPreservesIds(): void
    {
        $svc = new BackupService();
        $export = $svc->exportDatabase();

        // Datenbestand verändern ...
        $this->pdo->exec('DELETE FROM users');
        $this->pdo->exec("INSERT INTO users (username, role) VALUES ('ghost','lehrer')");
        $this->pdo->exec("UPDATE classes SET name='ÜBERSCHRIEBEN'");

        // ... und wiederherstellen.
        $counts = $svc->importDatabase($export);

        $this->assertSame(2, $counts['users']);

        $users = $this->pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
        $this->assertCount(2, $users);
        $this->assertSame('admin', $users[0]['username']);
        $this->assertSame(1, (int) $users[0]['id'], 'Primärschlüssel müssen erhalten bleiben.');
        $this->assertSame(['admin', 'lehrer1'], array_column($users, 'username'));
        $this->assertNotContains('ghost', array_column($users, 'username'));
        $this->assertSame('5a', $this->pdo->query('SELECT name FROM classes')->fetchColumn());
    }

    public function testImportDoesNotTouchExcludedTables(): void
    {
        $svc = new BackupService();
        $export = $svc->exportDatabase();

        $svc->importDatabase($export);

        $this->assertSame(
            1,
            (int) $this->pdo->query('SELECT COUNT(*) FROM login_attempts')->fetchColumn(),
            'Ausgenommene Tabellen dürfen beim Import unverändert bleiben.'
        );
    }

    public function testArchiveRoundTripRestoresDatabase(): void
    {
        $svc = new BackupService();
        $zipPath = tempnam(sys_get_temp_dir(), 'ocb_backup_test_') . '.zip';

        try {
            $stats = $svc->createArchive($zipPath, 'admin (ID 1)');
            $this->assertFileExists($zipPath);
            $this->assertSame(2, $stats['tables']['users']);

            $manifest = $svc->readManifest($zipPath);
            $this->assertSame(BackupService::FORMAT_ID, $manifest['format']);
            $this->assertSame(2, $manifest['tables']['users']);
            $this->assertArrayNotHasKey('migrations', $manifest['tables']);

            // Daten verändern und aus der Sicherung wiederherstellen.
            $this->pdo->exec("UPDATE users SET username='changed' WHERE id=1");
            $svc->restoreArchive($zipPath);

            $name = $this->pdo->query('SELECT username FROM users WHERE id=1')->fetchColumn();
            $this->assertSame('admin', $name);
        } finally {
            @unlink($zipPath);
        }
    }

    public function testReadManifestRejectsInvalidFile(): void
    {
        $bad = tempnam(sys_get_temp_dir(), 'ocb_bad_') . '.zip';
        file_put_contents($bad, 'this is not a zip archive');

        try {
            $this->expectException(\RuntimeException::class);
            (new BackupService())->readManifest($bad);
        } finally {
            @unlink($bad);
        }
    }
}
