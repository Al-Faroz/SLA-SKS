<?php

declare(strict_types=1);

use App\Services\AturanAktivitasMateriService as Media;
use PHPUnit\Framework\TestCase;

final class AturanAktivitasMateriTest extends TestCase
{
    public function testGambarMembutuhkanWaktuServerMinimalTigaPuluhDetik(): void
    {
        $s = Media::awal('gambar', 1000, null, null);
        self::assertSame(0, $s['waktu_aktif_detik']);
        $s = Media::detak($s, 'gambar', 1010, null, null, 10);
        $s = Media::detak($s, 'gambar', 1020, null, null, 10);
        self::assertSame('berjalan', $s['status']);
        $s = Media::detak($s, 'gambar', 1030, null, null, 10);
        self::assertSame('media_selesai', $s['status']);
        self::assertSame(30, $s['waktu_aktif_detik']);
    }

    public function testWaktuMateriGuruDiAtasBawaanTetapDihormati(): void
    {
        $s = Media::awal('gambar', 1000, null, null);
        for ($i = 1; $i <= 4; ++$i) {
            $s = Media::detak($s, 'gambar', 1000 + $i * 10, null, null, 50);
        }
        self::assertSame('berjalan', $s['status']);
        $s = Media::detak($s, 'gambar', 1050, null, null, 50);
        self::assertSame('media_selesai', $s['status']);
    }

    public function testHeartbeatDiBawahLimaDetikTidakMendapatKredit(): void
    {
        $s = Media::awal('gambar', 1000, null, null);
        $s = Media::detak($s, 'gambar', 1004, null, null, 30);
        self::assertSame(0, $s['waktu_aktif_detik']);
    }

    public function testTabLamaTidakMenambahDetikTersembunyi(): void
    {
        $s = Media::awal('gambar', 1000, null, null);
        $s = Media::detak($s, 'gambar', 1500, null, null, 30);
        self::assertSame(0, $s['waktu_aktif_detik']);
        $s = Media::detak($s, 'gambar', 1510, null, null, 30);
        self::assertSame(10, $s['waktu_aktif_detik']);
    }

    public function testPemutaranAudioMemerlukanSembilanPuluhPersenDanWaktu(): void
    {
        $s = Media::awal('audio', 1000, 0, 100);
        for ($i = 1; $i <= 9; $i++) {
            $s = Media::detak($s, 'audio', 1000 + $i * 10, $i * 10, 100, 50);
        }
        self::assertSame('media_selesai', $s['status']);
        self::assertSame(90, $s['waktu_putar_detik']);
        self::assertSame(90, $s['posisi_terjauh_detik']);
    }

    public function testYoutube360DetikTetapBerjalanJikaVideonyaPanjang(): void
    {
        $s = Media::awal('youtube', 1000, 0, 3600);
        // Contoh laporan pengguna: 380/360 detik, video berdurasi 3600 detik.
        for ($i = 1; $i <= 38; $i++) {
            $s = Media::detak($s, 'youtube', 1000 + $i * 10,
                $i * 10, 3600, 360);
        }
        self::assertSame(380, $s['waktu_aktif_detik']);
        self::assertSame('berjalan', $s['status']);
        for ($i = 39; $i <= 324; $i++) {
            $s = Media::detak($s, 'youtube', 1000 + $i * 10,
                $i * 10, 3600, 360);
        }
        self::assertSame('media_selesai', $s['status']);
    }

    public function testMelewatiVideoDenganSeekTidakDihitungSebagaiPutar(): void
    {
        $s = Media::awal('youtube', 1000, 0, 120);
        $s = Media::detak($s, 'youtube', 1010, 90, 120, 30);
        self::assertSame(0, $s['waktu_putar_detik']);
        self::assertSame(90, $s['posisi_terjauh_detik']);
        $s = Media::detak($s, 'youtube', 1020, 100, 120, 30);
        self::assertSame(10, $s['waktu_putar_detik']);
        self::assertSame('berjalan', $s['status']);
    }

    public function testPosisiMundurTidakMemberikanKredit(): void
    {
        $s = Media::awal('audio', 1000, 40, 100);
        $s = Media::detak($s, 'audio', 1010, 10, 100, 30);
        self::assertSame(0, $s['waktu_putar_detik']);
    }

    public function testDurasiMediaBerubahDitolak(): void
    {
        $s = Media::awal('audio', 1000, 0, 120);
        $this->expectException(\DomainException::class);
        Media::detak($s, 'audio', 1010, 10, 140, 30);
    }

    public function testMediaSelesaiTidakMendapatWaktuTambahan(): void
    {
        $s = Media::awal('gambar', 1000, null, null);
        $s = Media::detak($s, 'gambar', 1010, null, null, 30);
        $s = Media::detak($s, 'gambar', 1020, null, null, 30);
        $s = Media::detak($s, 'gambar', 1030, null, null, 30);
        self::assertSame($s, Media::detak($s, 'gambar', 1040, null, null, 30));
    }

    public function testPdfTidakBisaDinyatakanSelesaiDariHeartbeatBiasa(): void
    {
        $this->expectException(\DomainException::class);
        Media::awal('pdf', 1000, null, null);
    }

    public function testDurasiAtauPosisiTidakSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Media::awal('audio', 1000, 999, 100);
    }

    public function testWaktuMundurDitolak(): void
    {
        $s = Media::awal('gambar', 1000, null, null);
        $this->expectException(\DomainException::class);
        Media::detak($s, 'gambar', 900, null, null, 30);
    }
}
