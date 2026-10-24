<?php

declare(strict_types=1);

use App\Services\KatalogPembelajaranSiswaService;
use PHPUnit\Framework\TestCase;

final class KatalogPembelajaranSiswaTest extends TestCase
{
    public function testSemesterSebelumMulaiSelaluDilewati(): void
    {
        $state = KatalogPembelajaranSiswaService::statusTp(3, 4, 1, 'siap', true, true);
        self::assertSame('dilewati', $state['kode']);
        self::assertFalse($state['buka']);
    }

    public function testSemesterBerikutnyaTidakDibukaSebelumProgresTersedia(): void
    {
        $state = KatalogPembelajaranSiswaService::statusTp(5, 4, 1, 'siap', true, true);
        self::assertSame('terkunci', $state['kode']);
        self::assertFalse($state['buka']);
    }

    public function testHanyaTpPertamaBolehDitinjau(): void
    {
        $awal = KatalogPembelajaranSiswaService::statusTp(4, 4, 1, 'siap', true, true);
        $kedua = KatalogPembelajaranSiswaService::statusTp(4, 4, 2, 'siap', true, false);
        self::assertTrue($awal['buka']);
        self::assertSame('pratinjau', $awal['kode']);
        self::assertFalse($kedua['buka']);
        self::assertSame('Selesaikan TP sebelumnya', $kedua['label']);
    }

    public function testTpDrafTidakDapatDitinjau(): void
    {
        $s = KatalogPembelajaranSiswaService::statusTp(4, 4, 1, 'draf', false, true);
        self::assertFalse($s['buka']);
        self::assertSame('Menunggu TP disiapkan Guru', $s['label']);
    }

    public function testTpSiapTanpaSnapshotTidakDapatDitinjau(): void
    {
        $s = KatalogPembelajaranSiswaService::statusTp(4, 4, 1, 'siap', false, true);
        self::assertFalse($s['buka']);
        self::assertSame('menunggu', $s['kode']);
    }

    public function testUrutanTidakSahTidakDapatDitinjau(): void
    {
        $s = KatalogPembelajaranSiswaService::statusTp(4, 4, 0, 'siap', true, true);
        self::assertFalse($s['buka']);
    }

    public function testRoutesDilindungiFilterRoleSiswa(): void
    {
        $file = file_get_contents(dirname(__DIR__, 2) . '/app/Config/Routes.php');
        self::assertIsString($file);
        foreach (['siswa/pembelajaran', 'siswa/mapel/(:num)', 'siswa/semester/(:num)',
            'siswa/tp/(:num)'] as $path) {
            self::assertStringContainsString($path, $file);
        }
        self::assertSame(4, substr_count($file, "'SiswaPembelajaran::"));
        self::assertStringContainsString("'auth:siswa'", $file);
    }

    public function testHakGenDanPenugasanPaketDiperiksaDiService(): void
    {
        $file = file_get_contents(dirname(__DIR__, 2) . '/app/Services/KatalogPembelajaranSiswaService.php');
        self::assertIsString($file);
        foreach (["'peran_aktif'", "'siswa'", "'wajib_ganti_sandi'",
            "'pp.angkatan_id'", "'p.angkatan_id'", "'p.status'", "'aktif'",
            "'pp.mapel_id = p.mapel_id'", "'pp.semester = p.semester'",
            "'pp.tahun_ajaran_id = p.tahun_ajaran_id'", "versi_konten_tp",
            "if (\$tp === null || !\$tp['akses']['buka'])"] as $needle) {
            self::assertStringContainsString($needle, $file);
        }
    }

    public function testRincianTidakMenampilkanSoalAtauMediaSebelumDiagnostik(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root . '/app/Services/KatalogPembelajaranSiswaService.php');
        $view = file_get_contents($root . '/app/Views/siswa/rincian_tp.php');
        self::assertIsString($view);
        self::assertIsString($service);
        self::assertStringContainsString("->select('id,urutan,judul,jenis')", $service);
        self::assertStringNotContainsString("->table('soal')", $service);
        self::assertStringNotContainsString("->table('berkas')", $service);
        self::assertStringContainsString('Diagnostik wajib adalah langkah pertama', $view);
        self::assertStringNotContainsString('Mulai Diagnostik</a>', $view);
        self::assertStringNotContainsString('<form', $view);
        foreach (['rumusan', 'indikator', 'materi'] as $key) {
            self::assertStringContainsString($key, $view);
        }
    }

    public function testNavigasiSiswaDanHalamanResponsifTersedia(): void
    {
        $root = dirname(__DIR__, 2);
        $nav = file_get_contents($root . '/app/Services/NavigasiService.php');
        $home = file_get_contents($root . '/app/Views/akun/beranda.php');
        $css = file_get_contents($root . '/public/assets/css/app.css');
        self::assertIsString($nav);
        self::assertIsString($home);
        self::assertIsString($css);
        self::assertStringContainsString("'path' => 'siswa/pembelajaran'", $nav);
        self::assertStringContainsString("site_url('siswa/pembelajaran')", $home);
        self::assertStringContainsString('.a-siswa-kartu-grid', $css);
        self::assertStringContainsString('@media(max-width:520px)', $css);
    }
}
