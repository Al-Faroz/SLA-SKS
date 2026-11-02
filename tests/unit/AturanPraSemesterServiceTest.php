<?php

declare(strict_types=1);

use App\Services\AturanPraSemesterService as Aturan;
use PHPUnit\Framework\TestCase;

/** A8-0: fixture kebijakan murni, tidak mengakses DB siswa atau SQL privat. */
final class AturanPraSemesterServiceTest extends TestCase
{
    private function tp(): array
    {
        return [
            ['id'=>10,'jenis_tp'=>'esensial'],
            ['id'=>11,'jenis_tp'=>'non_esensial'],
            ['id'=>12,'jenis_tp'=>'non_esensial'],
        ];
    }

    private function soal(int $jumlah = 30): array
    {
        $hasil = [];
        for ($i=1;$i<=$jumlah;$i++) {
            $hasil[] = [
                'id'=>$i,
                'bab_id'=>$i % 2 === 0 ? 101 : 102,
                'aktif'=>true,'opsi_lengkap'=>true,'kunci_sah'=>true,
            ];
        }
        return $hasil;
    }

    private function now(string $wib): int
    {
        return (new \DateTimeImmutable($wib,
            new \DateTimeZone('Asia/Jakarta')))->getTimestamp();
    }

    public function testTPNonEsensialWajibTuntasTanpaMemintaProgresEsensial(): void
    {
        $a = Aturan::kelayakanTp($this->tp(),[11=>'tuntas',12=>'tuntas']);
        self::assertTrue($a['siap']);
        self::assertSame(2,$a['jumlah_wajib']);
        self::assertSame(2,$a['jumlah_tuntas']);
        self::assertSame([],$a['belum_tuntas']);
        // Nilai < KKM tidak menjadi gerbang; hanya status tuntas.
        self::assertTrue(Aturan::kelayakanTp($this->tp(),[
            10=>'belum_mulai',11=>'tuntas',12=>'tuntas',
        ])['siap']);
    }

    public function testSatuTPBelumTuntasTetapTolakWalaupunTPYangLainSelesai(): void
    {
        $r = Aturan::kelayakanTp($this->tp(),[11=>'tuntas',12=>'sumatif']);
        self::assertFalse($r['siap']);
        self::assertSame([12],$r['belum_tuntas']);
        self::assertSame(1,$r['jumlah_tuntas']);
        self::assertFalse(Aturan::kelayakanTp($this->tp(),[11=>'tuntas'])['siap']);
    }

    public function testPaketBelumPunyaTPNonEsensialTidakOtomatisLolos(): void
    {
        $r = Aturan::kelayakanTp([['id'=>10,'jenis_tp'=>'esensial']],[]);
        self::assertFalse($r['siap']);
        self::assertSame(0,$r['jumlah_wajib']);
        self::assertStringContainsString('Belum ada TP NonEsensial',$r['pesan']);
    }

    public function testTPDuplikatAtauJenisTidakValidDitolak(): void
    {
        foreach ([
            [['id'=>1,'jenis_tp'=>'non_esensial'],['id'=>1,'jenis_tp'=>'esensial']],
            [['id'=>1,'jenis_tp'=>'tidak_dikenal']],
            [['id'=>0,'jenis_tp'=>'non_esensial']],
        ] as $invalid) {
            try {
                Aturan::kelayakanTp($invalid,[]);
                self::fail('TP tidak sah tidak boleh diterima.');
            } catch (\DomainException $e) {
                self::assertNotSame('',$e->getMessage());
            }
        }
    }

    public function testBank30SoalSahMencakupSemuaBAB(): void
    {
        $r = Aturan::kesiapanBank([101,102],$this->soal());
        self::assertTrue($r['siap']);
        self::assertSame(30,$r['jumlah_sah']);
        self::assertSame(2,$r['jumlah_bab_tercakup']);
        self::assertSame([],$r['bab_tanpa_soal']);
    }

    public function testBankKurangSoalAtauBABTidakTerwakiliDitolak(): void
    {
        $kurang = Aturan::kesiapanBank([101,102],$this->soal(29));
        self::assertFalse($kurang['siap']);
        self::assertSame(29,$kurang['jumlah_sah']);
        $hanyaSatu = $this->soal();
        foreach ($hanyaSatu as &$item) $item['bab_id'] = 101;
        unset($item);
        $hasil = Aturan::kesiapanBank([101,102],$hanyaSatu);
        self::assertFalse($hasil['siap']);
        self::assertSame([102],$hasil['bab_tanpa_soal']);
        self::assertSame(30,$hasil['jumlah_sah']);
    }

