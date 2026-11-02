<?php

declare(strict_types=1);

use App\Services\MesinSumatifSiswaService;
use App\Services\NilaiTpService;
use App\Services\SusunButirUjianService;
use PHPUnit\Framework\TestCase;

final class SumatifRemedialNilaiV25Test extends TestCase
{
    private static function attempt(string $status, ?float $skor = null, int $kkm = 75): array
    {
        return ['status' => $status, 'skor' => $skor, 'kkm' => $kkm];
    }

    public function testSatuNormalDanSatuRemedialTanpaPercobaanKetiga(): void
    {
        $normal = self::attempt('selesai_sah', 60.0);
        self::assertSame('normal', MesinSumatifSiswaService::tahapBerikut(null, null));
        self::assertSame('lanjut_normal',
            MesinSumatifSiswaService::tahapBerikut(self::attempt('berjalan'), null));
        self::assertSame('remedial', MesinSumatifSiswaService::tahapBerikut($normal, null));
        self::assertSame('lanjut_remedial', MesinSumatifSiswaService::tahapBerikut(
            $normal, self::attempt('jeda_fullscreen')));
        self::assertSame('selesai', MesinSumatifSiswaService::tahapBerikut(
            $normal, self::attempt('selesai_sah', 55.0)));
        self::assertSame('selesai', MesinSumatifSiswaService::tahapBerikut(
            self::attempt('selesai_sah', 75.0), null));
    }

    public function testBatalTidakMenghabiskanKesempatanSahDiamDiam(): void
    {
        self::assertSame('menunggu_guru', MesinSumatifSiswaService::tahapBerikut(
            self::attempt('dibatalkan_menunggu_guru'), null));
        self::assertSame('menunggu_guru', MesinSumatifSiswaService::tahapBerikut(
            self::attempt('selesai_sah', 20.0),
            self::attempt('dibatalkan_menunggu_guru')));
    }

    public function testRumusDuaJalurDanNilaiNolBukanNull(): void
    {
        self::assertSame([
            'nilai_tp' => 78.25, 'status_nilai' => 'final',
        ], NilaiTpService::hitung('lulus_diagnostik', null, null, 78.25));
        self::assertSame([
            'nilai_tp' => 64.0, 'status_nilai' => 'final',
        ], NilaiTpService::hitung('belajar_wajib', 80.0, 90.0, 50.0));
        self::assertSame([
            'nilai_tp' => null, 'status_nilai' => 'sementara',
        ], NilaiTpService::hitung('belajar_wajib', null, 90.0, 50.0));
        self::assertSame([
            'nilai_tp' => 0.0, 'status_nilai' => 'final',
        ], NilaiTpService::hitung('belajar_wajib', 0.0, 0.0, 0.0));
    }

    public function testBankSumatifMinTigaPuluhPGDanTidakMencampurDiagnostik(): void
    {
        $bank = [];
        for ($i = 1; $i <= 31; $i++) {
            $bank[] = [
                'id' => $i, 'jenis' => $i <= 30 ? 'sumatif' : 'diagnostik',
                'aktif' => 1, 'pertanyaan' => 'Pertanyaan ' . $i,
                'opsi_a' => 'A' . $i, 'opsi_b' => 'B' . $i,
                'opsi_c' => 'C' . $i, 'opsi_d' => 'D' . $i,
                'opsi_e' => 'E' . $i, 'kunci' => 'C',
            ];
        }
        $snap = ['format' => 'SLA-SKS-TP-V1',
            'tp' => ['sum_tayang' => 30], 'soal' => $bank];
        $soal = SusunButirUjianService::sumatif($snap);
        self::assertCount(30, $soal);
        self::assertSame(range(1,30), array_column($soal, 'posisi'));
        self::assertNotContains(31, array_column($soal, 'soal_asal_id'));
        foreach ($soal as $item) {
            self::assertContains($item['kunci_acak'], ['A','B','C','D','E']);
            self::assertCount(5, json_decode($item['opsi_acak_json'], true));
        }
        $snap['tp']['sum_tayang'] = 29;
        $this->expectException(\DomainException::class);
        SusunButirUjianService::sumatif($snap);
    }

    public function testEndpointOtorisasiDanAlurUiNyataTerhubung(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = file_get_contents($root . '/app/Config/Routes.php');
        $ctrl = file_get_contents($root . '/app/Controllers/SiswaSumatif.php');
        $svc = file_get_contents($root . '/app/Services/MesinSumatifSiswaService.php');
        $view = file_get_contents($root . '/app/Views/siswa/sumatif_ujian.php');
        $detail = file_get_contents($root . '/app/Views/siswa/rincian_tp.php');
        self::assertIsString($routes);
        self::assertIsString($ctrl);
        self::assertIsString($svc);
        self::assertIsString($view);
        self::assertIsString($detail);
        self::assertStringContainsString('SiswaSumatif::mulai', $routes);
        self::assertStringContainsString("'auth:siswa'", $routes);
        self::assertStringContainsString('private function desktop()', $ctrl);
        self::assertStringContainsString("where('jenis', 'sumatif')", $svc);
        self::assertStringContainsString('KelayakanSumatifSiswaService', $svc);
        self::assertStringContainsString('NilaiTpService::sinkronkan', $svc);
        self::assertStringContainsString('data-ujian-jenis="sumatif"', $view);
        self::assertStringContainsString('Buka Ujian Sumatif / Remedial', $detail);
    }
}
