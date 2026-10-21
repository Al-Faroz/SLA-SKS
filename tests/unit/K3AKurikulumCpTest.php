<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CpReferensiService;
use CodeIgniter\Test\CIUnitTestCase;

final class K3AKurikulumCpTest extends CIUnitTestCase
{
    public function testValidasiIdentitasKurikulum(): void
    {
        $this->assertTrue(CpReferensiService::kurikulumValid(
            'Kurikulum Merdeka', 'Edisi 2026', 'Instansi resmi', 'Nomor aturan resmi'
        ));
        $this->assertFalse(CpReferensiService::kurikulumValid(
            '', '2026', 'Instansi resmi', 'Nomor aturan resmi'
        ));
        $this->assertFalse(CpReferensiService::kurikulumValid(
            'Kurikulum Merdeka', '', 'Instansi resmi', 'Nomor aturan resmi'
        ));
    }

    public function testKodeCpDibatasi(): void
    {
        $this->assertTrue(CpReferensiService::kodeValid('MAT.D-01'));
        $this->assertTrue(CpReferensiService::kodeValid('mat-d-02'));
        $this->assertFalse(CpReferensiService::kodeValid('A'));
        $this->assertFalse(CpReferensiService::kodeValid('CP/D/01'));
        $this->assertFalse(CpReferensiService::kodeValid('CP D 01'));
    }

    /** @return array<string,string> */
    private function dataValid(): array
    {
        return [
            'elemen' => 'Bilangan',
            'rumusan' => 'Peserta didik mampu menguasai konsep bilangan dalam konteks pembelajaran.',
            'otoritas' => 'Instansi resmi',
            'nomor_dokumen' => 'Aturan Nomor 123',
            'sumber_url' => 'https://example.org/dokumen-cp',
            'tanggal_berlaku' => '2026-07-01',
        ];
    }

    public function testVersiCpButuhRumusanOtoritasSumberDanTanggalValid(): void
    {
        $this->assertTrue(CpReferensiService::versiValid($this->dataValid()));

        $data = $this->dataValid();
        $data['rumusan'] = 'Pendek';
        $this->assertFalse(CpReferensiService::versiValid($data));

        $data = $this->dataValid();
        $data['sumber_url'] = 'javascript:alert(1)';
        $this->assertFalse(CpReferensiService::versiValid($data));

        $data = $this->dataValid();
        $data['tanggal_berlaku'] = '2026-02-30';
        $this->assertFalse(CpReferensiService::versiValid($data));

        $data = $this->dataValid();
        $data['otoritas'] = '';
        $this->assertFalse(CpReferensiService::versiValid($data));
    }

    public function testPatchKurikulumCpAdditiveDanBernomor(): void
    {
        $sql = file_get_contents(ROOTPATH . 'database/sql/updates/0008_kurikulum_cp.sql');
        $this->assertIsString($sql);
        $tanpaKomentar = preg_replace('/^\s*--.*$/m', '', $sql);
        $this->assertSame(3, preg_match_all('/CREATE TABLE\s+(ref_kurikulum|ref_cp|ref_cp_versi)\s*\(/i', $tanpaKomentar));
        $this->assertDoesNotMatchRegularExpression(
            '/\b(DROP|TRUNCATE|DELETE\s+FROM|ALTER\s+TABLE|UPDATE\s+pengguna)\b/i',
            $tanpaKomentar
        );
        $this->assertStringContainsString("VALUES ('K3-0008')", $sql);
        $this->assertStringContainsString('uq_cp_identitas', $sql);
        $this->assertStringContainsString('uq_cp_nomor_versi', $sql);
    }

    public function testTerbitHanyaDrafDanMenyimpanVersiHistoris(): void
    {
        $kode = file_get_contents(APPPATH . 'Services/CpReferensiService.php');
        $this->assertIsString($kode);
        $this->assertStringContainsString("WHERE id=? FOR UPDATE", $kode);
        $this->assertStringContainsString("WHERE id=? AND cp_id=? FOR UPDATE", $kode);
        $this->assertStringContainsString("versi['status'] !== 'draf'", $kode);
        $this->assertStringContainsString("->where('status', 'aktif')->update(['status' => 'diarsipkan'])", $kode);
        $this->assertStringContainsString("'status' => 'aktif'", $kode);
        $this->assertStringContainsString('transRollback()', $kode);
        $this->assertStringNotContainsString("->table('ref_cp_versi')->delete(", $kode);
    }
}
