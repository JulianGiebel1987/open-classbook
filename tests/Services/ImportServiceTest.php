<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\Services\ImportService;
use OpenClassbook\Tests\DatabaseTestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportServiceTest extends DatabaseTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/oc_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);

        parent::tearDown();
    }

    // --- Helper: create Excel files ---

    private function createTeacherExcel(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $sheet->fromArray(['Vorname', 'Nachname', 'Kürzel', 'E-Mail', 'Fächer', 'Klassen'], null, 'A1');

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNum);
            $rowNum++;
        }

        $path = $this->tempDir . '/teachers.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function createStudentExcel(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $sheet->fromArray(['Vorname', 'Nachname', 'Klasse', 'Geburtsdatum', 'Erziehungsberechtigten-Email'], null, 'A1');

        // Data rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNum);
            $rowNum++;
        }

        $path = $this->tempDir . '/students.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    // --- Teacher preview tests ---

    public function testPreviewTeachersWithValidData(): void
    {
        $path = $this->createTeacherExcel([
            ['Maria', 'Schmidt', 'SCH', 'schmidt@schule.de', 'Deutsch', '5a'],
            ['Hans', 'Mueller', 'MUE', 'mueller@schule.de', 'Mathematik', '6b'],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertCount(2, $result['rows']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals('Maria', $result['rows'][0]['firstname']);
        $this->assertEquals('Schmidt', $result['rows'][0]['lastname']);
        $this->assertEquals('SCH', $result['rows'][0]['abbreviation']);
        $this->assertEmpty($result['rows'][0]['errors']);
    }

    public function testPreviewTeachersDetectsMissingFirstname(): void
    {
        $path = $this->createTeacherExcel([
            ['', 'Schmidt', 'SCH', 'schmidt@schule.de', 'Deutsch', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertCount(1, $result['rows']);
        $this->assertContains('Vorname fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewTeachersDetectsMissingLastname(): void
    {
        $path = $this->createTeacherExcel([
            ['Maria', '', 'SCH', 'schmidt@schule.de', 'Deutsch', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertContains('Nachname fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewTeachersDetectsMissingAbbreviation(): void
    {
        $path = $this->createTeacherExcel([
            ['Maria', 'Schmidt', '', 'schmidt@schule.de', 'Deutsch', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertContains('Kürzel fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewTeachersDetectsMissingEmail(): void
    {
        $path = $this->createTeacherExcel([
            ['Maria', 'Schmidt', 'SCH', '', 'Deutsch', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertContains('E-Mail fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewTeachersDetectsDuplicateAbbreviation(): void
    {
        // Create existing teacher in DB
        $userId = $this->createTestUser(['username' => 'existing']);
        $this->createTestTeacher($userId, ['abbreviation' => 'SCH']);

        $path = $this->createTeacherExcel([
            ['Maria', 'Schmidt', 'SCH', 'schmidt@schule.de', 'Deutsch', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertNotEmpty($result['rows'][0]['errors']);
        $errorStr = implode(', ', $result['rows'][0]['errors']);
        $this->assertStringContainsString('existiert bereits', $errorStr);
    }

    public function testPreviewTeachersSkipsEmptyRows(): void
    {
        $path = $this->createTeacherExcel([
            ['Maria', 'Schmidt', 'SCH', 'schmidt@schule.de', 'Deutsch', ''],
            ['', '', '', '', '', ''],
            ['Hans', 'Mueller', 'MUE', 'mueller@schule.de', 'Mathe', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertCount(2, $result['rows']);
    }

    public function testPreviewTeachersCollectsMultipleErrors(): void
    {
        $path = $this->createTeacherExcel([
            ['', '', '', '', '', ''],  // empty - skipped
            ['Maria', '', '', '', 'Deutsch', ''],  // missing lastname, abbr, email
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertCount(1, $result['rows']);
        $this->assertGreaterThanOrEqual(3, count($result['rows'][0]['errors']));
    }

    // --- Teacher import tests ---

    public function testImportTeachersCreatesUsersAndTeachers(): void
    {
        $path = $this->createTeacherExcel([
            ['Anna', 'Bauer', 'BAU', 'bauer@schule.de', 'Kunst', ''],
        ]);

        $result = ImportService::importTeachers($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);

        // Verify teacher was created
        $teacher = self::$pdo->query("SELECT * FROM teachers WHERE abbreviation = 'BAU'")->fetch();
        $this->assertNotFalse($teacher);
        $this->assertEquals('Anna', $teacher['firstname']);
        $this->assertEquals('Bauer', $teacher['lastname']);

        // Verify user account was created
        $user = self::$pdo->query("SELECT * FROM users WHERE username = 'bau'")->fetch();
        $this->assertNotFalse($user);
        $this->assertEquals('lehrer', $user['role']);
        $this->assertEquals(1, $user['must_change_password']);
    }

    public function testImportTeachersSkipsRowsWithErrors(): void
    {
        $path = $this->createTeacherExcel([
            ['Anna', 'Bauer', 'BAU2', 'bauer2@schule.de', 'Kunst', ''],
            ['', 'Fehler', 'FEH', 'fehler@schule.de', '', ''],  // missing firstname
        ]);

        $result = ImportService::importTeachers($path);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
    }

    public function testImportTeachersHandlesUsernameCollision(): void
    {
        // Create existing user with username 'bau'
        $this->createTestUser(['username' => 'bau']);

        $path = $this->createTeacherExcel([
            ['Anna', 'Bauer', 'BAU3', 'bauer3@schule.de', 'Kunst', ''],
        ]);

        $result = ImportService::importTeachers($path);

        $this->assertEquals(1, $result['imported']);

        // Username should be 'bau1' because 'bau' was taken
        // Note: the abbreviation is BAU3, so username base is 'bau3'
        $user = self::$pdo->query("SELECT * FROM users WHERE username = 'bau3'")->fetch();
        $this->assertNotFalse($user);
    }

    // --- Student preview tests ---

    public function testPreviewStudentsWithValidData(): void
    {
        $this->createTestClass(['name' => '5a', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '5a', '15.03.2015', 'eltern@mail.de'],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertCount(1, $result['rows']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals('Lisa', $result['rows'][0]['firstname']);
        $this->assertEquals('2015-03-15', $result['rows'][0]['birthday']);
    }

    public function testPreviewStudentsDetectsMissingFirstname(): void
    {
        $this->createTestClass(['name' => '5b', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['', 'Weber', '5b', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertContains('Vorname fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewStudentsDetectsMissingLastname(): void
    {
        $this->createTestClass(['name' => '5c', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', '', '5c', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertContains('Nachname fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewStudentsDetectsMissingClass(): void
    {
        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertContains('Klasse fehlt', $result['rows'][0]['errors']);
    }

    public function testPreviewStudentsDetectsNonexistentClass(): void
    {
        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '99z', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $errorStr = implode(', ', $result['rows'][0]['errors']);
        $this->assertStringContainsString('nicht gefunden', $errorStr);
    }

    public function testPreviewStudentsDetectsInvalidDateFormat(): void
    {
        $this->createTestClass(['name' => '5d', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '5d', '2015-03-15', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $errorStr = implode(', ', $result['rows'][0]['errors']);
        $this->assertStringContainsString('Datumsformat', $errorStr);
    }

    public function testPreviewStudentsAcceptsOptionalBirthday(): void
    {
        $this->createTestClass(['name' => '5e', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '5e', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertEmpty($result['rows'][0]['errors']);
        $this->assertNull($result['rows'][0]['birthday']);
    }

    public function testPreviewStudentsSkipsEmptyRows(): void
    {
        $this->createTestClass(['name' => '5f', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '5f', '', ''],
            ['', '', '', '', ''],
            ['Tom', 'Braun', '5f', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertCount(2, $result['rows']);
    }

    // --- Student import tests ---

    public function testImportStudentsCreatesRecords(): void
    {
        $this->createTestClass(['name' => '5g', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '5g', '15.03.2015', 'eltern@mail.de'],
        ]);

        $result = ImportService::importStudents($path, '2025/2026');

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);

        $student = self::$pdo->query("SELECT * FROM students WHERE firstname = 'Lisa' AND lastname = 'Weber'")->fetch();
        $this->assertNotFalse($student);
        $this->assertEquals('2015-03-15', $student['birthday']);
        $this->assertEquals('eltern@mail.de', $student['guardian_email']);
    }

    public function testImportStudentsSkipsRowsWithErrors(): void
    {
        $this->createTestClass(['name' => '5h', 'school_year' => '2025/2026']);

        $path = $this->createStudentExcel([
            ['Lisa', 'Weber', '5h', '', ''],
            ['', 'Fehler', '5h', '', ''],  // missing firstname
        ]);

        $result = ImportService::importStudents($path, '2025/2026');

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
    }

    // --- Error summary tests ---

    public function testPreviewTeachersErrorSummaryIncludesRowNumber(): void
    {
        $path = $this->createTeacherExcel([
            ['', 'Schmidt', 'SCH2', 'schmidt@schule.de', '', ''],
        ]);

        $result = ImportService::previewTeachers($path);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Zeile 2', $result['errors'][0]);
    }

    public function testPreviewStudentsErrorSummaryIncludesRowNumber(): void
    {
        $path = $this->createStudentExcel([
            ['', 'Weber', '99z', '', ''],
        ]);

        $result = ImportService::previewStudents($path, '2025/2026');

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Zeile 2', $result['errors'][0]);
    }
}
