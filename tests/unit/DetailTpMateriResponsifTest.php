<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DetailTpMateriResponsifTest extends TestCase
{
    private static function file(string $path): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($source);
        return $source;
    }

    public function testDetailTpSatuHalamanDanStepProgress(): void
    {
        $view = self::file('app/Views/guru/detail_tp.php');
        self::assertStringContainsString('a-tbl a-tp-identitas', $view);
        self::assertStringContainsString('id="langkah-tp"', $view);
        self::assertStringContainsString("view('guru/_step_progress_tp'", $view);
        self::assertStringContainsString('id="rumusan-tp"', $view);
        self::assertStringContainsString('id="materi-tp"', $view);
        self::assertStringContainsString('id="kesiapan-tp"', $view);
        self::assertStringContainsString('Lengkapi satu Materi ini', $view);
        self::assertStringContainsString('Periksa kesiapan TP Siap', $view);
        self::assertStringNotContainsString('a-tp-materi-tabel', $view);
        self::assertStringNotContainsString('data-materi-hapus-form', $view);
        self::assertStringNotContainsString('data-label="Urutan"', $view);
    }

    public function testEditorMediaHasilImporMemakaiFormSimpanBerkasDanCsrf(): void
    {
        $view = self::file('app/Views/guru/detail_tp.php');
        $routes = self::file('app/Config/Routes.php');
        self::assertStringContainsString('count($materi) === 1', $view);
        self::assertStringContainsString('class="a-materi-form-fields"', $view);
        self::assertStringContainsString("view('guru/_materi_fields'", $view);
        self::assertStringContainsString('data-materi-form', $view);
        self::assertStringContainsString('enctype="multipart/form-data"', $view);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString('name="struktur_versi"', $view);
        self::assertStringContainsString("'materi/' . (int) ", str_replace("/materi/' . (int) ", "'materi/' . (int) ", $view));
        self::assertStringContainsString("GuruMateri::simpan", $routes);
        self::assertStringNotContainsString("name=\"indikator\"", $view);
    }

    public function testJenisDanFormMediaLengkap(): void
    {
        $partial = self::file('app/Views/guru/_materi_fields.php');
        foreach (['name="judul"', 'name="jenis"', 'name="media"', 'name="min_waktu_menit"',
            'name="petunjuk_resume"', 'name="petunjuk_hots"'] as $field) {
            self::assertStringContainsString($field, $partial);
        }
        self::assertStringContainsString('PDF maksimal 15 MB dan 40 halaman.', $partial);
        self::assertStringContainsString('data-materi-file-error', $partial);
    }

    public function testEditorLamaTetapTerotorisasiDanRedirectKeDetail(): void
    {
        $ctl = self::file('app/Controllers/GuruMateri.php');
        self::assertStringContainsString('public function edit(int $tpId, int $materiId)', $ctl);
        self::assertStringContainsString("'#materi-tp'", $ctl);
        self::assertStringContainsString('DetailTpService())->lihat($tpId)', $ctl);
        self::assertStringContainsString("view('publik/ditolak'", $ctl);
    }

    public function testCssResponsifTetapSatuSistem(): void
    {
        $css = self::file('public/assets/css/app.css');
        self::assertStringContainsString('.a-materi-form-fields', $css);
        self::assertStringContainsString('.a-atp-bab-card__tabel .a-tbl--tp-enam', $css);
        self::assertStringContainsString('.a-tp-langkah', $css);
        self::assertStringContainsString('@media (max-width: 980px)', $css);
    }
}
