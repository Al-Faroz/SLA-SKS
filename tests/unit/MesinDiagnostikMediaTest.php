<?php

declare(strict_types=1);

use App\Services\HtmlSoalGuruService;
use PHPUnit\Framework\TestCase;

final class MesinDiagnostikMediaTest extends TestCase
{
    public function testGambarEditorTanpaTeksBisaDitampilkanSaatUjian(): void
    {
        $id = str_repeat('a', 32);
        $data = HtmlSoalGuruService::bersihkan(
            '<p><img src="soal-image:' . $id . '" alt="gambar"></p>', 1, null, true
        );
        self::assertStringContainsString('src="soal-image:' . $id . '"', $data['html']);
        self::assertStringContainsString('a-word-image', $data['html']);
    }

    public function testGambarWordTanpaTeksBisaDitampilkanDenganSumberSah(): void
    {
        $data = HtmlSoalGuruService::bersihkan(
            '<p><img src="word-image:rId15"></p>', 1, str_repeat('b', 32), true
        );
        self::assertStringContainsString('src="word-image:rId15"', $data['html']);
    }

    public function testSanitizerEditorLamaTetapMenolakKontenTanpaTeks(): void
    {
        $this->expectException(\DomainException::class);
        HtmlSoalGuruService::bersihkan(
            '<p><img src="soal-image:' . str_repeat('a', 32) . '"></p>', 1
        );
    }

    public function testScriptAktifTetapDitolakWalauAdaGambar(): void
    {
        $this->expectException(\DomainException::class);
        HtmlSoalGuruService::bersihkan(
            '<p><img src="soal-image:' . str_repeat('a', 32) . '"><script>alert(1)</script></p>',
            1, null, true
        );
    }

    public function testEndpointUjianTidakMengirimKunciAtauSnapshotMentah(): void
    {
        $root=dirname(__DIR__,2);
        $service=file_get_contents($root.'/app/Services/MesinDiagnostikSiswaService.php');
        $view=file_get_contents($root.'/app/Views/siswa/diagnostik_ujian.php');
        $js=file_get_contents($root.'/public/assets/js/diagnostik-ujian.js');
        $routes=file_get_contents($root.'/app/Config/Routes.php');
        self::assertIsString($service);
        self::assertIsString($view);
        self::assertIsString($js);
        self::assertIsString($routes);
        self::assertStringContainsString('SusunButirUjianService::untukSiswa', $service);
        self::assertStringContainsString('HtmlSoalGuruService::bersihkan', $service);
        self::assertStringContainsString('AturanWaktuUjianService::rekonsiliasi', $service);
        self::assertStringContainsString('FOR UPDATE', $service);
        self::assertStringContainsString('csrf', $view);
        self::assertStringContainsString('requestFullscreen()', $js);
        self::assertStringContainsString('fullscreenchange', $js);
        self::assertStringContainsString('visibilitychange', $js);
        self::assertStringContainsString('heartbeat', $js);
        self::assertStringContainsString('window.confirm', $js);
        self::assertStringContainsString("'auth:siswa'", $routes);
    }
}
