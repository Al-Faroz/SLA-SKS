<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regresi markup/CSS A9-2 dari QA screenshot mode Dokumentasi.
 * Baca sumber lokal saja; tidak menggunakan DB ataupun data siswa.
 */
final class A9TampilanGuruTest extends TestCase
{
    private static function berkas(string $path): string
    {
        $isi = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($isi);
        return $isi;
    }

    public function testTabelRekapTpMemakaiDesignSystemDanMetrikGrid(): void
    {
        $view = self::berkas('app/Views/guru/rekap_nilai_tp.php');
        self::assertStringContainsString('class="a-rekap-metrik"', $view);
        self::assertStringContainsString(
            'class="a-tabel-gulir a-rekap-scroll"', $view
        );
        self::assertStringContainsString(
            'class="a-tbl a-rekap-table a-rekap-table--tp"', $view
        );
        self::assertStringNotContainsString('class="a-table"', $view);
        self::assertStringNotContainsString('class="a-siswa-materi-ringkas"', $view);
        self::assertStringContainsString('name="jenis_tp"', $view);
        self::assertStringContainsString('name="bab"', $view);
    }

    public function testTabelPraSemesterTerpisahDanMempunyaiLebarKolom(): void
    {
        $view = self::berkas('app/Views/guru/rekap_nilai_pra_semester.php');
        self::assertStringContainsString('a-filter-rekap-grid--dua', $view);
        self::assertStringContainsString(
            'class="a-tbl a-rekap-table a-rekap-table--pra"', $view
        );
        self::assertStringContainsString('a-rekap-scroll', $view);
        self::assertStringNotContainsString('class="a-table"', $view);
        self::assertStringContainsString('Nilai Terbaik', $view);
        self::assertStringContainsString('status_label', $view);
        self::assertStringContainsString('pra-semester/unduh', $view);
    }

    public function testDetailPenilaianDuaBagianDanFormBerdampingan(): void
    {
        $view = self::berkas('app/Views/guru/nilai_kegiatan.php');
        self::assertStringContainsString('a-penilaian-petunjuk', $view);
        self::assertStringContainsString('a-card--jawaban-siswa', $view);
        self::assertStringContainsString('a-penilaian-form-grid', $view);
        self::assertStringContainsString('data-nilai-guru', $view);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString('materi_judul_snapshot', $view);
        self::assertStringContainsString("esc((string) \$tugas['isi'])", $view);
    }

    public function testCssMemilikiScrollHorizontalDanDropdownDokumentasi(): void
    {
        $css = self::berkas('public/assets/css/app.css');
        $glass = self::berkas('public/assets/css/emerald-glass.css');
        $panel = self::berkas('app/Views/layouts/panel.php');
        self::assertStringContainsString(
            '.a-rekap-page .a-rekap-table--tp { min-width:1480px; }', $css
        );
        self::assertStringContainsString(
            '.a-rekap-page .a-rekap-table--pra { min-width:900px; }', $css
        );
        self::assertStringContainsString(
            '.a-penilaian-page .a-penilaian-form-grid', $css
        );
        self::assertStringContainsString(
            '[data-documentation="on"] .a-app--prototipe .a-side__submenu', $glass
        );
        self::assertStringContainsString('visibility:hidden;', $glass);
        self::assertStringContainsString('a-side__group', $panel);
        self::assertStringContainsString('aria-current="page"', $panel);
    }
}
