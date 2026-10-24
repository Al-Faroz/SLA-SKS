<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PratinjauMateriResponsifTest extends TestCase
{
    private static function source(string $path): string
    {
        $src = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($src, 'Berkas wajib ada: ' . $path);
        return $src;
    }

    public function testNavigasiBenarDanEditHanyaUntukTpDraf(): void
    {
        $view = self::source('app/Views/guru/materi_pratinjau.php');
        self::assertStringContainsString('a-pratinjau-navigasi', $view);
        self::assertStringContainsString("'#daftar-materi'", $view);
        self::assertStringContainsString('href="#materi-pratinjau-heading"', $view);
        self::assertStringContainsString('href="#petunjuk-materi"', $view);
        self::assertStringContainsString('id="petunjuk-materi"', $view);
        self::assertStringContainsString("if (\$tp['status'] === 'draf')", $view);
        self::assertStringContainsString("'/edit'", $view);
        self::assertStringContainsString('Kembali ke daftar Materi', $view);
    }

    public function testPratinjauSeluruhJenisMediaTetapAda(): void
    {
        $view = self::source('app/Views/guru/materi_pratinjau.php');
        foreach (["'youtube'", "'pdf'", "'audio'", "'gambar'"] as $type) {
            self::assertStringContainsString($type, $view);
        }
        self::assertStringContainsString('<details class="a-media-details">', $view);
        self::assertStringContainsString('Tampilkan video', $view);
        self::assertStringContainsString('Tampilkan PDF', $view);
        self::assertStringContainsString('Buka PDF di tab baru', $view);
        self::assertStringContainsString('<audio controls preload="metadata"', $view);
        self::assertStringContainsString('class="a-materi-gambar"', $view);
        self::assertStringContainsString("nl2br(esc(\$materi['petunjuk_resume']))", $view);
        self::assertStringContainsString("nl2br(esc(\$materi['petunjuk_hots']))", $view);
        self::assertStringNotContainsString('<form method="post"', $view);
    }

    public function testIdentitasMediaDanStatusTetapTerlihat(): void
    {
        $view = self::source('app/Views/guru/materi_pratinjau.php');
        self::assertStringContainsString('a-pratinjau-info', $view);
        self::assertStringContainsString("esc(strtoupper((string) \$materi['jenis']))", $view);
        self::assertStringContainsString("(int) \$materi['urutan']", $view);
        self::assertStringContainsString("(int) \$materi['aktif'] === 1", $view);
        self::assertStringContainsString('Materi masih Nonaktif', $view);
        self::assertStringContainsString("esc(\$urlMedia, 'attr')", $view);
    }

    public function testCssTerisolasiDanDokumentasiTersedia(): void
    {
        $css = self::source('public/assets/css/app.css');
        $gallery = self::source('docs/assets/galeri-komponen.html');
        self::assertStringContainsString('.a-pratinjau-navigasi', $css);
        self::assertStringContainsString('.a-pratinjau-media .a-materi-pdf', $css);
        self::assertStringContainsString('.a-pratinjau-media .a-media-video', $css);
        self::assertStringContainsString('.a-pratinjau-media .a-materi-audio audio', $css);
        self::assertStringContainsString('.a-pratinjau-media .a-materi-gambar img', $css);
        self::assertStringContainsString('@media(min-width:1100px)', $css);
        self::assertStringContainsString('@media(max-width:640px)', $css);
        self::assertStringContainsString('a-pratinjau-info', $gallery);
        self::assertStringContainsString('id="contoh-petunjuk"', $gallery);
    }
}
