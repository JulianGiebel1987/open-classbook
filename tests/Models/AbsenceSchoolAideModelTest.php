<?php

namespace OpenClassbook\Tests\Models;

use OpenClassbook\Models\AbsenceSchoolAide;
use OpenClassbook\Tests\DatabaseTestCase;

class AbsenceSchoolAideModelTest extends DatabaseTestCase
{
    private function makeAide(string $lastname = 'Beispiel'): int
    {
        $userId = $this->createTestUser(['username' => 'a' . uniqid(), 'role' => 'schulbegleiter']);
        return $this->createTestAide($userId, ['lastname' => $lastname]);
    }

    public function testCreateAndFindById(): void
    {
        $aideId = $this->makeAide();
        $id = AbsenceSchoolAide::create([
            'aide_id' => $aideId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-03',
            'type' => 'krank',
            'reason' => 'Grippe',
        ]);

        $absence = AbsenceSchoolAide::findById($id);
        $this->assertNotNull($absence);
        $this->assertEquals('krank', $absence['type']);
        $this->assertEquals('Beispiel', $absence['lastname']);
    }

    public function testFindAllFiltersByTypeAndDate(): void
    {
        $aideId = $this->makeAide();
        AbsenceSchoolAide::create(['aide_id' => $aideId, 'date_from' => '2026-07-01', 'date_to' => '2026-07-02', 'type' => 'krank']);
        AbsenceSchoolAide::create(['aide_id' => $aideId, 'date_from' => '2026-08-01', 'date_to' => '2026-08-02', 'type' => 'fortbildung']);

        $this->assertCount(2, AbsenceSchoolAide::findAll());
        $this->assertCount(1, AbsenceSchoolAide::findAll(['type' => 'krank']));
        // Overlap-Filter: nur Juli
        $julyOnly = AbsenceSchoolAide::findAll(['date_from' => '2026-07-01', 'date_to' => '2026-07-31']);
        $this->assertCount(1, $julyOnly);
    }

    public function testUpdateAndDelete(): void
    {
        $aideId = $this->makeAide();
        $id = AbsenceSchoolAide::create(['aide_id' => $aideId, 'date_from' => '2026-07-01', 'date_to' => '2026-07-02', 'type' => 'krank']);

        AbsenceSchoolAide::update($id, ['date_from' => '2026-07-01', 'date_to' => '2026-07-05', 'type' => 'sonstiges', 'reason' => null, 'notes' => 'x']);
        $this->assertEquals('sonstiges', AbsenceSchoolAide::findById($id)['type']);

        AbsenceSchoolAide::delete($id);
        $this->assertNull(AbsenceSchoolAide::findById($id));
    }
}
