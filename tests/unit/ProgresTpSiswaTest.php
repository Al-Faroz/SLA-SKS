<?php

declare(strict_types=1);

use App\Services\ProgresTpSiswaService;
use PHPUnit\Framework\TestCase;

final class ProgresTpSiswaTest extends TestCase
{
    private static function snapshot(int $id = 1): array
    {
        $json = json_encode([
            'format' => 'SLA-SKS-TP-V1',
            'tp' => ['id' => $id, 'status' => 'siap'],
            'materi' => [], 'soal' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return ['id' => 7, 'tp_id' => $id, 'nomor_versi' => 1,
            'snapshot_json' => $json, 'checksum' => hash('sha256', $json)];
    }

    public function testSnapshotSahDanChecksumSesuai(): void
    {
        self::assertTrue(ProgresTpSiswaService::validasiSnapshot(self::snapshot(), 1));
    }

    public function testMenolakSnapshotSalahTp(): void
    {
        self::assertFalse(ProgresTpSiswaService::validasiSnapshot(self::snapshot(), 2));
    }

    public function testMenolakChecksumSalahWalaupunJsonMasihValid(): void
    {
        $data = self::snapshot();
        $data['checksum'] = str_repeat('0', 64);
        self::assertFalse(ProgresTpSiswaService::validasiSnapshot($data, 1));
    }

    public function testMenolakSnapshotYangBelumSiap(): void
    {
        $data = self::snapshot();
        $x = json_decode($data['snapshot_json'], true);
        $x['tp']['status'] = 'draf';
        $data['snapshot_json'] = json_encode($x);
        $data['checksum'] = hash('sha256', $data['snapshot_json']);
        self::assertFalse(ProgresTpSiswaService::validasiSnapshot($data, 1));
    }

    public function testHanyaStatusKontrakDiakui(): void
    {
        foreach (['belum_mulai','diagnostik','belajar','sumatif','perlu_bantuan','tuntas'] as $status) {
            self::assertTrue(ProgresTpSiswaService::statusDiizinkan($status));
        }
        self::assertFalse(ProgresTpSiswaService::statusDiizinkan('selesai_dengan_uji_coba'));
    }

    public function testCSRFFormDanRoutePostAdaTanpaTombolUjianPalsu(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root . '/app/Views/siswa/rincian_tp.php');
        $route = file_get_contents($root . '/app/Config/Routes.php');
        $service = file_get_contents($root . '/app/Services/ProgresTpSiswaService.php');
        self::assertIsString($view);
        self::assertIsString($route);
        self::assertIsString($service);
        self::assertStringContainsString("csrf_field()", $view);
        self::assertStringContainsString("'/mulai'", $view);
        self::assertStringContainsString('Aktifkan TP &amp; simpan versi', $view);
        self::assertStringContainsString('Tes Diagnostik berjalan melalui ruang ujian khusus', $view);
        self::assertStringContainsString('Buka Materi', $view);
        self::assertStringContainsString("SiswaPembelajaran::mulaiTp", $route);
        self::assertStringContainsString("'auth:siswa'", $route);
        self::assertStringContainsString('FOR UPDATE', $service);
        self::assertStringContainsString('->transRollback()', $service);
        self::assertStringContainsString('->transCommit()', $service);
    }
}
