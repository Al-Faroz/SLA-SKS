<?php

declare(strict_types=1);

use App\Services\CpDaftarService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PHPUnit\Framework\TestCase;

final class CpDaftarResponsifTest extends TestCase
{
    public function testFilterDanHalamanMenolakNilaiTidakValid(): void
    {
        $result = CpDaftarService::filter([
            'cari' => '  SKI  ', 'mapel' => '2', 'kurikulum' => '3', 'status' => 'aktif',
        ]);
        self::assertSame(['cari'=>'SKI', 'mapel'=>2, 'kurikulum'=>3, 'status'=>'aktif'], $result);
        self::assertSame(1, CpDaftarService::halamanGet(null));
        self::assertSame(8, CpDaftarService::halamanGet('8'));
        self::assertSame(1, CpDaftarService::halamanGet('0'));
        $this->expectException(\DomainException::class);
        CpDaftarService::filter(['status' => 'disetujui-semua']);
    }

    public function testPencarianBerisiArrayDitolakDenganPesanDomain(): void
    {
        $this->expectException(\DomainException::class);
        CpDaftarService::filter(['cari' => ['salah']]);
    }

    public function testEksporFormulaTetapTeksBukanRumus(): void
    {
        $bytes = CpDaftarService::buatBerkas([[
            'kode' => '=1+1', 'mapel_nama' => '+cmd', 'kurikulum_nama' => 'Kurikulum',
            'kurikulum_versi' => '1', 'fase' => 'D', 'elemen' => '@SUM(A1:A2)',
            'versi_aktif_id' => 1, 'versi_aktif' => 2,
            'versi_draf_id' => null, 'versi_draf' => null,
            'tanggal_berlaku' => '2026-01-01',
        ]]);
        self::assertStringStartsWith('PK', $bytes);
        $tmp = tempnam(sys_get_temp_dir(), 'cp-test-');
        self::assertIsString($tmp);
        try {
            file_put_contents($tmp, $bytes);
            $xlsx = IOFactory::load($tmp);
            try {
                $sheet = $xlsx->getActiveSheet();
                self::assertSame('=1+1', $sheet->getCell('A2')->getValue());
                self::assertSame(DataType::TYPE_STRING, $sheet->getCell('A2')->getDataType());
                self::assertSame('+cmd', $sheet->getCell('B2')->getValue());
                self::assertSame('@SUM(A1:A2)', $sheet->getCell('F2')->getValue());
                self::assertSame('Aktif', $sheet->getCell('I2')->getValue());
            } finally {
                $xlsx->disconnectWorksheets();
            }
        } finally {
            @unlink($tmp);
        }
    }

    public function testTabelCpPetaAtpDanAksiTetapAda(): void
    {
        $root = dirname(__DIR__, 2);
        $cp = file_get_contents($root . '/app/Views/admin/kurikulum_cp.php');
        $atp = file_get_contents($root . '/app/Views/guru/peta_tp.php');
        self::assertIsString($cp);
        self::assertIsString($atp);
        self::assertStringContainsString('a-tbl--cards a-cp__tabel', $cp);
        self::assertStringContainsString("view('components/pager'", $cp);
        self::assertStringContainsString('admin/kurikulum-cp/ekspor', $cp);
        self::assertStringContainsString('data-label="Kode CP"', $cp);
        self::assertStringContainsString('data-label="Aksi"', $cp);
        self::assertStringContainsString('a-tbl--cards a-tbl--peta-atp', $atp);
        self::assertStringContainsString('data-label="Judul TP"', $atp);
        self::assertStringContainsString("site_url('guru/tp/'", $atp);
    }
}
