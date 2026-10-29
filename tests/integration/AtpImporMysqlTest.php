<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\AtpImporKonfirmasiService;
use App\Services\AtpImporPratinjauService;
use App\Services\AtpStagingService;
use App\Services\AtpValidasiUnggahService;
use App\Services\AtpTemplateLayoutService;
use Config\Database;
use DomainException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

/**
 * Menguji Service asli terhadap database QA sintetis MySQL 8, bukan DB lokal.
 * Kasus: G1, insert idempoten, revisi draf, konflik, rollback atomik.
 */
final class AtpImporMysqlTest extends TestCase
{
    private static $db;
    private array $temporary = [];

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_ATP_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya berjalan pada fixture anonim sla_sks_qa.');
        }
        self::$db = Database::connect([
            'DSN' => '', 'hostname' => '127.0.0.1',
            'username' => 'root', 'password' => '',
            'database' => 'sla_sks_qa', 'DBDriver' => 'MySQLi',
            'DBPrefix' => '', 'pConnect' => false, 'DBDebug' => true,
            'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci',
            'failover' => [], 'port' => 3306,
        ], false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['log_aktivitas', 'progres_tp', 'versi_konten_tp',
                  'materi', 'tp', 'bab'] as $table) {
            self::$db->table($table)->emptyTable();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->temporary as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db !== null) {
            self::$db->close();
        }
    }

    private function guru(int $id = 101): callable
    {
        return static fn (): array => [
            'id' => $id, 'nama' => 'Guru Penguji',
            'peran_aktif' => 'guru', 'status' => 'aktif',
            'wajib_ganti_sandi' => 0,
        ];
    }

    private function preview(string $file, int $guru = 101): array
    {
        return (new AtpImporPratinjauService(self::$db, $this->guru($guru)))
            ->pratinjau(11, $file);
    }

    private function confirm(string $file, string $fingerprint, int $guru = 101): array
    {
        return (new AtpImporKonfirmasiService(self::$db, $this->guru($guru)))
            ->simpan(11, $file, $fingerprint);
    }

    private function workbook(string $tpName = 'Menganalisis Peristiwa',
        string $babName = 'Sejarah Awal', bool $tambahanSemester5 = false): string
    {
        $source = dirname(__DIR__, 2) . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';
        $buku = IOFactory::load($source);
        AtpTemplateLayoutService::terapkan($buku);
        try {
            $sum = $buku->getSheetByName('RINGKASAN');
            $sum->setCellValue('B5', 'GEN2025');
            $sum->setCellValue('B9', 'SKI');
            $sum->setCellValue('B14', '1');
            // REF CP diunduh dari server: fixture menyimpan satu elemen aktif
            // dengan identitas/versi/teks yang harus cocok saat konfirmasi.
            $referensi = $buku->getSheetByName('REFERENSI CP');
            foreach (['A'=>'51', 'B'=>'Pemahaman Konsep',
                      'C'=>'Contoh rumusan CP SKI sintetis',
                      'D'=>'D', 'E'=>'1'] as $col => $value) {
                $referensi->setCellValue($col . '7', $value);
            }
            $sh = $buku->getSheetByName('SEMESTER 4');
            $sh->setCellValue('A13', 1);
            $sh->setCellValue('B13', $babName);
            $sh->setCellValue('C13', 'Sejarah, latar belakang dan perkembangan');
            $sh->setCellValue('A39', 1);
            $sh->setCellValue('B39', 1);
            $sh->setCellValue('C39', 'NonEsensial');
            $sh->setCellValue('D39', $tpName);
            $sh->setCellValue('E39', 'Menganalisis hubungan sebab akibat');
            if ($tambahanSemester5) {
                $s5 = $buku->getSheetByName('SEMESTER 5');
                foreach ([
                    'A13'=>1, 'B13'=>'Tokoh Muslim Nusantara',
                    'C13'=>'Menelaah perkembangan dan keteladanan tokoh',
                    'A39'=>1, 'B39'=>1, 'C39'=>'NonEsensial',
                    'D39'=>'Perjalanan Islam',
                    'E39'=>'Menganalisis penyebaran Islam di Indonesia',
                ] as $cell => $value) {
                    $s5->setCellValue($cell, $value);
                }
            }
            $tmp = tempnam(sys_get_temp_dir(), 'uji_atp_imp_');
            self::assertIsString($tmp);
            (new Xlsx($buku))->save($tmp);
            $this->temporary[] = $tmp;
            return $tmp;
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testSatuKonfirmasiMembuatBabTpDanLog(): void
    {
        $file = $this->workbook();
        $p = $this->preview($file);
        self::assertSame(['baru' => 2, 'ubah' => 0, 'tetap' => 0, 'galat' => 0],
            $p['jumlah']);
        self::assertTrue($p['boleh_konfirmasi']);
        $r = $this->confirm($file, $p['fingerprint']);
        self::assertSame(1, $r['baru_bab']);
        self::assertSame(1, $r['baru_tp']);
        self::assertSame(1, self::$db->table('bab')->countAllResults());
        self::assertSame(1, self::$db->table('tp')->countAllResults());
        self::assertSame('draf', self::$db->table('tp')->get()->getRow('status'));
        self::assertSame('SKI.8.1.1', self::$db->table('tp')->get()->getRow('kode_tp'));
        self::assertSame(1, self::$db->table('log_aktivitas')->countAllResults());
        self::assertSame(0, self::$db->table('materi')->countAllResults());
    }

    public function testPipelineUploadPreviewStagingCommitMenghasilkanBabTp(): void
    {
        $file = $this->workbook();
        AtpValidasiUnggahService::periksa(
            $file, 'ATP-V25-2-GEN2025-SKI-6-SEMESTER.xlsx', filesize($file)
        );
        $pra = $this->preview($file);
        self::assertSame(['baru' => 2, 'ubah' => 0, 'tetap' => 0, 'galat' => 0],
            $pra['jumlah']);
        self::assertSame('SKI.8.1.1', $pra['rencana']['tp'][0]['kode']);
        $dir = sys_get_temp_dir() . '/atp_import_sesi_' . bin2hex(random_bytes(8));
        $stage = new AtpStagingService($dir);
        try {
            $data = $stage->buat($file, 101, 11, 'sesi_guru_qa', $pra['fingerprint']);
            $filePrivat = $stage->ambil($data['token'], 101, 11, 'sesi_guru_qa');
            $hasil = $this->confirm($filePrivat['file'], $filePrivat['fingerprint']);
            self::assertSame(1, $hasil['baru_bab']);
            self::assertSame(1, $hasil['baru_tp']);
            self::assertSame('SKI.8.1.1',
                self::$db->table('tp')->get()->getRow('kode_tp'));
            self::assertSame(1, self::$db->table('log_aktivitas')->countAllResults());
            $stage->hapus($data['token']);
            $this->expectException(\DomainException::class);
            $stage->ambil($data['token'], 101, 11, 'sesi_guru_qa');
        } finally {
            foreach (glob($dir . '/*') ?: [] as $sisa) {
                @unlink($sisa);
            }
            @unlink($dir . '/.htaccess');
            @rmdir($dir);
        }
    }

    public function testImporUlangIdentikTanpaDuplikasiDanPerbedaanDitolak(): void
    {
        $f = $this->workbook();
        $p = $this->preview($f);
        $this->confirm($f, $p['fingerprint']);
        $ulang = $this->preview($f);
        self::assertSame(['baru' => 0, 'ubah' => 0, 'tetap' => 2, 'galat' => 0],
            $ulang['jumlah']);
        self::assertFalse($ulang['boleh_konfirmasi']);

        $g = $this->workbook('Mengurai Sejarah Baru', 'Sejarah Diperbarui');
        $ubah = $this->preview($g);
        self::assertSame(2, $ubah['jumlah']['galat']);
        self::assertFalse($ubah['boleh_konfirmasi']);
        self::assertSame(1, self::$db->table('tp')->countAllResults());
        self::assertSame('Menganalisis Peristiwa',
            self::$db->table('tp')->get()->getRow('nama_tp'));
        self::assertSame(1, self::$db->table('log_aktivitas')->countAllResults());
    }

    public function testDuaSemesterBedaTahunAjaranTetapTransaksional(): void
    {
        $file = $this->workbook('Menganalisis Peristiwa', 'Sejarah Awal', true);
        $preview = $this->preview($file);
        self::assertSame(4, $preview['jumlah']['baru']);
        self::assertSame(0, $preview['jumlah']['galat']);
        $hasil = $this->confirm($file, $preview['fingerprint']);
        self::assertSame(2, $hasil['baru_bab']);
        self::assertSame(2, $hasil['baru_tp']);
        $bab4 = self::$db->table('bab')->where('paket_semester_id', 11)->get()->getRowArray();
        $bab5 = self::$db->table('bab')->where('paket_semester_id', 12)->get()->getRowArray();
        self::assertSame(1, (int) $bab4['tahun_ajaran_id']);
        self::assertSame(2, (int) $bab5['tahun_ajaran_id']);
        self::assertSame(1, self::$db->table('tp')->where('paket_semester_id', 12)->countAllResults());
    }

    public function testSemesterManualYangSudahPunyaBabTanpaTpDitolak(): void
    {
        self::$db->table('bab')->insert([
            'paket_semester_id' => 11, 'angkatan_id' => 1, 'mapel_id' => 5,
            'kurikulum_id' => 1, 'tahun_ajaran_id' => 1, 'kelas_id' => 2,
            'nomor_bab' => 1, 'nama_bab' => 'Sejarah Awal',
            'deskripsi_bab' => 'Sejarah, latar belakang dan perkembangan',
            'dibuat_pada' => '2026-10-27 00:00:00',
        ]);
        $p = $this->preview($this->workbook());
        self::assertGreaterThan(0, $p['jumlah']['galat']);
        self::assertFalse($p['boleh_konfirmasi']);
        self::assertSame(0, self::$db->table('tp')->countAllResults());
    }

    public function testSemesterIdentikBolehDilewatiSaatSemesterLainBaru(): void
    {
        $file = $this->workbook();
        $p = $this->preview($file);
        $this->confirm($file, $p['fingerprint']);
        $dua = $this->workbook('Menganalisis Peristiwa', 'Sejarah Awal', true);
        $p2 = $this->preview($dua);
        self::assertSame(2, $p2['jumlah']['tetap']);
        self::assertSame(2, $p2['jumlah']['baru']);
        self::assertTrue($p2['boleh_konfirmasi']);
        $hasil = $this->confirm($dua, $p2['fingerprint']);
        self::assertSame(1, $hasil['baru_bab']);
        self::assertSame(1, $hasil['baru_tp']);
        self::assertSame(2, $hasil['tetap']);
        self::assertSame(2, self::$db->table('bab')->countAllResults());
        self::assertSame(2, self::$db->table('tp')->countAllResults());
    }

    public function testDuaBabDiSemesterKosongBerhasilDalamSatuBatch(): void
    {
        $file = $this->workbook();
        $book = IOFactory::load($file);
        try {
            $sh = $book->getSheetByName('SEMESTER 4');
            foreach ([
                'A14'=>2, 'B14'=>'Tokoh Islam',
                'C14'=>'Mempelajari tokoh dan keteladanannya',
                'A40'=>2, 'B40'=>1, 'C40'=>'Esensial',
                'D40'=>'Meneladani Tokoh',
                'E40'=>'Mengapresiasi perjuangan tokoh Islam',
            ] as $c => $v) {
                $sh->setCellValue($c, $v);
            }
            (new Xlsx($book))->save($file);
        } finally {
            $book->disconnectWorksheets();
        }
        $preview = $this->preview($file);
        self::assertSame(4, $preview['jumlah']['baru']);
        self::assertTrue($preview['boleh_konfirmasi']);
        $r = $this->confirm($file, $preview['fingerprint']);
        self::assertSame(2, $r['baru_bab']);
        self::assertSame(2, $r['baru_tp']);
        self::assertSame(2, self::$db->table('bab')->countAllResults());
        self::assertSame(2, self::$db->table('tp')->countAllResults());
    }

    public function testKonteksLintasGenDitolak(): void
    {
        $f = $this->workbook();
        try {
            $this->preview($f, 102);
            self::fail('Guru Gen B berhasil melihat paket Gen A.');
        } catch (DomainException $e) {
            self::assertStringContainsString('penugasan', $e->getMessage());
        }
        self::assertSame(0, self::$db->table('tp')->countAllResults());
    }

    public function testKonfirmasiKedaluarsaAkibatPerubahanDbDitolak(): void
    {
        $f = $this->workbook();
        $p = $this->preview($f);
        self::$db->table('bab')->insert([
            'paket_semester_id' => 11, 'angkatan_id' => 1, 'mapel_id' => 5,
            'kurikulum_id' => 1, 'tahun_ajaran_id' => 1, 'kelas_id' => 2,
            'nomor_bab' => 1, 'nama_bab' => 'Konkurensi',
            'deskripsi_bab' => 'Berubah sesudah preview',
            'dibuat_pada' => '2026-10-27 00:00:00',
        ]);
        $this->expectExceptionMessage('Data akademik berubah setelah pratinjau');
        $this->confirm($f, $p['fingerprint']);
    }

    public function testGagalInsertTpMenggulungSeluruhTransaksi(): void
    {
        $f = $this->workbook('GAGAL-SENGAJA');
        $p = $this->preview($f);
        self::assertTrue($p['boleh_konfirmasi']);
        try {
            $this->confirm($f, $p['fingerprint']);
            self::fail('Constraint sintetis seharusnya menggagalkan TP.');
        } catch (\Throwable $e) {
            self::assertSame(0, self::$db->table('bab')->countAllResults(),
                'BAB harus rollback bila INSERT TP gagal.');
            self::assertSame(0, self::$db->table('tp')->countAllResults());
            self::assertSame(0, self::$db->table('log_aktivitas')->countAllResults());
        }
    }

    public function testTpSiapTidakDiubahOlehImpor(): void
    {
        $f = $this->workbook();
        $p = $this->preview($f);
        $this->confirm($f, $p['fingerprint']);
        self::$db->table('tp')->update(['status' => 'siap']);
        $baru = $this->workbook('Nama berbeda');
        $review = $this->preview($baru);
        self::assertGreaterThan(0, $review['jumlah']['galat']);
        self::assertFalse($review['boleh_konfirmasi']);
        self::assertSame('Menganalisis Peristiwa',
            self::$db->table('tp')->get()->getRow('nama_tp'));
    }

    public function testIsiNamaLebih35KarakterDitolakPadaR2(): void
    {
        $f = $this->workbook(str_repeat('T', 37));
        $p = $this->preview($f);
        self::assertGreaterThan(0, $p['jumlah']['galat']);
        self::assertFalse($p['boleh_konfirmasi']);
    }
}
