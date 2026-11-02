<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KoreksiTpService;
use DomainException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class KoreksiTpServiceV25Test extends TestCase
{
    private static function sumber(string $p): string
    {
        $c = file_get_contents(dirname(__DIR__, 2) . '/' . $p);
        self::assertIsString($c);
        return $c;
    }

    public function testRuteKoreksiGuruDanAdminTersedia(): void
    {
        $routes = self::sumber('app/Config/Routes.php');
        foreach (['GuruKoreksiTp::index','GuruKoreksiTp::buat','GuruKoreksiTp::edit',
            'GuruKoreksiTp::rincian','GuruKoreksiTp::soal','GuruKoreksiTp::kirim',
            'AdminKoreksiTp::index','AdminKoreksiTp::detail','AdminKoreksiTp::putuskan',
            'AdminKoreksiTp::media','GuruKoreksiTp::unggahMedia','GuruKoreksiTp::media'] as $target) {
            self::assertStringContainsString($target, $routes);
        }
        $view = self::sumber('app/Views/guru/detail_tp.php');
        self::assertStringContainsString('Ajukan Koreksi TP Siap', $view);
    }

    public function testDDLMenambahTabelTanpaMenghapusData(): void
    {
        $sql = self::sumber('database/sql/updates/V4-0006_KOREKSI_TP_SIAP.sql');
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS', $sql);
        self::assertStringContainsString('uq_koreksi_tp_terbuka', $sql);
        self::assertStringContainsString('checksum_dasar', $sql);
        self::assertStringContainsString("ENUM('A','B','C')", $sql);
        self::assertStringContainsString('V4-0006-KOREKSI-TP-SIAP', $sql);
        self::assertStringNotContainsString('DROP TABLE', $sql);
        self::assertStringNotContainsString('TRUNCATE', $sql);
    }

    public function testSnapshotTakLengkapDitolak(): void
    {
        $method = new ReflectionMethod(KoreksiTpService::class, 'decode');
        $this->expectException(DomainException::class);
        $method->invoke(null, '{"tp":{},"soal":[]}');
    }

    public function testVersiLamaDanSelfApprovalDilindungi(): void
    {
        $src = self::sumber('app/Services/KoreksiTpService.php');
        self::assertStringContainsString('versi_konten_tp', $src);
        self::assertStringContainsString('nomor_versi', $src);
        self::assertStringContainsString('Akun pengusul Guru tidak boleh memutus', $src);
        self::assertStringContainsString('KesiapanTpService::evaluasi', $src);
        self::assertStringContainsString('periksaFileMateri', $src);
        self::assertStringContainsString('mediaAdmin', $src);
        self::assertStringContainsString('perubahanNyata', $src);
        self::assertStringContainsString('transRollback()', $src);
    }

    public function testCPBerstatusAktifTetapBisaDiterbitkanSebagaiVersiBaru(): void
    {
        $index = self::sumber('app/Views/admin/kurikulum_cp.php');
        $cp = self::sumber('app/Services/CpReferensiService.php');
        self::assertStringContainsString('Sunting CP', $index);
        self::assertStringContainsString('Lanjut Sunting CP', $index);
        self::assertStringContainsString('public function versiBaru', $cp);
        self::assertStringContainsString('cp_mapel_elemen', $cp);
        self::assertStringContainsString("'status' => 'diarsipkan'", $cp);
    }
}
