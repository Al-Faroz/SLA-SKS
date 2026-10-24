<?php

declare(strict_types=1);

use App\Services\AturanWaktuUjianService as Waktu;
use PHPUnit\Framework\TestCase;

final class AturanWaktuUjianServiceTest extends TestCase
{
    public function testAwalDanHeartbeatMenghitungWaktuAktifServer(): void
    {
        $s = Waktu::awal(1800, 1000);
        self::assertTrue(Waktu::bolehMenjawab($s));
        $s = Waktu::heartbeat($s, 1010);
        self::assertSame('berjalan', $s['status']);
        self::assertSame(1790, $s['sisa_detik']);
        self::assertSame(1010, $s['last_seen_server_at']);
        $s = Waktu::heartbeat($s, 1020);
        self::assertSame(1780, $s['sisa_detik']);
    }

    public function testTimeoutKoneksiMenghitungDuaPuluhDetikAwalSaja(): void
    {
        $s = Waktu::rekonsiliasi(Waktu::awal(300, 1000), 1045);
        self::assertSame('jeda_koneksi', $s['status']);
        self::assertSame(280, $s['sisa_detik']);
        self::assertSame(1020, $s['jeda_mulai_pada']);
        self::assertFalse(Waktu::bolehMenjawab($s));
        $s = Waktu::pulih($s, 1050);
        self::assertSame('berjalan', $s['status']);
        self::assertSame(30, $s['jeda_koneksi_total_detik']);
        self::assertSame(280, $s['sisa_detik']);
    }

    public function testFullscreenDanKoneksiTidakDihitungGanda(): void
    {
        $s = Waktu::keluarFullscreen(Waktu::awal(300, 1000), 1010);
        self::assertSame('jeda_fullscreen', $s['status']);
        self::assertSame(290, $s['sisa_detik']);
        $s = Waktu::rekonsiliasi($s, 1060);
        self::assertSame('jeda_fullscreen', $s['status']);
        self::assertSame(0, $s['jeda_koneksi_total_detik']);
        $s = Waktu::pulih($s, 1060);
        self::assertSame(50, $s['jeda_fullscreen_total_detik']);
        self::assertSame(0, $s['jeda_koneksi_total_detik']);
        self::assertSame(290, $s['sisa_detik']);
    }

    public function testFullscreenSembilanPuluhDetikDibatalkan(): void
    {
        $s = Waktu::keluarFullscreen(Waktu::awal(300, 1000), 1005);
        $s = Waktu::rekonsiliasi($s, 1095);
        self::assertSame('dibatalkan_menunggu_guru', $s['status']);
        self::assertNull($s['skor'] ?? null);
        self::assertFalse(Waktu::bolehMenjawab($s));
    }

    public function testAkumulasiFullscreenSeratusDelapanPuluhDetikDibatalkan(): void
    {
        $s = Waktu::awal(600, 1000);
        $s = Waktu::keluarFullscreen($s, 1010);
        $s = Waktu::pulih($s, 1099); // 89 detik
        $s = Waktu::keluarFullscreen($s, 1100);
        $s = Waktu::pulih($s, 1189); // 178 detik
        $s = Waktu::keluarFullscreen($s, 1190);
        $s = Waktu::rekonsiliasi($s, 1192); // +2 detik
        self::assertSame('dibatalkan_menunggu_guru', $s['status']);
    }

    public function testInsidenBerulangPadaJedaTidakMenghitungDuaPelanggaran(): void
    {
        $s = Waktu::keluarFullscreen(Waktu::awal(600, 1000), 1005);
        $s = Waktu::keluarFullscreen($s, 1005);
        $s = Waktu::keluarFullscreen($s, 1006);
        self::assertSame(1, $s['jumlah_pelanggaran_fullscreen']);
        self::assertSame('jeda_fullscreen', $s['status']);
    }

    public function testPelanggaranKetigaMembatalkanTanpaSkor(): void
    {
        $s = Waktu::awal(600, 1000);
        foreach ([1005, 1015, 1025] as $waktu) {
            $s = Waktu::keluarFullscreen($s, $waktu);
            if ($waktu !== 1025) {
                $s = Waktu::pulih($s, $waktu + 1);
            }
        }
        self::assertSame(3, $s['jumlah_pelanggaran_fullscreen']);
        self::assertSame('dibatalkan_menunggu_guru', $s['status']);
        self::assertNull($s['skor'] ?? null);
    }

    public function testTimeoutKoneksiLimaMenitDibatalkan(): void
    {
        $s = Waktu::rekonsiliasi(Waktu::awal(900, 1000), 1320);
        self::assertSame('dibatalkan_menunggu_guru', $s['status']);
        self::assertFalse(Waktu::bolehMenjawab($s));
    }

    public function testWaktuHabisMenuntutFinalisasiAtomikDanMenolakJawaban(): void
    {
        $s = Waktu::awal(300, 1000);
        for ($i = 1; $i <= 30; $i++) {
            $s = Waktu::heartbeat($s, 1000 + $i * 10);
        }
        self::assertSame('berjalan', $s['status']);
        self::assertTrue($s['harus_finalisasi']);
        self::assertSame(0, $s['sisa_detik']);
        self::assertNull($s['skor'] ?? null);
        self::assertFalse(Waktu::bolehMenjawab($s));
    }

    public function testStatusFinalIdempotenDanWaktuServerMundurDitolak(): void
    {
        $s = Waktu::awal(600, 1000);
        $s = Waktu::keluarFullscreen($s, 1005);
        $s = Waktu::rekonsiliasi($s, 1095);
        self::assertSame($s, Waktu::rekonsiliasi($s, 9999));
        $this->expectException(\DomainException::class);
        Waktu::rekonsiliasi(Waktu::awal(600, 1000), 999);
    }

    public function testDurasiTidakValidDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Waktu::awal(299, 1000);
    }
}
