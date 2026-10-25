<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PembacaMateriNavigasiTest extends TestCase
{
    public function testHalamanMempunyaiTombolFullScreenDanZoom(): void
    {
        $root=dirname(__DIR__,2);
        $view=file_get_contents($root.'/app/Views/siswa/materi.php');
        $js=file_get_contents($root.'/public/assets/js/pembaca-materi.js');
        self::assertIsString($view);
        self::assertIsString($js);
        self::assertStringContainsString('data-pembaca-fullscreen', $view);
        self::assertStringContainsString('data-pembaca-toggle', $view);
        self::assertStringContainsString('data-zoom-tambah', $view);
        self::assertStringContainsString('data-zoom-kurang', $view);
        self::assertStringContainsString('data-zoom-reset', $view);
        self::assertStringContainsString('data-zoom-nilai', $view);
        self::assertStringContainsString('requestFullscreen()', $js);
        self::assertStringContainsString('exitFullscreen()', $js);
        self::assertStringContainsString('fullscreenchange', $js);
        self::assertStringContainsString('MAX = 300', $js);
        self::assertStringContainsString('MIN = 50', $js);
        self::assertStringNotContainsString('fetch(', $js);
        self::assertStringNotContainsString('progres_materi', $js);
    }

    public function testMateriSelanjutnyaDitautkanHanyaSetelahGateSah(): void
    {
        $root=dirname(__DIR__,2);
        $service=file_get_contents($root.'/app/Services/MateriSiswaService.php');
        $view=file_get_contents($root.'/app/Views/siswa/materi.php');
        self::assertStringContainsString('materi_berikutnya', $view);
        self::assertStringContainsString('materi_berikutnya', $service);
        self::assertStringContainsString("['boleh_buka']", $view);
        self::assertStringContainsString('disabled aria-describedby', $view);
        self::assertStringContainsString('Resume', $service);
        self::assertStringContainsString('HOTS', $service);
        self::assertStringContainsString('Materi Selanjutnya', $view);
        self::assertStringContainsString("'tuntas'", $service);
    }

    public function testBatasPencatatanGambarTetapMenggunakanHeartbeat(): void
    {
        $root=dirname(__DIR__,2);
        $js=file_get_contents($root.'/public/assets/js/aktivitas-materi.js');
        self::assertIsString($js);
        self::assertStringContainsString("document.querySelector('[data-gambar-observer]')", $js);
        self::assertStringContainsString('observer.observe(viewport)', $js);
        self::assertStringContainsString("image.naturalWidth === 0", $js);
        self::assertStringContainsString("document.hasFocus()", $js);
    }
}
