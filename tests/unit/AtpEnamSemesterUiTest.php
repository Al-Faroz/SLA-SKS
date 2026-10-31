<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AtpEnamSemesterUiTest extends TestCase
{
    public function testDrafEnamHanyaAdaDiPetaAtpDanDilindungiCsrf(): void
    {
        $root = dirname(__DIR__, 2);
        $peta = (string) file_get_contents($root . '/app/Views/guru/peta_tp.php');
        $semester = (string) file_get_contents($root . '/app/Views/guru/semester_atp.php');
        $routes = (string) file_get_contents($root . '/app/Config/Routes.php');

        self::assertStringContainsString('Pembuatan Draf · 6 Semester', $peta);
        self::assertStringContainsString('guru/paket-semester/buat-enam', $peta);
        self::assertStringContainsString('csrf_field()', $peta);
        self::assertStringContainsString('tahun_id[<?= $n ?>]', $peta);
        self::assertStringContainsString('Buat Draf untuk 6 Semester', $peta);
        self::assertStringNotContainsString('dialog-paket-semester', $semester);
        self::assertStringNotContainsString('guru/paket-semester/baru', $routes);
        self::assertStringContainsString(
            "'guru/paket-semester/buat-enam', 'GuruPaketSemester::buatEnam', ['filter' => 'auth:guru']",
            $routes
        );
    }

    public function testTombolMateriKeduaTersembunyiNamunSuntingTetapTersedia(): void
    {
        $root = dirname(__DIR__, 2);
        $view = (string) file_get_contents($root . '/app/Views/guru/detail_tp.php');
        $materi = (string) file_get_contents($root . '/app/Services/MateriDrafService.php');
        $berkas = (string) file_get_contents($root . '/app/Services/BerkasMateriService.php');

        self::assertStringContainsString("jumlah_materi === 0", $view);
        self::assertStringContainsString('satu Materi', $view);
        self::assertStringContainsString('countAllResults() > 0', $materi);
        self::assertStringContainsString('countAllResults() > 0', $berkas);
    }
    public function testLulusDiagnostikTetapWajibSumatifSebelumTuntas(): void
    {
        $root = dirname(__DIR__, 2);
        $diagnostik = (string) file_get_contents(
            $root . '/app/Services/MesinDiagnostikSiswaService.php'
        );
        $katalog = (string) file_get_contents(
            $root . '/app/Services/KatalogPembelajaranSiswaService.php'
        );
        $materi = (string) file_get_contents(
            $root . '/app/Services/MateriSiswaService.php'
        );
        self::assertStringContainsString('$lulus ? \'sumatif\' : \'belajar\'', $diagnostik);
        self::assertStringContainsString("->where('u.jenis', 'sumatif')", $katalog);
        self::assertStringContainsString("->where('u.status', 'selesai_sah')", $katalog);
        self::assertStringContainsString("'sumatif', 'tuntas'", $materi);
    }

}
