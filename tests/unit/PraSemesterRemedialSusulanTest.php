<?php

declare(strict_types=1);

use App\Services\AturanJadwalPraSemesterService as Jadwal;
use App\Services\PenilaianPraSemesterService as Nilai;
use PHPUnit\Framework\TestCase;

/**
 * A8 kebijakan pasca-ujian: semua data anonim, tanpa koneksi/INSERT SQL.
 * Sumber data bersifat urutan percobaan satu siswa dan satu paket ujian.
 */
final class PraSemesterRemedialSusulanTest extends TestCase
{
    private function a(int $id, string $tahap, string $status, float|string|null $skor=null, int $kkm=75): array
    {
        return compact('id','tahap','status','skor','kkm');
    }

    private function now(string $date): int
    {
        return (new \DateTimeImmutable($date,new \DateTimeZone('Asia/Jakarta')))->getTimestamp();
    }

    private function slot(
        string $jenis='utama', string $tahap='normal', bool $peserta=true
    ): array {
        return [
            'jenis'=>$jenis, 'tahap'=>$tahap,
            'mulai'=>'2026-11-10 08:00:00',
            'akhir'=>'2026-11-10 09:00:00',
            'peserta_ditetapkan'=>$peserta,
            'durasi_menit'=>90,
        ];
    }

    private function boleh(array $slot,array $attempt, string $time='2026-11-10 08:45:00'): array
    {
        return Jadwal::periksaMulai(
            $slot,$attempt,$this->now($time),true,true,true,true
        );
    }

    public function testUjianUtamaSatuPaketDanDurasiDimulaiPerSiswa(): void
    {
        $r=$this->boleh($this->slot(),[]);
        self::assertTrue($r['boleh_buat']);
        self::assertFalse($r['boleh_lanjut']);
        self::assertSame('belum_mulai',$r['fase']);
        self::assertSame('normal',$r['tahap']);
        self::assertSame(5400,$r['durasi_detik']);
        self::assertSame('berlangsung',$r['status_jadwal']);
        self::assertFalse($this->boleh($this->slot(),[], '2026-11-10 09:00:00')['boleh_buat']);
    }

    public function testUjianNormalBawahKkmNilaiSementaraDanRemedialKhusus(): void
    {
        $a=[$this->a(100,'normal','selesai_sah',50)];
        $r=Nilai::ringkas($a);
        self::assertSame('menunggu_remedial',$r['fase']);
        self::assertSame('remedial',$r['tahap_berikut']);
        self::assertSame(50.0,$r['nilai_terbaik']);
        self::assertSame('sementara',$r['status_nilai']);
        self::assertFalse($this->boleh($this->slot(),$a)['boleh_buat']);
        self::assertFalse($this->boleh($this->slot('susulan','normal'),$a)['boleh_buat']);
        self::assertTrue($this->boleh($this->slot('remedial','remedial'),$a)['boleh_buat']);

        $a[]=$this->a(101,'remedial','selesai_sah',45);
        $t=Nilai::ringkas($a);
        self::assertSame('selesai',$t['fase']);
        self::assertSame('final',$t['status_nilai']);
        self::assertSame(50.0,$t['nilai_terbaik']); // nilai terbaik, bukan terakhir
        self::assertSame(1,$t['jumlah_sah_remedial']);
        self::assertFalse($this->boleh($this->slot('remedial','remedial'),$a)['boleh_buat']);
    }

    public function testNilaiNolSahBukanNullDanRemedialDapatLebihBaik(): void
    {
        $a=[$this->a(1,'normal','selesai_sah','0.00')];
        self::assertSame(0.0,Nilai::ringkas($a)['nilai_terbaik']);
        self::assertSame('sementara',Nilai::ringkas($a)['status_nilai']);
        $a[]=$this->a(2,'remedial','selesai_sah',65);
        self::assertSame(65.0,Nilai::ringkas($a)['nilai_terbaik']);
        self::assertSame('final',Nilai::ringkas($a)['status_nilai']);
    }

    public function testNormalLulusTidakMendapatRemedialAtauSusulan(): void
    {
        $a=[$this->a(1,'normal','selesai_sah',75)];
        $r=Nilai::ringkas($a);
        self::assertSame('selesai',$r['fase']);
        self::assertSame(75.0,$r['nilai_terbaik']);
        self::assertSame('final',$r['status_nilai']);
        self::assertFalse($this->boleh($this->slot('remedial','remedial'),$a)['boleh_buat']);
        self::assertFalse($this->boleh($this->slot('susulan','normal'),$a)['boleh_buat']);
    }

    public function testPembatalanNormalHanyaSusulanDitunjukAdmin(): void
    {
        $a=[$this->a(1,'normal','dibatalkan_menunggu_guru')];
        self::assertSame('butuh_susulan_normal',Nilai::ringkas($a)['fase']);
        self::assertNull(Nilai::ringkas($a)['nilai_terbaik']);
        self::assertFalse($this->boleh($this->slot(),$a)['boleh_buat']);
        self::assertFalse($this->boleh($this->slot('susulan','normal',false),$a)['boleh_buat']);
        self::assertTrue($this->boleh($this->slot('susulan','normal',true),$a)['boleh_buat']);
        $a[]=$this->a(2,'normal','dibatalkan_menunggu_guru');
        self::assertSame(2,Nilai::ringkas($a)['jumlah_batal']);
        self::assertTrue($this->boleh($this->slot('susulan','normal',true),$a)['boleh_buat']);
        $a[]=$this->a(3,'normal','selesai_sah',83);
        self::assertSame('selesai',Nilai::ringkas($a)['fase']);
        self::assertSame(1,Nilai::ringkas($a)['jumlah_sah_normal']);
        self::assertSame(83.0,Nilai::ringkas($a)['nilai_terbaik']);
    }

