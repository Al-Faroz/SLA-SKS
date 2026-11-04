<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * A14-4: regresi tampilan Kelola Pengumuman Admin/Guru dan FAQ Admin.
 * Satu view dipakai ketiga halaman; database/izin tetap diuji di
 * InformasiGlobalDatabaseTest dan PengumumanGuruGenMariaDbTest.
 */
final class InformasiTabelUiTest extends TestCase
{
    private static function sumber(string $file): string
    {
        $hasil = file_get_contents(dirname(__DIR__, 2) . '/' . $file);
        self::assertIsString($hasil, $file . ' harus tersedia');
        return $hasil;
    }

    public function testTabelMenggunakanKomponenResmiDanVarianJumlahKolom(): void
    {
        $view = self::sumber('app/Views/informasi/kelola.php');
        self::assertStringContainsString('a-tbl a-tbl--cards a-informasi__tabel', $view);
        self::assertStringNotContainsString('class="a-tabel"', $view);
        self::assertStringContainsString('a-informasi__tabel--faq', $view);
        self::assertStringContainsString('a-informasi__tabel--pengumuman', $view);
        self::assertStringContainsString("<?php if (\$jenis === 'faq'): ?><th scope=\"col\">Urutan</th>", $view);
        self::assertStringContainsString('scope="row"', $view);
        foreach (['Status', 'Urutan', 'Versi', 'Diperbarui (UTC)', 'Aksi'] as $label) {
            self::assertStringContainsString('data-label="' . $label . '"', $view);
        }
        self::assertStringContainsString("data-label=\"<?= esc(\$kolomJudul, 'attr') ?>\"", $view);
    }

    public function testTombolDanPaginasiTidakKehilanganRuteDanCsrf(): void
    {
        $view = self::sumber('app/Views/informasi/kelola.php');
        self::assertStringContainsString('a-informasi__row-actions', $view);
        self::assertStringContainsString("site_url(\$prefix.'/tambah')", $view);
        self::assertStringContainsString("'/.(int)\$item['id'].'/sunting'", $view);
        self::assertStringContainsString("'/.(int)\$item['id'].'/arsip'", $view);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString('name="versi"', $view);
        self::assertStringContainsString("confirm('Arsipkan informasi ini?", $view);
        self::assertStringContainsString("daftar['total_halaman']", $view);
        self::assertStringContainsString("esc(\$jenis === 'faq' ? \$item['pertanyaan'] : \$item['judul'])", $view);
    }

    public function testCssMengalokasikanLebarKolomDanTampilanResponsif(): void
    {
        $css = self::sumber('public/assets/css/app.css');
        $awal = strpos($css, '/* A14-4: tabel kelola FAQ/Pengumuman');
        self::assertNotFalse($awal);
        $potongan = substr($css, $awal, 2500);
        self::assertStringContainsString('width:100%;', $potongan);
        self::assertStringContainsString('table-layout:fixed;', $potongan);
        self::assertStringContainsString('.a-informasi__tabel--faq thead th:nth-child(6)', $potongan);
        self::assertStringContainsString('.a-informasi__tabel--pengumuman thead th:nth-child(5)', $potongan);
        self::assertStringContainsString('.a-informasi__row-actions', $potongan);
        self::assertStringContainsString('@media (max-width:980px)', $potongan);
        self::assertStringContainsString('table-layout:auto;', $potongan);
        self::assertStringContainsString('min-width:0;', $potongan);
        self::assertStringContainsString('font-variant-numeric:tabular-nums;', $potongan);
    }

    public function testTemaEmeraldGlassMenargetkanKomponenTabelYangSama(): void
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        self::assertStringContainsString('.a-page .a-tbl thead th', $css);
        self::assertStringContainsString('.a-page .a-tbl tbody', $css);
        self::assertStringContainsString('.a-page .a-tbl--cards tbody', $css);
        self::assertStringContainsString('var(--eg-table)', $css);
    }
}
