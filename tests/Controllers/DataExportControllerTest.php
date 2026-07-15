<?php

namespace OpenClassbook\Tests\Controllers;

use OpenClassbook\Controllers\DataExportController;
use PHPUnit\Framework\TestCase;

class DataExportControllerTest extends TestCase
{
    public function testEncodeProducesPrettyValidJson(): void
    {
        $json = DataExportController::encode(['konto' => ['username' => 'lehrer1']]);

        $this->assertJson($json);
        $this->assertStringContainsString("\n", $json, 'Sollte formatiert (JSON_PRETTY_PRINT) sein.');
        $this->assertSame('lehrer1', json_decode($json, true)['konto']['username']);
    }

    public function testEncodeKeepsUnicodeAndSlashesReadable(): void
    {
        $json = DataExportController::encode(['wert' => 'Ümläut & /pfad']);

        $this->assertStringContainsString('Ümläut', $json, 'Unicode darf nicht escaped werden.');
        $this->assertStringContainsString('/pfad', $json, 'Slashes duerfen nicht escaped werden.');
    }

    /**
     * Ungueltige UTF-8-Bytes (z.B. aus Importdaten) duerfen den Export nicht
     * zu einem leeren Download machen: json_encode() gaebe sonst false zurueck.
     */
    public function testEncodeNeverReturnsEmptyOnInvalidUtf8(): void
    {
        $json = DataExportController::encode(['notiz' => "kaputtes \xB1 Byte"]);

        $this->assertNotSame('', $json);
        $this->assertJson($json);
        $this->assertNotNull(json_decode($json), 'Ergebnis muss gueltiges JSON bleiben.');
    }
}