    public function testTidakHadirBisaSusulanAdminTanpaMenciptakanNilaiNol(): void
    {
        $r=Nilai::ringkas([]);
        self::assertSame('belum_mulai',$r['fase']);
        self::assertNull($r['nilai_terbaik']);
        self::assertNull($r['status_nilai']);
        self::assertFalse($this->boleh($this->slot(),[],'2026-11-10 09:00:00')['boleh_buat']);
        self::assertFalse($this->boleh($this->slot('susulan','normal',false),[])['boleh_buat']);
        self::assertTrue($this->boleh($this->slot('susulan','normal',true),[])['boleh_buat']);
    }

    public function testRemedialDibatalkanSusulanTetapRemedial(): void
    {
        $a=[
            $this->a(1,'normal','selesai_sah',55),
            $this->a(2,'remedial','dibatalkan_menunggu_guru')
        ];
        $r=Nilai::ringkas($a);
        self::assertSame('butuh_susulan_remedial',$r['fase']);
        self::assertSame(55.0,$r['nilai_terbaik']);
        self::assertSame('sementara',$r['status_nilai']);
        self::assertFalse($this->boleh($this->slot('remedial','remedial'),$a)['boleh_buat']);
        self::assertFalse($this->boleh($this->slot('susulan','normal',true),$a)['boleh_buat']);
        self::assertTrue($this->boleh($this->slot('susulan','remedial',true),$a)['boleh_buat']);
        $a[]=$this->a(3,'remedial','dibatalkan_menunggu_guru');
        self::assertTrue($this->boleh($this->slot('susulan','remedial',true),$a)['boleh_buat']);
        $a[]=$this->a(4,'remedial','selesai_sah',77);
        self::assertSame(77.0,Nilai::ringkas($a)['nilai_terbaik']);
        self::assertSame(1,Nilai::ringkas($a)['jumlah_sah_remedial']);
        self::assertSame('final',Nilai::ringkas($a)['status_nilai']);
    }

    public function testSedangUjianBolehLanjutSetelahJendelaDitutupTanpaAttemptBaru(): void
    {
        $a=[$this->a(7,'normal','jeda_koneksi')];
        $r=$this->boleh($this->slot(),$a,'2026-11-10 09:30:00');
        self::assertFalse($r['boleh_buat']);
        self::assertTrue($r['boleh_lanjut']);
        self::assertSame('ditutup',$r['status_jadwal']);
        self::assertSame(7,$r['percobaan_aktif_id']);
        self::assertSame('normal',$r['tahap']);
    }

    public function testGuardKelayakanTpBankPerangkatFullscreen(): void
    {
        $w=$this->slot();
        foreach ([[false,true,true,true],[true,false,true,true],
            [true,true,false,true],[true,true,true,false]] as $args) {
            $r=Jadwal::periksaMulai($w,[],$this->now('2026-11-10 08:30:00'),...$args);
            self::assertFalse($r['boleh_buat']);
            self::assertNotEmpty($r['alasan']);
        }
    }

    public function testTidakBolehGantiKkmDuaAttempt(): void
    {
        $this->expectException(\DomainException::class);
        Nilai::ringkas([
            $this->a(1,'normal','dibatalkan_menunggu_guru',null,75),
            $this->a(2,'normal','selesai_sah',50,80),
        ]);
    }

    public function testTidakBolehMengubahNilaiBatalMenjadiNilaiSah(): void
    {
        $this->expectException(\DomainException::class);
        Nilai::ringkas([$this->a(1,'normal','dibatalkan_menunggu_guru',90)]);
    }

    public function testRemedialTanpaNormalSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Nilai::ringkas([
            $this->a(1,'normal','dibatalkan_menunggu_guru'),
            $this->a(2,'remedial','selesai_sah',60),
        ]);
    }

    public function testDuaNormalSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Nilai::ringkas([
            $this->a(1,'normal','selesai_sah',60),
            $this->a(2,'normal','selesai_sah',80),
        ]);
    }

    public function testRemedialSetelahNormalLulusDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Nilai::ringkas([
            $this->a(1,'normal','selesai_sah',85),
            $this->a(2,'remedial','selesai_sah',95),
        ]);
    }

    public function testJadwalTahapTidakSahDitolak(): void
    {
        $this->expectException(\DomainException::class);
        $this->boleh($this->slot('utama','remedial'),[]);
    }

    public function testSnapshotBersifatStatelessTanpaAksesDatabase(): void
    {
        $r=dirname(__DIR__,2);
        foreach (['AturanJadwalPraSemesterService','PenilaianPraSemesterService'] as $service) {
            $raw=(string)file_get_contents($r.'/app/Services/'.$service.'.php');
            self::assertStringNotContainsString('db_connect(', $raw);
            self::assertStringNotContainsString('INSERT INTO', $raw);
            self::assertStringNotContainsString('->table(', $raw);
        }
    }
}
