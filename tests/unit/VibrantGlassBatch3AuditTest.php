<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * UI Tahap 3 Batch 3: kontrak markup / CSS, bukan pengganti browser XAMPP.
 * Tidak mengakses database atau data pribadi siswa.
 */
final class VibrantGlassBatch3AuditTest extends TestCase
{
    private static function baca(string $path): string
    {
        $s = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($s, 'Berkas harus ada: ' . $path);
        return $s;
    }

    public function testDaftarPraSemesterAdminDanGuruMenjagaFilterSertaSemantikTabel(): void
    {
        $admin = self::baca('app/Views/admin/pra_semester.php');
        $guru = self::baca('app/Views/guru/pra_semester.php');
        foreach ([$admin, $guru] as $halaman) {
            self::assertStringContainsString('class="a-card a-pra-daftar"', $halaman);
            self::assertStringContainsString('<form method="get"', $halaman);
            self::assertStringContainsString('name="gen"', $halaman);
            self::assertStringContainsString('role="region"', $halaman);
            self::assertStringContainsString('scope="row"', $halaman);
            self::assertStringContainsString('a-pra-table__selected', $halaman);
            self::assertStringContainsString('a-pra-action', $halaman);
        }
        foreach (['name="mapel"', 'name="semester"', 'name="status"'] as $filter) {
            self::assertStringContainsString($filter, $admin);
        }
        self::assertStringContainsString("site_url('guru/pra-semester')", $guru);
        self::assertStringContainsString("site_url('admin/pra-semester')", $admin);
    }

    public function testHalamanDetailPraSemesterTetapMemilikiFormSahDanCsrf(): void
    {
        $detail = self::baca('app/Views/admin/pra_semester_detail.php');
        self::assertStringContainsString('a-pra-sesi-form', $detail);
        self::assertStringContainsString('csrf_field()', $detail);
        self::assertStringContainsString('a-pra-table-wrap', $detail);
        self::assertStringContainsString('role="region"', $detail);
        self::assertStringContainsString('Remedial', $detail);
        self::assertStringContainsString('Susulan', $detail);
    }

    public function testSertifikatPribadiDanMonitoringPimpinanTetapBacaSesuaiKewenangan(): void
    {
        $sertifikat = self::baca('app/Views/siswa/sertifikat.php');
        $monitor = self::baca('app/Views/pimpinan/laporan.php');
        self::assertStringContainsString('a-sertifikat__scroll', $sertifikat);
        self::assertStringContainsString('scope="row"', $sertifikat);
        self::assertStringContainsString('aria-label="Unduh PDF sertifikat', $sertifikat);
        self::assertStringContainsString('Digantikan', $sertifikat);
        self::assertStringContainsString('Hasil Pra-Semester bukan syarat penerbitan.', $sertifikat);
        self::assertStringContainsString('a-laporan__filter', $monitor);
        self::assertStringContainsString('name="paket"', $monitor);
        self::assertStringContainsString('a-laporan__scroll', $monitor);
        self::assertStringContainsString('<progress max="100"', $monitor);
        self::assertStringContainsString('aria-label="Progres TP', $monitor);
    }

    public function testInformasiDanFaqMenyediakanRegionTabelDanPaginasi(): void
    {
        $kelola = self::baca('app/Views/informasi/kelola.php');
        $pengumuman = self::baca('app/Views/informasi/pengumuman.php');
        self::assertStringContainsString('role="region"', $kelola);
        self::assertStringContainsString('aria-label="Tabel kelola informasi dapat digulir"', $kelola);
        self::assertStringContainsString('csrf_field()', $kelola);
        self::assertStringContainsString('a-informasi__pagination', $kelola);
        self::assertStringContainsString('a-informasi__kartu', $pengumuman);
        self::assertStringContainsString('a-informasi__pagination', $pengumuman);
    }

    public function testCssBatch3HanyaMempercantikHalamanTerkaitDanResponsif(): void
    {
        $css = self::baca('public/assets/css/emerald-glass.css');
        $pos = strpos($css, '/* UI Tahap 3 — Batch 3:');
        self::assertNotFalse($pos);
        $scope = substr($css, $pos);
        foreach ([
            '.a-pra-daftar .a-pra-filter',
            '.a-pra-table tbody tr.a-pra-table__selected',
            '.a-pra-sesi-form',
            '.a-sertifikat .a-sertifikat__status-item',
            '.a-sertifikat .a-sertifikat__table .a-btn',
            '.a-laporan .a-laporan__filter',
            '.a-laporan .a-laporan__progres progress',
            '.a-informasi .a-informasi__item',
            ':focus-visible',
            '@media (max-width:980px)',
            '@media (max-width:620px)',
        ] as $selector) {
            self::assertStringContainsString($selector, $scope);
        }
        self::assertStringNotContainsString('!important', $scope);
        self::assertStringNotContainsString('position:fixed', $scope);
        self::assertSame(substr_count($scope, '{'), substr_count($scope, '}'));
    }

    public function testRuteIntiTidakBergantiSelamaPenyelarasanUi(): void
    {
        $routes = self::baca('app/Config/Routes.php');
        foreach ([
            "guru/pra-semester",
            "admin/pra-semester",
            "siswa/sertifikat",
            "pimpinan/laporan",
            "guru/koreksi-nilai",
        ] as $route) {
            self::assertStringContainsString($route, $routes);
        }
    }
}
