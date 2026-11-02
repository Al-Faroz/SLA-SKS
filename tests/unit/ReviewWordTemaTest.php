<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReviewWordTemaTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $isi = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($isi);
        return $isi;
    }

    public function testReviewWordMemakaiKomponenDarkLightTanpaMengubahAlurKonfirmasi(): void
    {
        $view = self::sumber('app/Views/guru/review_bank_soal.php');
        $css = self::sumber('public/assets/css/emerald-glass.css');

        self::assertStringContainsString('class="a-page a-review-word-page"', $view);
        self::assertStringContainsString('class="a-word-review-item"', $view);
        self::assertStringContainsString('a-review-word__kunci-benar', $view);
        self::assertStringContainsString('a-review-word__kunci-lain', $view);
        self::assertStringContainsString('name="tinjau" value="ya" required', $view);
        self::assertStringContainsString("'/konfirmasi'", $view);

        self::assertStringContainsString('.a-review-word-page .a-word-review-item {', $css);
        self::assertStringContainsString('background:var(--eg-table);', $css);
        self::assertStringContainsString('.a-word-review-item > summary', $css);
        self::assertStringContainsString('color:var(--e-text);', $css);
        self::assertStringContainsString('.a-review-word__kunci-benar', $css);
        self::assertStringContainsString('.a-review-word__kunci-lain', $css);
    }

    /** Baca nilai token hex terakhir dalam satu mode, tanpa mengubah CSS aplikasi. */
    private static function token(string $css, string $tema, string $nama): string
    {
        preg_match_all('/html\[data-theme="' . preg_quote($tema, '/') . '"\]\s*\{([^}]*)\}/s', $css, $blocks);
        $found = null;
        foreach ($blocks[1] as $block) {
            if (preg_match('/--' . preg_quote($nama, '/') . '\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $block, $match)) {
                $found = $match[1];
            }
        }
        self::assertNotNull($found, "Token --{$nama} pada tema {$tema} harus tersedia.");
        return $found;
    }

    private static function luminance(string $hex): float
    {
        $nilai = [];
        foreach ([1, 3, 5] as $offset) {
            $channel = hexdec(substr($hex, $offset, 2)) / 255;
            $nilai[] = $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }
        return $nilai[0] * 0.2126 + $nilai[1] * 0.7152 + $nilai[2] * 0.0722;
    }

    public function testKontrasPaletReviewWordMemenuhiStandarTeksAA(): void
    {
        $css = self::sumber('public/assets/css/emerald-glass.css');
        foreach (['dark', 'light'] as $tema) {
            $pasangan = [
                ['e-text', 'eg-table'],
                ['e-text', 'eg-table-head'],
                ['e-muted', 'eg-table-head'],
                ['eg-table-label', 'eg-table-head'],
                ['e-primary', 'eg-table-row'],
            ];
            foreach ($pasangan as [$depan, $belakang]) {
                $a = self::luminance(self::token($css, $tema, $depan));
                $b = self::luminance(self::token($css, $tema, $belakang));
                $rasio = (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
                self::assertGreaterThanOrEqual(
                    4.5, $rasio, "Kontras {$tema}: {$depan} pada {$belakang} harus >= 4.5:1"
                );
            }
        }
    }
}
