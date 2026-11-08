<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regresi historis Mode Dokumentasi.
 *
 * Sejak Vibrant Glass 5 November 2026, mode dokumentasi dicabut:
 * screenshot full-page memakai shell normal dengan sidebar desktop statis.
 * Nama berkas dipertahankan agar pengujian lama tidak hilang diam-diam.
 */
final class ModeDokumentasiShellTest extends TestCase
{
    private static function sumber(string $relatif): string
    {
        $teks = file_get_contents(dirname(__DIR__, 2).'/'.$relatif);
        self::assertIsString($teks);
        return $teks;
    }

    public function testShellTidakLagiMenawarkanModeDokumentasi(): void
    {
        $panel = self::sumber('app/Views/layouts/panel.php');
        self::assertStringContainsString('data-shell="panel"', $panel);
        self::assertStringContainsString('data-sidebar-collapse', $panel);
        self::assertStringContainsString('data-theme-toggle', $panel);
        self::assertStringContainsString('data-menu-toggle', $panel);
        self::assertStringContainsString('data-back-to-top', $panel);
        self::assertStringContainsString('#arrow-up', $panel);
        self::assertStringNotContainsString('data-documentation-toggle', $panel);
        self::assertStringNotContainsString('data-documentation-label', $panel);
    }

    public function testLayoutNormalMenjagaSidebarDesktopDanDrawerHp(): void
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        $app = self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('position: static; align-self: stretch; height: auto;', $app);
        self::assertStringContainsString('grid-template-columns:var(--a-sidebar-ringkas) minmax(0,1fr)', $css);
        self::assertStringContainsString('position:static;', $css);
        self::assertStringContainsString('overflow:visible;', $css);
        self::assertStringContainsString('.a-back-to-top[hidden]', $css);
        self::assertStringContainsString('@media print', $css);
        self::assertStringNotContainsString('data-documentation', $css);
    }

    public function testTemaDanSidebarDisimpanTetapiModeDokumentasiTidak(): void
    {
        $js = self::sumber('public/assets/js/theme-init.js');
        self::assertStringContainsString("getSaved('sla-sks-tema')", $js);
        self::assertStringContainsString("getSaved('sla-sks-sidebar')", $js);
        self::assertStringNotContainsString('sla-sks-mode-dokumentasi', $js);
        self::assertStringNotContainsString('html.dataset.documentation', $js);

        $app = self::sumber('public/assets/js/app.js');
        self::assertStringContainsString('window.scrollTo({ top: 0', $app);
        self::assertStringContainsString('window.scrollY < 440', $app);
        self::assertStringContainsString('main.inert = shouldOpen', $app);
    }
}
