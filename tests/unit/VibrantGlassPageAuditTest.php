<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Audit statis perubahan UI Tahap 3, Batch 1.
 * Pengujian browser/XAMPP dan akses keyboard tetap harus dilakukan pemilik.
 */
final class VibrantGlassPageAuditTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/' . $path;
        self::assertFileExists($file);
        $data = file_get_contents($file);
        self::assertIsString($data);
        return $data;
    }

    private static function lapisanUi(): string
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        $marker = 'VIBRANT GLASS PAGE AUDIT — TAHAP 3';
        $offset = strpos($css, $marker);
        self::assertNotFalse($offset);
        return substr($css, $offset);
    }

    public function testRekapNilaiMemakaiEmpatWarnaDariTokenKpiTanpaMenyentuhAngka(): void
    {
        $css = self::lapisanUi();
        self::assertStringContainsString('.a-rekap-page--tp .a-rekap-metrik__item', $css);
        self::assertStringContainsString('--v3-rekap-bg:var(--vg-kpi-3-bg)', $css);
        self::assertStringContainsString('--v3-rekap-bg:var(--vg-kpi-1-bg)', $css);
        self::assertStringContainsString('--v3-rekap-bg:var(--vg-kpi-5-bg)', $css);
        self::assertStringContainsString('--v3-rekap-bg:var(--vg-kpi-6-bg)', $css);
        self::assertStringContainsString('color:var(--vg-kpi-ink)', $css);
        self::assertStringContainsString('.a-rekap-table__siswa', $css);
        self::assertStringContainsString('.a-rekap-scroll-hint', $css);

        $view = self::sumber('app/Views/guru/rekap_nilai_tp.php');
        foreach (['jumlah_siswa', 'jumlah_final', 'jumlah_sementara', 'jumlah_belum',
            'a-rekap-metrik__item--final', 'a-rekap-metrik__item--sementara',
            'a-rekap-metrik__item--belum', 'a-rekap-scroll', 'urlEkspor'] as $kunci) {
            self::assertStringContainsString($kunci, $view);
        }
    }

    public function testKelolaTpMenandaiKesiapanTanpaMengubahFormAtauStepService(): void
    {
        $css = self::lapisanUi();
        self::assertStringContainsString('.a-guru-detail-tp', $css);
        self::assertStringContainsString('.a-tp-langkah__item.is-selesai', $css);
        self::assertStringContainsString('.a-tp-langkah__item.is-belum', $css);
        self::assertStringContainsString('#materi-tp', $css);
        self::assertStringContainsString('#kesiapan-tp.a-tp-penutup--siap', $css);

        $view = self::sumber('app/Views/guru/detail_tp.php');
        self::assertStringContainsString('a-tp-penutup--siap', $view);
        self::assertStringContainsString('a-tp-penutup--draf', $view);
        self::assertStringContainsString("'status'] === 'siap'", $view);
        self::assertStringContainsString("view('guru/_step_progress_tp'", $view);
        self::assertStringContainsString('data-materi-form', $view);
        self::assertStringContainsString('csrf_field()', $view);
    }

    public function testAkunAdminTetapMemilikiFormFilterDanAksiPerBaris(): void
    {
        $css = self::lapisanUi();
        foreach (['.a-admin-akun__form', '.a-admin-akun__filter',
            '.a-admin-akun__xlsx', '.a-admin-akun__daftar',
            '.a-admin-akun__tabel', '.a-admin-akun__aksi'] as $pola) {
            self::assertStringContainsString($pola, $css);
        }
        $view = self::sumber('app/Views/admin/akun_daftar.php');
        self::assertStringContainsString('a-admin-akun__form', $view);
        self::assertStringContainsString('a-admin-akun__daftar', $view);
        self::assertStringContainsString('a-admin-akun__xlsx', $view);
        self::assertStringContainsString("view('components/pager'", $view);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString('name="cari"', $view);
        self::assertStringContainsString('name="status"', $view);
    }

    public function testGaleriDanDokumenMencatatCakupanAuditTanpaFiturPalsu(): void
    {
        $galeri = self::sumber('docs/assets/galeri-komponen.html');
        self::assertStringContainsString('a-galeri-v3__metrics', $galeri);
        self::assertStringContainsString('Tanda pisah adalah contoh', $galeri);
        self::assertStringContainsString('Belum Ada Nilai', $galeri);

        $css = self::lapisanUi();
        self::assertStringContainsString('.a-galeri-v3__metrics', $css);
        self::assertStringNotContainsString('.a-atp-kartu', $css);
        self::assertStringNotContainsString('.a-ujian', $css);
        self::assertStringNotContainsString('position:sticky', $css);
        self::assertStringNotContainsString('position:fixed', $css);

        self::assertStringContainsString('## 0C. Tahap 3',
            self::sumber('docs/12-UI-UX-DAN-HALAMAN.md'));
        self::assertStringContainsString('### Tahap 3 — Audit visual',
            self::sumber('docs/13-DESIGN-SYSTEM-CSS.md'));
    }
}
