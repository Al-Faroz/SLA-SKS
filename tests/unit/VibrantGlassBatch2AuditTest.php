<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Kontrak statis UI Vibrant Glass Tahap 3 Batch 2.
 *
 * Tidak menggantikan uji runtime XAMPP, klik form, dan visual Dark/Light.
 */
final class VibrantGlassBatch2AuditTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/' . $path;
        self::assertFileExists($file);
        $text = file_get_contents($file);
        self::assertIsString($text);
        return $text;
    }

    private static function cssBatch2(): string
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        $pos = strpos($css, 'VIBRANT GLASS PAGE AUDIT — BATCH 2');
        self::assertNotFalse($pos);
        return substr($css, $pos);
    }

    public function testBankSoalMempertahankanDuaTabKkmSoalDanReviewWord(): void
    {
        $view = self::sumber('app/Views/guru/bank_soal.php');
        foreach ([
            'a-guru-bank-soal--vibrant',
            'a-bank-soal__ringkasan-grid',
            'a-bank-soal__tabs',
            "['diagnostik' => 'Diagnostik', 'sumatif' => 'Sumatif']",
            'aria-current="page"',
            'a-bank-soal__kkm',
            'a-bank-soal__scroll',
            'a-tbl--cards a-tbl--bank',
            'a-soal-status-form',
            'a-guru-bank-soal__word-form',
            'a-guru-bank-soal__upload-form',
            'word_riwayat',
            'csrf_field()',
        ] as $needle) {
            self::assertStringContainsString($needle, $view);
        }
        $css = self::cssBatch2();
        self::assertStringContainsString('.a-bank-soal__status.is-cukup', $css);
        self::assertStringContainsString('.a-bank-soal__tab.is-aktif', $css);
        self::assertStringContainsString('.a-bank-soal__kkm-ringkas', $css);
        self::assertStringContainsString('.a-bank-soal__scroll:focus-visible', $css);
    }

    public function testAntreanTetapMempertahankanLimaFilterSkorDanPager(): void
    {
        $view = self::sumber('app/Views/guru/antrean_penilaian.php');
        foreach ([
            'a-penilaian-page--antrean', 'a-penilaian-filter-card',
            'a-penilaian-list-card', 'a-penilaian-scroll',
            'a-penilaian-tabel__aksi', 'a-penilaian-pager',
            'name="pilihan"', 'name="semester"', 'name="bab"',
            'name="jenis"', 'name="status_penilaian"',
            'guru/koreksi-nilai/', 'guru/penilaian/',
        ] as $needle) {
            self::assertStringContainsString($needle, $view);
        }
        $css = self::cssBatch2();
        self::assertStringContainsString('grid-template-columns:minmax(215px,1.75fr)', $css);
        self::assertStringContainsString('.a-penilaian-tabel__aksi', $css);
        self::assertStringContainsString('@media (max-width:680px)', $css);
    }

    public function testPenilaianMemisahkanAcuanJawabanDanAksiKoreksi(): void
    {
        $view = self::sumber('app/Views/guru/nilai_kegiatan.php');
        foreach ([
            'a-penilaian-page--detail', 'a-penilaian-petunjuk',
            'a-card--jawaban-siswa', 'a-penilaian-skor-card',
            'versi_konten_tp_id', 'dikirim_pada',
            'name="nilai"', 'name="umpan_balik"',
            'data-nilai-simpan', 'data-nilai-lanjut',
            'guru/koreksi-nilai/', 'csrf_field()',
        ] as $needle) {
            self::assertStringContainsString($needle, $view);
        }
        $css = self::cssBatch2();
        self::assertStringContainsString('.a-penilaian-page--detail .a-penilaian-isi', $css);
        self::assertStringContainsString('.a-penilaian-page--detail .a-penilaian-form-grid', $css);
    }

    public function testEditorSoalMasihMenggunakanPilihanKunciDanPratinjauServer(): void
    {
        $view = self::sumber('app/Views/guru/soal_form.php');
        foreach ([
            'a-guru-soal-form', 'form-soal-guru',
            'a-soal-kunci', 'radiogroup', 'pratinjau-soal',
            'hasil-pratinjau-soal', 'data-upload-url',
            'data-preview-url', 'csrf_field()',
        ] as $needle) {
            self::assertStringContainsString($needle, $view);
        }
        $css = self::cssBatch2();
        self::assertStringContainsString('.a-guru-soal-form .a-soal-kunci label:has(input:checked)', $css);
        self::assertStringContainsString('.a-guru-soal-form .a-soal-kunci label:focus-within', $css);
        self::assertStringContainsString('#hasil-pratinjau-soal[hidden]', $css);
    }

    public function testReviewWordTetapMemerlukanKonfirmasiDanBukanSimpanOtomatis(): void
    {
        $view = self::sumber('app/Views/guru/review_bank_soal.php');
        foreach ([
            'a-guru-review-word', '<details', '<summary>',
            'a-word-preview', 'a-review-word__kunci-benar',
            '/konfirmasi', 'csrf_field()', 'Konfirmasi &amp; Simpan',
        ] as $needle) {
            self::assertStringContainsString($needle, $view);
        }
        $css = self::cssBatch2();
        self::assertStringContainsString('.a-guru-review-word .a-word-review-item[open]', $css);
        self::assertStringNotContainsString('.a-ujian', $css);
        self::assertStringNotContainsString('.a-atp-kartu', $css);
    }

    public function testDokumenMencatatBatch2DanTidakAdaModeDokumentasiBaru(): void
    {
        $doc12 = self::sumber('docs/12-UI-UX-DAN-HALAMAN.md');
        $doc13 = self::sumber('docs/13-DESIGN-SYSTEM-CSS.md');
        self::assertStringContainsString('Batch 2', $doc12);
        self::assertStringContainsString('BATCH 2', $doc13);
        $css = self::cssBatch2();
        self::assertStringNotContainsString('data-documentation', $css);
        self::assertStringNotContainsString('position:sticky', $css);
        self::assertStringNotContainsString('position:fixed', $css);
    }
}
