<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Kontrak statis fondasi Vibrant Glass 2026-11-05.
 * Tidak menggantikan uji browser full-page dan XAMPP.
 */
final class VibrantGlassShellTest extends TestCase
{
    private static function source(string $path): string
    {
        $file = dirname(__DIR__, 2).'/'.$path;
        self::assertFileExists($file);
        $content = file_get_contents($file);
        self::assertIsString($content);
        return $content;
    }

    public function testShellMenjagaNavigasiDanMenghapusModeDokumentasi(): void
    {
        $html = self::source('app/Views/layouts/panel.php');
        self::assertStringContainsString('class="a-app a-app--prototipe"', $html);
        self::assertStringContainsString('class="a-side"', $html);
        self::assertStringContainsString('class="a-main"', $html);
        self::assertStringContainsString('data-sidebar-collapse', $html);
        self::assertStringContainsString('data-menu-toggle', $html);
        self::assertStringContainsString('data-menu-close', $html);
        self::assertStringContainsString('data-back-to-top', $html);
        self::assertStringContainsString('csrf_field()', $html);
        self::assertStringNotContainsString('data-documentation-toggle', $html);
    }

    public function testTemaDanNavigasiTetapPersistenTanpaModeDokumentasi(): void
    {
        $js = self::source('public/assets/js/theme-init.js');
        self::assertStringContainsString('sla-sks-tema', $js);
        self::assertStringContainsString('sla-sks-sidebar', $js);
        self::assertStringContainsString('a-app--is-collapsed', $js);
        self::assertStringNotContainsString('sla-sks-mode-dokumentasi', $js);
        self::assertStringNotContainsString('data-documentation', $js);
    }

    public function testTombolKeAtasDanDrawerMobilePunyaPerilakuAsli(): void
    {
        $js = self::source('public/assets/js/app.js');
        self::assertStringContainsString("'[data-back-to-top]'", $js);
        self::assertStringContainsString('window.scrollTo({ top: 0', $js);
        self::assertStringContainsString('window.scrollY < 440', $js);
        self::assertStringContainsString('prefers-reduced-motion: reduce', $js);
        self::assertStringContainsString('main.inert = shouldOpen', $js);
        self::assertStringContainsString("event.key === 'Escape'", $js);
    }

    public function testCssGridDanPaletteDarkLightTerpisah(): void
    {
        $base = self::source('public/assets/css/app.css');
        self::assertStringContainsString('position: static; align-self: stretch; height: auto;', $base);
        $css = self::source('public/assets/css/emerald-glass.css');
        self::assertStringContainsString('--eg-sidebar:linear-gradient(165deg,rgba(7,26,31,.97)', $css);
        self::assertStringContainsString('--eg-sidebar:linear-gradient(165deg,#EDF7F2,#DDEDE5)', $css);
        self::assertStringContainsString('--eg-side-text:#183F34', $css);
        self::assertStringContainsString('grid-template-columns:var(--a-sidebar-ringkas) minmax(0,1fr)', $css);
        self::assertStringContainsString('.a-back-to-top[hidden] { display:none; }', $css);
        self::assertStringContainsString('@media (max-width:767px)', $css);
        self::assertStringNotContainsString('data-documentation', $css);
    }

    public function testDokumentasiBaruTercerminDalamGaleriDanMaster(): void
    {
        $galeri = self::source('docs/assets/galeri-komponen.html');
        self::assertStringContainsString('Vibrant Glass — shell desktop dan mobile', $galeri);
        self::assertStringNotContainsString('<h2>Mode Dokumentasi — screenshot', $galeri);
        self::assertStringContainsString('Kembali ke Atas', $galeri);
        self::assertStringContainsString('5 November 2026', self::source('docs/12-UI-UX-DAN-HALAMAN.md'));
        self::assertStringContainsString('Vibrant Glass', self::source('docs/13-DESIGN-SYSTEM-CSS.md'));
    }
}
