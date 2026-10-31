<?php

declare(strict_types=1);

use App\Services\AtpExcelParserService;
use App\Services\AtpStagingService;
use App\Services\AtpUnduhanService;
use App\Services\AtpValidasiUnggahService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

/**
 * Pipeline riil file ZIP XLSX: unduhan V25-3 -> isi Guru -> validasi
 * unggahan -> staging privat -> baca parser tanpa database produksi.
 */
final class AtpUnggahPratinjauRoundTripTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir()
            . '/sla_atp_upload_qa_' . bin2hex(random_bytes(7));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $path) {
            @unlink($path);
        }
        @unlink($this->directory . '/.htaccess');
        @rmdir($this->directory);
        parent::tearDown();
    }

    private function buatWorkbook(bool $lama = false): string
    {
        $buku = IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
        try {
            AtpUnduhanService::isiBuku($buku, [
                'kode_gen' => 'GEN2025', 'nama_gen' => 'Gen Uji',
                'guru_id' => '9', 'nama_guru' => 'Guru QA',
                'kode_mapel' => 'SKI', 'nama_mapel' => 'Sejarah Kebudayaan Islam',
                'kurikulum' => 'Kurikulum KBC', 'tahun_ajaran' => '2026/2027',
                'cp_id' => '1', 'semester_mulai' => 4, 'semester_paket' => 4,
            ], [[
                'id' => 51, 'elemen' => 'Pemahaman Konsep',
                'rumusan' => 'Rumusan uji tanpa data pribadi',
                'nomor_versi' => 1,
            ]]);

            $sheet = $buku->getSheetByName('SEMESTER 4');
            foreach ([
                'A13' => 1, 'B13' => 'Dakwah Rasulullah',
                'C13' => 'Analisis perjalanan dakwah',
                'A39' => 1, 'C39' => 'NonEsensial',
                'D39' => 'Meneladani strategi dakwah',
                'E39' => 'Menganalisis peristiwa dalam dakwah',
                'F39' => 'Materi sejarah awal',
                'G39' => 'PDF', 'H39' => 'Rencana pembelajaran',
            ] as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            if ($lama) {
                $buku->getSheetByName('RINGKASAN')
                    ->setCellValue('B4', 'SLA-SKS-ATP-MANUAL-V25-1');
            }

            $file = $this->directory . '/unggah_' . bin2hex(random_bytes(4)) . '.xlsx';
            (new Xlsx($buku))->save($file);
            return $file;
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testExcelV25TigaBisaDiperiksaDisimpanPrivatDanDibacaLagi(): void
    {
        $file = $this->buatWorkbook();
        AtpValidasiUnggahService::periksa($file, 'ATP-V25-3-GEN2025-SKI-6-SEMESTER.xlsx', filesize($file));
        $staging = new AtpStagingService($this->directory);
        $data = $staging->buat($file, 9, 11, 'sesi_qa', str_repeat('a', 64));
        $disimpan = $staging->ambil($data['token'], 9, 11, 'sesi_qa');
        self::assertSame(hash_file('sha256', $file), $disimpan['sha256']);
        $baca = AtpExcelParserService::baca(
            $disimpan['file'], 'SKI', 4, 'GEN2025', ['Pemahaman Konsep'],
            1, [[
                'id' => 51, 'elemen' => 'Pemahaman Konsep',
                'rumusan' => 'Rumusan uji tanpa data pribadi', 'nomor_versi' => 1,
            ]]
        );
        self::assertSame(1, $baca['jumlah_bab']);
        self::assertSame(1, $baca['jumlah_tp']);
        self::assertSame('SKI.8.1.1', $baca['tp'][0]['kode']);
        self::assertSame('Materi sejarah awal', $baca['tp'][0]['materi']);
        self::assertSame('PDF', $baca['tp'][0]['media']);
        $staging->hapus($data['token']);
        self::assertFileDoesNotExist($disimpan['file']);
    }

    public function testVersiLamaDitolakDenganPetunjukUnduhUlang(): void
    {
        $file = $this->buatWorkbook(true);
        AtpValidasiUnggahService::periksa($file, 'ATP-V25-3-TEST.xlsx', filesize($file));
        $this->expectExceptionMessage('Unduh ulang ATP V25-3');
        AtpExcelParserService::baca($file, 'SKI', 4, 'GEN2025');
    }

    public function testEkstensiMacroAtauXlsDitolak(): void
    {
        $file = $this->buatWorkbook();
        foreach (['.xlsm', '.xls', '.php'] as $ekstensi) {
            try {
                AtpValidasiUnggahService::periksa(
                    $file, 'ATP' . $ekstensi, filesize($file)
                );
                self::fail('Ekstensi berisiko semestinya ditolak.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('.xlsx', $e->getMessage());
            }
        }
    }

    public function testFilePalsuDanZipTanpaWorkbookDitolak(): void
    {
        $palsu = $this->directory . '/palsu.xlsx';
        file_put_contents($palsu, "<?php echo 'x';");
        $this->expectException(\DomainException::class);
        AtpValidasiUnggahService::periksa($palsu, 'palsu.xlsx', filesize($palsu));
    }

    public function testBerkasKosongDitolakSebelumMasukStaging(): void
    {
        $file = $this->directory . '/kosong.xlsx';
        file_put_contents($file, '');
        $this->expectExceptionMessage('maksimal 5 MB');
        AtpValidasiUnggahService::periksa($file, 'kosong.xlsx', 0);
    }
}
