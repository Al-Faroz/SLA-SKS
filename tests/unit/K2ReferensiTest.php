<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PengaturanService;
use App\Services\ReferensiService;
use CodeIgniter\Test\CIUnitTestCase;

final class K2ReferensiTest extends CIUnitTestCase
{
    public function testSemesterMulaiHanyaSatuSampaiEnam(): void
    {
        foreach (range(1, 6) as $semester) {
            $this->assertTrue(ReferensiService::validasiSemesterMulai($semester));
        }
        $this->assertFalse(ReferensiService::validasiSemesterMulai(0));
        $this->assertFalse(ReferensiService::validasiSemesterMulai(7));
    }

    public function testTahunAjaranHarusBerurutan(): void
    {
        $this->assertTrue(ReferensiService::validasiTahunAjaran('2026/2027'));
        $this->assertFalse(ReferensiService::validasiTahunAjaran('2026/2028'));
        $this->assertFalse(ReferensiService::validasiTahunAjaran('2026'));
        $this->assertFalse(ReferensiService::validasiTahunAjaran('abcd/efgh'));
    }

    public function testIdentitasWajibLengkapDanBerformatValid(): void
    {
        $this->assertArrayHasKey('nama_madrasah', PengaturanService::validasiIdentitas([]));
        $input = [
            'nama_madrasah' => 'MTs Uji',
            'alamat_madrasah' => 'Alamat uji',
            'npsn' => '12345678',
            'nsm' => '123456789012',
            'telepon' => '031-1234567',
            'email' => 'uji@example.org',
            'website' => '',
            'kota_penerbitan' => 'Kota Uji',
            'kepala_madrasah_nama' => 'Kepala Uji',
            'kepala_madrasah_nip' => '000000000000000000',
        ];
        $this->assertSame([], PengaturanService::validasiIdentitas($input));
        $input['npsn'] = '123';
        $this->assertArrayHasKey('npsn', PengaturanService::validasiIdentitas($input));
        $input['npsn'] = '12345678';
        $input['website'] = 'javascript:alert(1)';
        $this->assertArrayHasKey('website', PengaturanService::validasiIdentitas($input));
    }

    public function testPatchK2TidakMemuatPerintahPenghapusanData(): void
    {
        $path = ROOTPATH . 'database/sql/updates/0002_referensi_identitas.sql';
        $sql = file_get_contents($path);
        $this->assertIsString($sql);
        $sql = preg_replace('/^\\s*--.*$/m', '', $sql);
        $this->assertSame(10, preg_match_all('/CREATE TABLE\\s+/i', $sql));
        $this->assertDoesNotMatchRegularExpression('/\\b(DROP|TRUNCATE|DELETE\\s+FROM)\\b/i', $sql);
        $this->assertStringContainsString("VALUES ('K2-0002')", $sql);
    }
}
