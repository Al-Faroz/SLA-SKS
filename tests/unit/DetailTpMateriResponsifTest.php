<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DetailTpMateriResponsifTest extends TestCase
{
    private static function file(string $path): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($source, $path . ' harus tersedia');
        return $source;
    }

    public function testTabelIdentitasDanMateriTetapResponsifTanpaChecklistGanda(): void
    {
        $view = self::file('app/Views/guru/detail_tp.php');
        self::assertStringContainsString('a-tbl a-tp-identitas', $view);
        self::assertStringNotContainsString('a-tbl a-tbl--cards a-tp-checklist', $view);
        self::assertStringContainsString('id="kesiapan-tp"', $view);
        self::assertStringContainsString('Lihat status dan versi TP', $view);
        self::assertStringContainsString('a-tbl a-tbl--cards a-tp-materi-tabel', $view);
        foreach (['No.', 'Judul', 'Jenis', 'Status', 'Pratinjau', 'Urutan', 'Edit', 'Hapus'] as $label) {
            self::assertStringContainsString('data-label="' . $label . '"', $view);
        }
        self::assertStringNotContainsString('Pemeriksaan awal kelengkapan konten', $view);
    }

    public function testSeluruhTindakanMateriTetapAdaDanMenggunakanPostCsrf(): void
    {
        $view = self::file('app/Views/guru/detail_tp.php');
        foreach (['/pratinjau', '/urut', '/edit', '/hapus'] as $action) {
            self::assertStringContainsString($action, $view);
        }
        self::assertStringContainsString('data-materi-hapus-form', $view);
        self::assertStringContainsString('data-materi-hapus', $view);
        self::assertStringContainsString('name="konfirmasi" value="HAPUS"', $view);
        self::assertStringContainsString('name="struktur_versi"', $view);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString("'naik' => 'Naik', 'turun' => 'Turun'", $view);
        self::assertStringContainsString("'status'] === 'draf'", $view);
    }

    public function testFormTambahDanEditMemakaiPartialDanWrapperYangSama(): void
    {
        $tambah = self::file('app/Views/guru/detail_tp.php');
        $edit = self::file('app/Views/guru/materi_edit.php');
        foreach ([$tambah, $edit] as $view) {
            self::assertStringContainsString('class="a-materi-form-fields"', $view);
            self::assertStringContainsString("view('guru/_materi_fields'", $view);
            self::assertStringContainsString('data-materi-form', $view);
            self::assertStringContainsString('enctype="multipart/form-data"', $view);
            self::assertStringContainsString('csrf_field()', $view);
        }
        $partial = self::file('app/Views/guru/_materi_fields.php');
        foreach (['name="jenis"', 'name="media"', 'name="min_waktu_menit"',
            'name="petunjuk_resume"', 'name="petunjuk_hots"'] as $field) {
            self::assertStringContainsString($field, $partial);
        }
        self::assertStringContainsString('PDF maksimal 15 MB dan 40 halaman.', $partial);
        self::assertStringContainsString('data-materi-file-error', $partial);
    }

    public function testCssResponsifDanGaleriTersedia(): void
    {
        $css = self::file('public/assets/css/app.css');
        $gallery = self::file('docs/assets/galeri-komponen.html');
        self::assertStringContainsString('a-tp-materi-tabel', $css);
        self::assertStringContainsString('.a-materi-form-fields', $css);
        self::assertStringContainsString('@media (max-width:980px)', $css);
        self::assertStringContainsString('@media (min-width:1100px)', $css);
        self::assertStringContainsString('a-tbl--cards a-tp-materi-tabel', $gallery);
        self::assertStringContainsString('data-label="Judul"', $gallery);
    }
}
