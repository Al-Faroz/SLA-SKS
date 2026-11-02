<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ModeDokumentasiShellTest extends TestCase
{
    private static function sumber(string $relatif): string
    {
        $teks = file_get_contents(dirname(__DIR__, 2) . '/' . $relatif);
        self::assertIsString($teks);
        return $teks;
    }

    public function testTombolAdaPadaShellBersamaSemuaPeran(): void
    {
        $panel = self::sumber('app/Views/layouts/panel.php');
        self::assertStringContainsString('data-shell="panel"', $panel);
        self::assertStringContainsString('data-documentation-toggle', $panel);
        self::assertStringContainsString('aria-pressed="false"', $panel);
        self::assertStringContainsString('data-documentation-label', $panel);
        self::assertStringContainsString('#camera', $panel);
        self::assertStringContainsString('data-sidebar-collapse', $panel);
        self::assertStringContainsString('data-theme-toggle', $panel);
        self::assertStringContainsString('data-menu-toggle', $panel);
    }

    public function testModeDokumentasiHanyaMengubahLayoutSaatDiaktifkan(): void
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        $app = self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('position: sticky; top: 0; height: 100vh', $app);
        self::assertStringContainsString('html[data-theme][data-documentation="on"]', $css);
        self::assertStringContainsString('@media (min-width:768px)', $css);
        self::assertMatchesRegularExpression(
            '/html\[data-theme\]\[data-documentation="on"\]\s+\.a-app--prototipe\s+\.a-side\s*\{[^}]*position:static;[^}]*height:auto;[^}]*overflow:visible;/s',
            $css
        );
        self::assertStringContainsString('backdrop-filter:none;', $css);
        self::assertStringContainsString('scroll-behavior:auto;', $css);
        self::assertStringContainsString('background:var(--eg-document-wall);', $css);
        self::assertStringContainsString('html[data-theme][data-documentation="on"] .a-app--prototipe .a-page > .a-card', $css);
        self::assertStringContainsString('@media print', $css);
    }

    public function testModeDipertahankanPerTabDanBisaDibatalkan(): void
    {
        $js = self::sumber('public/assets/js/theme-init.js');
        self::assertStringContainsString("const documentationKey = 'sla-sks-mode-dokumentasi'", $js);
        self::assertStringContainsString('sessionStorage.getItem(documentationKey)', $js);
        self::assertStringContainsString("html.dataset.documentation = 'on'", $js);
        self::assertStringContainsString('delete html.dataset.documentation', $js);
        self::assertStringContainsString('sessionStorage.removeItem(documentationKey)', $js);
        self::assertStringContainsString('documentationButton.addEventListener', $js);
        self::assertStringContainsString('aria-pressed', $js);
        self::assertStringContainsString('Aktifkan Mode Dokumentasi', $js);
        // Penyimpanan tema dan sidebar tetap menggunakan mekanisme semula.
        self::assertStringContainsString("getSaved('sla-sks-tema')", $js);
        self::assertStringContainsString("getSaved('sla-sks-sidebar')", $js);
    }
}
