<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AkunXlsxService;
use CodeIgniter\Test\CIUnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

final class K2DExportXlsxTest extends CIUnitTestCase
{
    public function testTemplatePengelolaDanSiswaTerpisah(): void
    {
        $staf = AkunXlsxService::buatBuku('pengelola', [], true);
        $this->assertSame('Pengelola', $staf->getActiveSheet()->getTitle());
        $this->assertSame('username', $staf->getActiveSheet()->getCell('A1')->getValue());
        $this->assertSame('peran', $staf->getActiveSheet()->getCell('C1')->getValue());
        $this->assertSame('sandi_awal', $staf->getActiveSheet()->getCell('D1')->getValue());
        $this->assertNotNull($staf->getSheetByName('Petunjuk'));
        $staf->disconnectWorksheets();

        $siswa = AkunXlsxService::buatBuku('siswa', [], true);
        $this->assertSame('nisn', $siswa->getActiveSheet()->getCell('A1')->getValue());
        $this->assertSame('kode_gen', $siswa->getActiveSheet()->getCell('C1')->getValue());
        $this->assertSame('semester_mulai', $siswa->getActiveSheet()->getCell('D1')->getValue());
        $this->assertSame('sandi_awal', $siswa->getActiveSheet()->getCell('E1')->getValue());
        $siswa->disconnectWorksheets();
    }

    public function testEksporTidakMenyertakanSandiDanMenjagaNisnNolDiDepan(): void
    {
        $this->assertSame(['nisn', 'nama', 'kode_gen', 'semester_mulai', 'status'],
            AkunXlsxService::kolom('siswa', false));
        $this->assertSame(['username', 'nama', 'peran', 'status'],
            AkunXlsxService::kolom('pengelola', false));

        $buku = AkunXlsxService::buatBuku('siswa', [[
            'nisn' => '0123456789', 'nama' => '=2+3', 'kode_gen' => 'G01',
            'semester_mulai' => '', 'status' => 'aktif', 'sandi_hash' => 'HARUS_DIABAIKAN',
        ]], false);

        $sheet = $buku->getActiveSheet();
        $this->assertSame('0123456789', $sheet->getCell('A2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A2')->getDataType());
        $this->assertSame("'=2+3", $sheet->getCell('B2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
        $this->assertSame('status', $sheet->getCell('E1')->getValue());
        $this->assertNull($sheet->getCell('F1')->getValue());
        $buku->disconnectWorksheets();
    }

    public function testSanitasiFormulaDanKontrolKarakter(): void
    {
        foreach (['=2+2', '+SUM(1)', '-SUM(1)', '@WEBSERVICE("x")', ' =1+2'] as $teks) {
            $this->assertStringStartsWith("'", AkunXlsxService::amanUntukXlsx($teks));
        }
        $this->assertSame('Nama Normal', AkunXlsxService::amanUntukXlsx('Nama Normal'));
        $this->assertSame('AB', AkunXlsxService::amanUntukXlsx("A\x00B"));
    }

    public function testWorkbookHasilBisaDibacaSebagaiFileXlsx(): void
    {
        $buku = AkunXlsxService::buatBuku('siswa', [[
            'nisn' => '0123456789', 'nama' => 'Uji Xlsx', 'kode_gen' => 'G01',
            'semester_mulai' => '2', 'status' => 'aktif',
        ]], false);

        $path = tempnam(sys_get_temp_dir(), 'sks_xlsx_');
        $this->assertNotFalse($path);
        try {
            (new XlsxWriter($buku))->save($path);
            $this->assertGreaterThan(0, filesize($path));
            $baca = (new XlsxReader())->load($path);
            $this->assertSame('0123456789', $baca->getActiveSheet()->getCell('A2')->getValue());
            $this->assertSame('Uji Xlsx', $baca->getActiveSheet()->getCell('B2')->getValue());
            $baca->disconnectWorksheets();
        } finally {
            $buku->disconnectWorksheets();
            @unlink($path);
        }
    }
}
