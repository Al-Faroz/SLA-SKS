<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PenggunaAdminService;
use CodeIgniter\Test\CIUnitTestCase;

final class K2BAkunTest extends CIUnitTestCase
{
    public function testJenisAkunTerpisahTanpaMenduplikasiStafDuaPeran(): void
    {
        $this->assertTrue(PenggunaAdminService::jenisPeranSesuai('siswa', ['siswa']));
        $this->assertTrue(PenggunaAdminService::jenisPeranSesuai('pengelola', ['admin']));
        $this->assertTrue(PenggunaAdminService::jenisPeranSesuai('pengelola', ['guru']));
        $this->assertTrue(PenggunaAdminService::jenisPeranSesuai('pengelola', ['admin', 'guru']));
        $this->assertTrue(PenggunaAdminService::jenisPeranSesuai('pengelola', ['guru', 'pimpinan']));
        $this->assertFalse(PenggunaAdminService::jenisPeranSesuai('pengelola', ['siswa']));
        $this->assertFalse(PenggunaAdminService::jenisPeranSesuai('siswa', ['admin']));
        $this->assertFalse(PenggunaAdminService::jenisPeranSesuai('siswa', ['admin', 'siswa']));
        $this->assertFalse(PenggunaAdminService::jenisPeranSesuai('admin', ['admin']));
    }

    public function testSandiSementaraAcakTidakPernahMenggunakanSandiBawaan(): void
    {
        $awal = PenggunaAdminService::buatSandiSementara();
        $berikut = PenggunaAdminService::buatSandiSementara();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/D', $awal);
        $this->assertNotSame($awal, $berikut);
    }

    public function testSqlK2BMenjagaDataLamaDanMembuatRelasiSiswa(): void
    {
        $path = ROOTPATH . 'database/sql/updates/0004_siswa_akun.sql';
        $sql = file_get_contents($path);
        $this->assertIsString($sql);
        $bersih = preg_replace('/^\s*--.*$/m', '', $sql);
        $this->assertSame(1, preg_match_all('/CREATE TABLE\s+siswa\s*\(/i', $bersih));
        $this->assertDoesNotMatchRegularExpression('/\b(DROP|TRUNCATE|DELETE\s+FROM|ALTER\s+TABLE|UPDATE\s+pengguna)\b/i', $bersih);
        $this->assertStringContainsString('UNIQUE KEY uq_siswa_nisn (nisn)', $sql);
        $this->assertStringContainsString("VALUES ('K2-0004')", $sql);
        $this->assertStringContainsString('NULL, NULL, UTC_TIMESTAMP()', $sql);
    }
}
