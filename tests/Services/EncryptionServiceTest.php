<?php

namespace OpenClassbook\Tests\Services;

use OpenClassbook\App;
use OpenClassbook\Services\EncryptionService;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Festen App-Schluessel setzen, damit keine Schluesseldatei erzeugt wird.
        $config = App::config();
        $config['security']['app_encryption_key'] = bin2hex(random_bytes(32));
        App::setConfig($config);
    }

    public function testEncryptDecryptRoundtrip(): void
    {
        $plain = 'Streng vertrauliche Nachricht mit Umlauten: äöü ß 🎓';
        $encrypted = EncryptionService::encrypt($plain);

        $this->assertNotSame($plain, $encrypted);
        $this->assertSame($plain, EncryptionService::decrypt($encrypted));
    }

    public function testEncryptedValueCarriesPrefixAndHidesPlaintext(): void
    {
        $plain = 'Geheimtext';
        $encrypted = EncryptionService::encrypt($plain);

        $this->assertStringStartsWith('enc:v1:', $encrypted);
        $this->assertStringNotContainsString($plain, $encrypted);
        $this->assertTrue(EncryptionService::isEncrypted($encrypted));
    }

    public function testDecryptLeavesLegacyPlaintextUnchanged(): void
    {
        $legacy = 'Alte, unverschluesselte Nachricht';

        $this->assertFalse(EncryptionService::isEncrypted($legacy));
        $this->assertSame($legacy, EncryptionService::decrypt($legacy));
    }

    public function testEncryptionUsesRandomIvSoCiphertextsDiffer(): void
    {
        $plain = 'identischer Klartext';

        $this->assertNotSame(
            EncryptionService::encrypt($plain),
            EncryptionService::encrypt($plain),
            'Zwei Verschluesselungen desselben Klartexts sollten sich durch den zufaelligen IV unterscheiden.'
        );
    }

    public function testEmptyStringRoundtrip(): void
    {
        $encrypted = EncryptionService::encrypt('');
        $this->assertSame('', EncryptionService::decrypt($encrypted));
    }

    public function testUndecryptableCiphertextYieldsPlaceholderNotRawBlob(): void
    {
        // Mit einem anderen Schluessel verschluesseln, dann Schluessel wechseln:
        // die Nachricht ist danach nicht mehr entschluesselbar.
        $encrypted = EncryptionService::encrypt('Streng geheim');

        $config = App::config();
        $config['security']['app_encryption_key'] = bin2hex(random_bytes(32));
        App::setConfig($config);

        $result = EncryptionService::decrypt($encrypted);

        $this->assertStringStartsWith('enc:v1:', $encrypted);
        $this->assertStringNotContainsString('enc:v1:', $result, 'Roher Chiffretext darf niemals ausgegeben werden.');
        $this->assertStringContainsString('nicht entschlüsselt', $result);
    }

    public function testMalformedCiphertextYieldsPlaceholder(): void
    {
        $result = EncryptionService::decrypt('enc:v1:not-valid-base64-@@@');

        $this->assertStringNotContainsString('enc:v1:', $result);
        $this->assertStringContainsString('nicht entschlüsselt', $result);
    }
}
