<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Kontrak statis komponen Vibrant Glass Tahap 2.
 * Tidak menggantikan QA browser/XAMPP dan pengujian kontras hasil render.
 */
final class VibrantGlassComponentsTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $file = dirname(__DIR__, 2).'/'.$path;
        self::assertFileExists($file);
        $text = file_get_contents($file);
        self::assertIsString($text);
        return $text;
    }

    private static function lapisanKomponen(): string
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        $pos = strpos($css, 'VIBRANT GLASS COMPONENT SYSTEM — TAHAP 2');
        self::assertNotFalse($pos);
        return substr($css, $pos);
    }

    public function testPaletEnamKpiDibedakanAntaraDarkDanLight(): void
    {
        $css = self::lapisanKomponen();
        self::assertStringContainsString('html[data-theme="dark"]', $css);
        self::assertStringContainsString('html[data-theme="light"]', $css);
        for ($i = 1; $i <= 6; $i++) {
            self::assertSame(2, substr_count($css, '--vg-kpi-'.$i.'-bg:'));
            self::assertSame(2, substr_count($css, '--vg-kpi-'.$i.'-line:'));
            self::assertSame(2, substr_count($css, '--vg-kpi-'.$i.'-accent:'));
            self::assertStringContainsString(':nth-child(6n + '.$i.')', $css);
        }
        self::assertStringContainsString('--vg-kpi-1-bg:linear-gradient(135deg,#E8F8F0,#D8EFE4)', $css);
        self::assertStringContainsString('--vg-kpi-6-bg:linear-gradient(135deg,#FCEEF3,#F5DFE9)', $css);
    }

    public function testKpiDipakaiEmpatPeranTanpaMengubahDataView(): void
    {
        $css = self::lapisanKomponen();
        foreach ([
            '.a-proto-metric',
            '.a-dashboard-admin__metric',
            '.a-dashboard-guru__metric',
            '.a-dashboard-pimpinan__metric',
            '.a-dashboard-siswa__metric',
        ] as $kelas) {
            self::assertStringContainsString($kelas, $css);
        }
        self::assertStringContainsString('font-variant-numeric:tabular-nums', $css);
        self::assertStringContainsString('color:var(--vg-kpi-muted)', $css);
        self::assertStringContainsString('.a-proto-metric__symbol', $css);
    }

    public function testFormTabelTombolDanKeadaanFokusMemakaiToken(): void
    {
        $css = self::lapisanKomponen();
        foreach ([
            '--vg-control-bg:', '--vg-control-line:', '--vg-panel-shadow:',
            '--vg-secondary-bg:', '--vg-table-line:', '--vg-danger-bg:',
            '.a-tabel-gulir', '.a-btn--sekunder', '.a-btn--bahaya',
            '[aria-invalid="true"]', ':focus-visible',
            'prefers-reduced-motion:reduce',
        ] as $pola) {
            self::assertStringContainsString($pola, $css);
        }
        self::assertStringContainsString('input:not([type="checkbox"])', $css);
        self::assertStringContainsString('input:not([type="file"])', $css);
        self::assertStringContainsString('input:not([type="submit"])', $css);
        self::assertStringContainsString('@media (min-width:981px)', $css);
    }

    public function testGaleriMencantumkanKpiContohBukanDataNyata(): void
    {
        $galeri = self::sumber('docs/assets/galeri-komponen.html');
        self::assertStringContainsString('Enam kartu KPI — Vibrant Glass', $galeri);
        self::assertSame(6, substr_count($galeri, 'class="a-galeri-vg-kpi__item"'));
        self::assertStringContainsString('Tidak berisi angka aplikasi nyata', $galeri);
        self::assertStringContainsString('Enam gradient kartu Semester resmi tidak berubah', $galeri);
    }
}
