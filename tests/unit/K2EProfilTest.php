<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ProfilService;
use CodeIgniter\Test\CIUnitTestCase;

final class K2EProfilTest extends CIUnitTestCase
{
    public function testKontakPribadiDibatasiTanpaMengubahIdentitasResmi(): void
    {
        $this->assertTrue(ProfilService::teleponValid(''));
        $this->assertTrue(ProfilService::teleponValid('08123456789'));
        $this->assertTrue(ProfilService::teleponValid('+62 812 3456 7890'));
        $this->assertFalse(ProfilService::teleponValid('123'));
        $this->assertFalse(ProfilService::teleponValid('<script>'));
        $this->assertFalse(ProfilService::teleponValid('08123456789012345678901234567890'));
    }

    public function testUkuranFotoTidakMelebihiKebijakan(): void
    {
        $this->assertSame(2097152, ProfilService::MAKS_FOTO_BYTE);
        $this->assertSame(1600, ProfilService::MAKS_SISI);
        $this->assertSame(100, ProfilService::MIN_SISI);
    }

    public function testPatchProfilAdditiveTanpaMengubahAkunAtauRiwayat(): void
    {
        $sql = file_get_contents(ROOTPATH . 'database/sql/updates/0007_profil_pengelola.sql');
        $this->assertIsString($sql);
        $bersih = preg_replace('/^\s*--.*$/m', '', $sql);
        $this->assertSame(2, preg_match_all('/CREATE TABLE\s+(foto_profil|profil_pengelola)\s*\(/i', $bersih));
        $this->assertDoesNotMatchRegularExpression('/\b(DROP|TRUNCATE|DELETE\s+FROM|ALTER\s+TABLE|UPDATE\s+pengguna)\b/i', $bersih);
        $this->assertStringContainsString("VALUES ('K2-0007')", $sql);
        $this->assertStringContainsString('foto_menunggu_id', $sql);
        $this->assertStringContainsString('foto_aset_id', $sql);
    }

    public function testKodeProfilTidakMengeksposTeleponKeGuruSiswa(): void
    {
        $kode = file_get_contents(APPPATH . 'Services/ProfilService.php');
        $this->assertIsString($kode);
        $this->assertStringContainsString('penugasan_guru_gen pg', $kode);
        $this->assertStringContainsString("gp.peran='guru'", $kode);
        $this->assertStringContainsString('foto_aset_id', $kode);
        $this->assertStringContainsString("telepon_pribadi", $kode);
        $this->assertStringContainsString('imagecreatefromstring', $kode);
        $this->assertStringContainsString('imagejpeg', $kode);
        $this->assertStringContainsString('imagepng', $kode);
        $this->assertStringContainsString("'foto_menunggu_id' => null", $kode);
        $this->assertStringContainsString('Anda tidak memiliki izin melihat foto ini.', $kode);
    }
}
