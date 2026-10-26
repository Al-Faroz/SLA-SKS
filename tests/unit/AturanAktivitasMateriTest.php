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

    public function testGambarKembaliTerlihatTidakMenghitungJedaTakTerlihat(): void
    {
        $s=Media::awal('gambar', 1000, null, null);
        $s=Media::detak($s,'gambar',1005,null,null,30);
        self::assertSame(5,$s['waktu_aktif_detik']);
        // Gambar di-scroll keluar layar setelah detak lalu kembali;
        // durasi selama tersembunyi tidak boleh ditambahkan.
        $s=Media::detak($s,'gambar',1010,null,null,30,true);
        self::assertSame(5,$s['waktu_aktif_detik']);
        $s=Media::detak($s,'gambar',1015,null,null,30);
        self::assertSame(10,$s['waktu_aktif_detik']);
        self::assertSame('berjalan',$s['status']);
    }

    public function testGambarTuntasPresisiPadaTigaPuluhDetikDenganDetakLimaDetik(): void
    {
        $s=Media::awal('gambar',1000,null,null);
        for ($i=1;$i<=5;$i++) {
            $s=Media::detak($s,'gambar',1000+5*$i,null,null,30);
        }
        self::assertSame(25,$s['waktu_aktif_detik']);
        self::assertSame('berjalan',$s['status']);
        $s=Media::detak($s,'gambar',1030,null,null,30);
        self::assertSame(30,$s['waktu_aktif_detik']);
        self::assertSame('media_selesai',$s['status']);
    }

    public function testAudioMengikutiBatasYangLebihCepat(): void
    {
        $s = Media::awal('audio', 1000, 0, 100);
        for ($i = 1; $i <= 4; $i++) {
            $s = Media::detak($s, 'audio', 1000 + $i * 10, $i * 10, 100, 50);
        }
        self::assertSame('berjalan', $s['status']);
        $s = Media::detak($s, 'audio', 1050, 50, 100, 50);
        self::assertSame('media_selesai', $s['status']);
        self::assertSame(50, $s['waktu_putar_detik']);
        self::assertSame(50, $s['posisi_terjauh_detik']);

        $s2 = Media::awal('audio', 2000, 0, 100);
        for ($i = 1; $i <= 9; $i++) {
            $s2 = Media::detak($s2, 'audio', 2000 + $i * 10, $i * 10, 100, 150);
        }
        self::assertSame('media_selesai', $s2['status']);
        self::assertSame(90, $s2['waktu_putar_detik']);
    }

    public function testVideoPanjangBerhentiPadaBatasGuruLebihDulu(): void
    {
        self::assertSame(600, Media::batasEfektif(600, 900));
        self::assertSame(360, Media::batasEfektif(360, 9968));
        $s = Media::awal('youtube', 1000, 0, 3600);
        for ($i = 1; $i <= 35; $i++) {
            $s = Media::detak($s, 'youtube', 1000 + $i * 10, $i * 10, 3600, 360);
        }
        self::assertSame('berjalan', $s['status']);
        $s = Media::detak($s, 'youtube', 1360, 360, 3600, 360);
        self::assertSame('media_selesai', $s['status']);
        self::assertSame(360, $s['waktu_putar_detik']);
        self::assertSame(360, $s['posisi_terjauh_detik']);
    }

    public function testVideoPendekBerhentiPadaSembilanPuluhPersenLebihDulu(): void
    {
        self::assertSame(378, Media::batasEfektif(600, 420));
        $s = Media::awal('youtube', 1000, 0, 420);
        for ($i = 1; $i <= 37; $i++) {
            $s = Media::detak($s, 'youtube', 1000 + $i * 10, $i * 10, 420, 600);
        }
        self::assertSame('berjalan', $s['status']);
        $s = Media::detak($s, 'youtube', 1380, 380, 420, 600);
        self::assertSame('media_selesai', $s['status']);
        self::assertSame(380, $s['waktu_putar_detik']);
    }

    public function testSeekTidakMemenuhiBatasMeskipunPosisiJauh(): void
    {
        $s = Media::awal('youtube', 1000, 0, 900);
        $s = Media::detak($s, 'youtube', 1010, 750, 900, 600);
        self::assertSame(750, $s['posisi_terjauh_detik']);
        self::assertSame(0, $s['waktu_putar_detik']);
        self::assertSame('berjalan', $s['status']);
        self::assertFalse(Media::memenuhiPemutaran($s, 600, 900));
    }

    public function testGuruDanDurasiTidakSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Media::batasEfektif(600, 0);
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