    public function testSoalNonAktifKunciTidakSahOpsiKurangDanBABAsingTidakDihitung(): void
    {
        $butir = $this->soal(34);
        $butir[0]['aktif']=false;
        $butir[1]['opsi_lengkap']=false;
        $butir[2]['kunci_sah']=false;
        $butir[3]['bab_id']=999;
        $r = Aturan::kesiapanBank([101,102],$butir);
        self::assertTrue($r['siap']);
        self::assertSame(30,$r['jumlah_sah']);
        self::assertSame(4,$r['jumlah_soal_tidak_sah']);
        $butir[4]['aktif']=false;
        self::assertFalse(Aturan::kesiapanBank([101,102],$butir)['siap']);
    }

    public function testBankDuplikatSoalBABDanKosongTidakLolos(): void
    {
        self::assertFalse(Aturan::kesiapanBank([],$this->soal())['siap']);
        foreach ([
            [101,101],
            [0],
        ] as $ids) {
            try {
                Aturan::kesiapanBank($ids,$this->soal());
                self::fail('BAB tidak sah.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('BAB',$e->getMessage());
            }
        }
        $duplikat = $this->soal();
        $duplikat[1]['id']=1;
        $this->expectException(\DomainException::class);
        Aturan::kesiapanBank([101,102],$duplikat);
    }

    public function testJadwalWibSerentakAwalInklusifDanAkhirEksklusif(): void
    {
        $mulai = '2026-11-10 08:00:00';
        $akhir = '2026-11-10 09:30:00';
        $awal = $this->now($mulai);
        $sampai = $this->now($akhir);
        self::assertSame('belum_mulai',Aturan::jadwal($mulai,$akhir,$awal-1)['status']);
        self::assertFalse(Aturan::jadwal($mulai,$akhir,$awal-1)['boleh_mulai']);
        self::assertSame('berlangsung',Aturan::jadwal($mulai,$akhir,$awal)['status']);
        self::assertTrue(Aturan::jadwal($mulai,$akhir,$awal)['boleh_mulai']);
        self::assertTrue(Aturan::jadwal($mulai,$akhir,$sampai-1)['boleh_mulai']);
        self::assertFalse(Aturan::jadwal($mulai,$akhir,$sampai)['boleh_mulai']);
        self::assertSame('ditutup',Aturan::jadwal($mulai,$akhir,$sampai)['status']);
        // WIB 08:00 sama dengan UTC 01:00; tidak memakai jam browser.
        self::assertSame('2026-11-10 01:00:00',gmdate('Y-m-d H:i:s',$awal));
    }

    public function testJadwalTidakAdaDitutupDanTanggalCacatDitolak(): void
    {
        $w = $this->now('2026-11-10 08:30:00');
        $belum = Aturan::jadwal(null,null,$w);
        self::assertSame('belum_dijadwalkan',$belum['status']);
        self::assertFalse($belum['boleh_mulai']);
        foreach ([
            ['2026-11-10 09:30:00','2026-11-10 08:00:00'],
            ['2026-11-10 08:00:00','2026-11-10 08:00:00'],
            ['2026-02-31 08:00:00','2026-11-10 09:00:00'],
            ['2026-11-10T08:00','2026-11-10 09:00:00'],
        ] as [$start,$end]) {
            try {
                Aturan::jadwal($start,$end,$w);
                self::fail('Jadwal salah tidak boleh diloloskan.');
            } catch (\DomainException $e) {
                self::assertNotSame('',$e->getMessage());
            }
        }
    }

    public function testGerbangKombinasiSemuaSyaratDanAlasanTransparan(): void
    {
        $mulai='2026-11-10 08:00:00';
        $akhir='2026-11-10 09:30:00';
        $now=$this->now('2026-11-10 08:30:00');
        $ok = Aturan::gerbangMulai(
            $this->tp(),[11=>'tuntas',12=>'tuntas'],
            [101,102],$this->soal(),$mulai,$akhir,$now
        );
        self::assertTrue($ok['boleh_mulai']);
        self::assertSame([],$ok['alasan']);

        $blocked = Aturan::gerbangMulai(
            $this->tp(),[11=>'tuntas',12=>'belajar'],
            [101,102],$this->soal(20),$mulai,$akhir,$now
        );
        self::assertFalse($blocked['boleh_mulai']);
        self::assertCount(2,$blocked['alasan']);

        $closed = Aturan::gerbangMulai(
            $this->tp(),[11=>'tuntas',12=>'tuntas'],
            [101,102],$this->soal(),$mulai,$akhir,$now+7200
        );
        self::assertFalse($closed['boleh_mulai']);
        self::assertSame('ditutup',$closed['jadwal']['status']);
    }

    public function testFondasiTidakMembuatRouteAtauMemanggilDatabase(): void
    {
        $source = file_get_contents(dirname(__DIR__,2).'/app/Services/AturanPraSemesterService.php');
        self::assertIsString($source);
        self::assertStringNotContainsString('db_connect(',$source);
        self::assertStringNotContainsString('table(',$source);
        self::assertStringNotContainsString('INSERT INTO',$source);
        self::assertStringNotContainsString('CREATE TABLE',$source);
    }
}
