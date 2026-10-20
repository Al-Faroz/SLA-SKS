<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MutasiPenggunaService;
use CodeIgniter\Test\CIUnitTestCase;

final class K2CIdentitasTest extends CIUnitTestCase
{
    public function testKoreksiIdentitasSiswaMemerlukanNisn10Angka(): void
    {
        $this->assertTrue(MutasiPenggunaService::identitasValid('siswa', '0123456789', 'Siswa Contoh', ['siswa']));
        $this->assertFalse(MutasiPenggunaService::identitasValid('siswa', '12345', 'Siswa Contoh', ['siswa']));
        $this->assertFalse(MutasiPenggunaService::identitasValid('siswa', '123456789X', 'Siswa Contoh', ['siswa']));
        $this->assertFalse(MutasiPenggunaService::identitasValid('siswa', '0123456789', 'Siswa Contoh', ['guru']));
    }

    public function testIdentitasPengelolaMembatasiPeranDanUsername(): void
    {
        $this->assertTrue(MutasiPenggunaService::identitasValid('pengelola', 'admin.contoh', 'Admin Contoh', ['admin']));
        $this->assertTrue(MutasiPenggunaService::identitasValid('pengelola', 'guru-1', 'Guru Contoh', ['guru', 'pimpinan']));
        $this->assertFalse(MutasiPenggunaService::identitasValid('pengelola', 'guru/1', 'Guru Contoh', ['guru']));
        $this->assertFalse(MutasiPenggunaService::identitasValid('pengelola', 'admin-1', 'AB', ['admin']));
        $this->assertFalse(MutasiPenggunaService::identitasValid('pengelola', 'admin-1', 'Admin Contoh', ['siswa']));
    }

    public function testPatchAuditBersifatAdditiveDanBerversi(): void
    {
        $sql = file_get_contents(ROOTPATH . 'database/sql/updates/0005_riwayat_identitas.sql');
        $this->assertIsString($sql);
        $bersih = preg_replace('/^\s*--.*$/m', '', $sql);
        $this->assertSame(1, preg_match_all('/CREATE TABLE\s+riwayat_identitas\s*\(/i', $bersih));
        $this->assertDoesNotMatchRegularExpression('/\b(DROP|TRUNCATE|DELETE\s+FROM|UPDATE\s+pengguna)\b/i', $bersih);
        $this->assertStringContainsString('username_sebelum', $sql);
        $this->assertStringContainsString('username_sesudah', $sql);
        $this->assertStringContainsString("VALUES ('K2-0005')", $sql);
    }

    public function testKodeMenolakPelepasanHakTanpaValidasi(): void
    {
        $kode = file_get_contents(APPPATH . 'Services/MutasiPenggunaService.php');
        $this->assertIsString($kode);
        $this->assertStringContainsString('penugasan_guru_gen', $kode);
        $this->assertStringContainsString('Admin aktif terakhir', $kode);
        $this->assertStringContainsString('versi_sesi', $kode);
        $this->assertStringContainsString('FOR UPDATE', $kode);
        $this->assertStringContainsString('riwayat_identitas', $kode);
    }
}
