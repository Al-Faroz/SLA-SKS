<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReferensiService;
use CodeIgniter\Test\CIUnitTestCase;

final class K2GuruGenTest extends CIUnitTestCase
{
    public function testHanyaAkunAktifBerperanGuruYangLayak(): void
    {
        $this->assertTrue(ReferensiService::layakMenjadiGuru('aktif', ['guru']));
        $this->assertTrue(ReferensiService::layakMenjadiGuru('aktif', ['admin', 'guru']));
        $this->assertTrue(ReferensiService::layakMenjadiGuru('aktif', ['guru', 'pimpinan']));
        $this->assertFalse(ReferensiService::layakMenjadiGuru('aktif', ['admin']));
        $this->assertFalse(ReferensiService::layakMenjadiGuru('aktif', ['pimpinan']));
        $this->assertFalse(ReferensiService::layakMenjadiGuru('aktif', ['siswa']));
        $this->assertFalse(ReferensiService::layakMenjadiGuru('nonaktif', ['guru']));
        $this->assertFalse(ReferensiService::layakMenjadiGuru('aktif', []));
    }

    public function testSql0003MenambahDuaTabelTanpaMengubahDataK2Lama(): void
    {
        $path = ROOTPATH . 'database/sql/updates/0003_guru_per_gen.sql';
        $sql = file_get_contents($path);
        $this->assertIsString($sql);
        $clean = preg_replace('/^\s*--.*$/m', '', $sql);
        $this->assertSame(2, preg_match_all('/CREATE TABLE\s+/i', $clean));
        $this->assertDoesNotMatchRegularExpression('/\b(DROP|TRUNCATE|DELETE\s+FROM|ALTER\s+TABLE)\b/i', $clean);
        $this->assertStringContainsString('UNIQUE KEY uq_g1_gen_mapel (angkatan_id, mapel_id)', $sql);
        $this->assertStringContainsString("VALUES ('K2-0003')", $sql);
        $this->assertStringNotContainsString('INSERT INTO penugasan_guru_gen', $sql);
    }
}
