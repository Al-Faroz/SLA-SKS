<?php

declare(strict_types=1);

use App\Services\KelayakanSumatifSiswaService;
use PHPUnit\Framework\TestCase;

/** A3: kontrak murni kelayakan Sumatif, tanpa koneksi DB produksi. */
final class KelayakanSumatifSiswaTest extends TestCase
{
    public function testDiagnostikLulusTetapPerluSumatifTanpaTugas(): void
    {
        $s = KelayakanSumatifSiswaService::evaluasi('sumatif', 1);
        self::assertTrue($s['siap']);
        self::assertSame('diagnostik_lulus', $s['jalur']);
        self::assertFalse($s['selesai']);
        self::assertFalse($s['bukti']['resume']);
        self::assertStringContainsString('Sumatif tetap wajib', $s['pesan']);
    }

    public function testDiagnostikGagalMenungguSemuaTahapFinal(): void
    {
        $belum = KelayakanSumatifSiswaService::evaluasi('belajar', 1, [
            'media' => true, 'resume' => true, 'hots' => false,
        ]);
        self::assertFalse($belum['siap']);
        self::assertTrue($belum['bukti']['media']);
        self::assertTrue($belum['bukti']['resume']);
        self::assertFalse($belum['bukti']['hots']);

        $lengkap = KelayakanSumatifSiswaService::evaluasi('belajar', 1, [
            'media' => true, 'resume' => true, 'hots' => true,
        ]);
        self::assertTrue($lengkap['siap']);
        self::assertSame('belajar', $lengkap['jalur']);
        self::assertFalse($lengkap['selesai']);
    }

    public function testTidakPercayaBuktiParsialDanKontrakJumlahMateri(): void
    {
        foreach ([0, 2, 3] as $jumlah) {
            self::assertFalse(KelayakanSumatifSiswaService::evaluasi(
                'belajar', $jumlah, ['media' => true, 'resume' => true, 'hots' => true]
            )['siap']);
            self::assertFalse(KelayakanSumatifSiswaService::evaluasi(
                'sumatif', $jumlah
            )['siap']);
        }
        self::assertFalse(KelayakanSumatifSiswaService::evaluasi('belajar', 1, [
            'media' => 'true', 'resume' => 1, 'hots' => true,
        ])['siap']);
    }

    public function testBelumDiagnostikDanTpSelesaiTidakBukaSumatifBaru(): void
    {
        foreach (['diagnostik', 'belum_mulai', 'perlu_bantuan', 'tidak_dikenal'] as $status) {
            self::assertFalse(KelayakanSumatifSiswaService::evaluasi($status, 1, [
                'media' => true, 'resume' => true, 'hots' => true,
            ])['siap']);
        }
        $selesai = KelayakanSumatifSiswaService::evaluasi('tuntas', 1);
        self::assertFalse($selesai['siap']);
        self::assertTrue($selesai['selesai']);
    }

    public function testHalamanMemakaiServiceOtoritatifDanTidakAdaRuteSumatifPalsu(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = file_get_contents($root . '/app/Controllers/SiswaPembelajaran.php');
        $view = file_get_contents($root . '/app/Views/siswa/rincian_tp.php');
        $mediaView = file_get_contents($root . '/app/Views/siswa/materi.php');
        $service = file_get_contents($root . '/app/Services/KelayakanSumatifSiswaService.php');
        $route = file_get_contents($root . '/app/Config/Routes.php');
        self::assertIsString($controller);
        self::assertIsString($view);
        self::assertIsString($mediaView);
        self::assertIsString($service);
        self::assertIsString($route);
        self::assertStringContainsString('(new KelayakanSumatifSiswaService())->periksa($tpId)', $controller);
        self::assertStringContainsString('$kesiapanSumatif', $view);
        self::assertStringContainsString('Periksa syarat Sumatif', $mediaView);
        self::assertStringContainsString('versi_konten_tp_id', $service);
        self::assertStringContainsString('resume_materi', $service);
        self::assertStringContainsString('jawaban_hots_materi', $service);
        self::assertStringNotContainsString("SiswaSumatif::mulai", $route);
    }
}
