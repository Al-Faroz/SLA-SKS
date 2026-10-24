<?php

declare(strict_types=1);

use App\Services\PetunjukDiagnostikSiswaService;
use PHPUnit\Framework\TestCase;

final class PetunjukDiagnostikSiswaTest extends TestCase
{
    private static function snapshot(): array
    {
        $soal = [];
        for ($i = 1; $i <= 10; $i++) {
            $soal[] = [
                'id' => $i, 'jenis' => 'diagnostik', 'urutan' => $i,
                'pertanyaan' => 'Soal ' . $i, 'opsi_a' => 'Pilihan A',
                'opsi_b' => 'Pilihan B', 'opsi_c' => 'Pilihan C',
                'opsi_d' => 'Pilihan D', 'opsi_e' => 'Pilihan E',
                'kunci' => 'B', 'aktif' => 1,
            ];
        }
        $soal[] = [
            'id' => 50, 'jenis' => 'sumatif', 'urutan' => 1,
            'pertanyaan' => 'Sumatif', 'opsi_a' => 'A', 'opsi_b' => 'B',
            'opsi_c' => 'C', 'opsi_d' => 'D', 'opsi_e' => 'E',
            'kunci' => 'A', 'aktif' => 1,
        ];
        return ['format' => 'SLA-SKS-TP-V1',
            'tp' => ['id' => 1, 'status' => 'siap', 'diag_tayang' => 10,
                'diag_durasi' => 30], 'materi' => [], 'soal' => $soal];
    }

    public function testBankDiagnostikYangLengkapSiap(): void
    {
        $result = PetunjukDiagnostikSiswaService::periksaBank(self::snapshot());
        self::assertTrue($result['sah']);
        self::assertSame(10, $result['jumlah_bank']);
        self::assertSame(10, $result['jumlah_tayang']);
        self::assertSame(30, $result['durasi_menit']);
    }

    public function testJumlahDiBawahTayangDitolak(): void
    {
        $data = self::snapshot();
        array_shift($data['soal']);
        self::assertFalse(PetunjukDiagnostikSiswaService::periksaBank($data)['sah']);
    }

    public function testOpsiKosongKunciTidakSahDanIdDuplikatDitolak(): void
    {
        $data = self::snapshot();
        $data['soal'][0]['opsi_c'] = '';
        self::assertFalse(PetunjukDiagnostikSiswaService::periksaBank($data)['sah']);
        $data = self::snapshot();
        $data['soal'][0]['kunci'] = 'F';
        self::assertFalse(PetunjukDiagnostikSiswaService::periksaBank($data)['sah']);
        $data = self::snapshot();
        $data['soal'][0]['id'] = 2;
        self::assertFalse(PetunjukDiagnostikSiswaService::periksaBank($data)['sah']);
    }

    public function testDurasiDanJumlahTayangHarusMasukRentang(): void
    {
        $data = self::snapshot();
        $data['tp']['diag_durasi'] = 0;
        self::assertFalse(PetunjukDiagnostikSiswaService::periksaBank($data)['sah']);
        $data = self::snapshot();
        $data['tp']['diag_tayang'] = 0;
        self::assertFalse(PetunjukDiagnostikSiswaService::periksaBank($data)['sah']);
    }

    public function testHalamanTidakMengirimSoalAtauKunciDanTidakMembuatAttempt(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root . '/app/Services/PetunjukDiagnostikSiswaService.php');
        $view = file_get_contents($root . '/app/Views/siswa/diagnostik_petunjuk.php');
        $routes = file_get_contents($root . '/app/Config/Routes.php');
        self::assertIsString($service);
        self::assertIsString($view);
        self::assertIsString($routes);
        self::assertStringContainsString("ProgresTpSiswaService::validasiSnapshot", $service);
        self::assertStringContainsString("->rincianTp(\$tpId)", $service);
        self::assertStringNotContainsString("->table('percobaan_ujian')", $service);
        self::assertStringNotContainsString("->table('jawaban_percobaan')", $service);
        self::assertStringNotContainsString("->insert(", $service);
        self::assertStringNotContainsString("['soal']", $view);
        self::assertStringNotContainsString("['kunci']", $view);
        self::assertStringContainsString("Uji fullscreen perangkat", $view);
        self::assertStringContainsString("Pelaksanaan Diagnostik belum diaktifkan", $view);
        self::assertStringContainsString("SiswaDiagnostik::petunjuk", $routes);
        self::assertStringContainsString("'auth:siswa'", $routes);
    }

    public function testJavascriptMencobaFullscreenTapiTidakMengirimUjian(): void
    {
        $js = file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/diagnostik-preflight.js');
        self::assertIsString($js);
        self::assertStringContainsString('requestFullscreen()', $js);
        self::assertStringContainsString('document.fullscreenEnabled', $js);
        self::assertStringContainsString('document.fullscreenElement', $js);
        self::assertStringContainsString('document.exitFullscreen()', $js);
        self::assertStringNotContainsString('fetch(', $js);
        self::assertStringNotContainsString('XMLHttpRequest', $js);
    }
}
