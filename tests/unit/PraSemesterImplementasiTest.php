<?php

declare(strict_types=1);

use App\Services\AturanPraSemesterService;
use App\Services\AturanWaktuUjianService;
use App\Services\PraSemesterGuruService;
use App\Services\PraSemesterJadwalService;
use App\Services\SusunButirUjianService;
use PHPUnit\Framework\TestCase;

/** Regresi kontrak Pra-Semester sebelum uji database XAMPP. */
final class PraSemesterImplementasiTest extends TestCase
{
    private function bank(): array
    {
        $r=[];
        for ($i=1;$i<=45;$i++) {
            $r[]=[
                'id'=>$i,'bab_id'=>$i%3+1,'aktif'=>1,
                'pertanyaan'=>'Pertanyaan '.$i,
                'opsi_a'=>'Opsi A '.$i,'opsi_b'=>'Opsi B '.$i,'opsi_c'=>'Opsi C '.$i,
                'opsi_d'=>'Opsi D '.$i,'opsi_e'=>'Opsi E '.$i,'kunci'=>'C',
            ];
        }
        return $r;
    }

    public function testPraSemesterMemilihTigaBabDanMengacakPilihanTanpaBocorKunci(): void
    {
        $pilih = SusunButirUjianService::praSemester($this->bank(),[1,2,3],30);
        self::assertCount(30,$pilih);
        self::assertSame(range(1,30),array_column($pilih,'posisi'));
        self::assertCount(30,array_unique(array_column($pilih,'soal_pra_semester_id')));
        foreach ($pilih as $row) {
            self::assertNull($row['soal_asal_id']);
            self::assertContains($row['kunci_acak'],['A','B','C','D','E']);
            self::assertCount(5,json_decode($row['opsi_acak_json'],true));
            self::assertNull($row['konten_terstruktur_json']);
        }
        $bank = $this->bank();
        $bab = [];
        foreach ($pilih as $row) $bab[]=$bank[(int)$row['soal_pra_semester_id']-1]['bab_id'];
        self::assertSame([1,2,3],array_values(array_unique((function(array $x): array { sort($x); return $x; })($bab))));
    }

    public function testPraSemesterTolakBankKurangDanBabBelumTerwakili(): void
    {
        $this->expectException(\DomainException::class);
        SusunButirUjianService::praSemester(array_slice($this->bank(),0,20),[1,2,3],30);
    }

    public function testFormAdminMemisahkanJendelaDanDurasi(): void
    {
        $a = PraSemesterJadwalService::validasiForm(
            '2026-11-10T08:00','2026-11-10T09:00','60');
        $b = PraSemesterJadwalService::validasiForm(
            '2026-11-11T07:30','2026-11-11T09:00','90');
        self::assertSame('2026-11-10 08:00:00',$a['mulai']);
        self::assertSame('2026-11-10 09:00:00',$a['selesai']);
        self::assertSame(60,$a['durasi']);
        self::assertSame(90,$b['durasi']);
        foreach ([['2026-11-10T08:00','2026-11-10T08:00','60'],
            ['2026-11-10T08:00','2026-11-10T09:00','0'],
            ['2026-02-31T08:00','2026-11-11T09:00','60']] as $bad) {
            try {
                PraSemesterJadwalService::validasiForm(...$bad);
                self::fail('Jadwal tidak sah seharusnya ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('',$e->getMessage());
            }
        }
    }

    public function testDurasiDihitungDariMulaiSiswaDanTidakDipotongPenutupanJendela(): void
    {
        $mulai=(new \DateTimeImmutable('2026-11-10 08:45:00',
            new \DateTimeZone('Asia/Jakarta')))->getTimestamp();
        $jendela = AturanPraSemesterService::jadwal(
            '2026-11-10 08:00:00','2026-11-10 09:00:00',$mulai
        );
        self::assertTrue($jendela['boleh_mulai']);
        $state = AturanWaktuUjianService::awal(3600,$mulai);
        for($i=10;$i<=3600;$i+=10) {
            $state=AturanWaktuUjianService::heartbeat($state,$mulai+$i);
        }
        self::assertSame(0,$state['sisa_detik']);
        self::assertTrue($state['harus_finalisasi']);
        self::assertSame('ditutup',AturanPraSemesterService::jadwal(
            '2026-11-10 08:00:00','2026-11-10 09:00:00',$mulai+900
        )['status']);
        self::assertFalse(AturanPraSemesterService::jadwal(
            '2026-11-10 08:00:00','2026-11-10 09:00:00',
            (new \DateTimeImmutable('2026-11-10 09:00:00',new \DateTimeZone('Asia/Jakarta')))
                ->getTimestamp()
        )['boleh_mulai']);
    }

    public function testValidasiBankManualMenolakBabLuarDanOpsiKosong(): void
    {
        $data=['bab_id'=>'2','kunci'=>'C','pertanyaan'=>'Contoh',
            'opsi_a'=>'A','opsi_b'=>'B','opsi_c'=>'C','opsi_d'=>'D','opsi_e'=>'E'];
        self::assertSame(2,PraSemesterGuruService::validasiSoal($data)['bab_id']);
        $data['opsi_a']=' ';
        $this->expectException(\DomainException::class);
        PraSemesterGuruService::validasiSoal($data);
    }

    public function testRuteDanTombolNyataTerhubungKeServiceDanUjian(): void
    {
        $root=dirname(__DIR__,2);
        $r=(string)file_get_contents($root.'/app/Config/Routes.php');
        $bank=(string)file_get_contents($root.'/app/Views/guru/pra_semester.php');
        $bankDetail=(string)file_get_contents($root.'/app/Views/guru/pra_semester_detail.php');
        $jadwal=(string)file_get_contents($root.'/app/Views/admin/pra_semester.php');
        $jadwalDetail=(string)file_get_contents($root.'/app/Views/admin/pra_semester_detail.php');
        $guruCtrl=(string)file_get_contents($root.'/app/Controllers/GuruPraSemester.php');
        $adminCtrl=(string)file_get_contents($root.'/app/Controllers/AdminPraSemester.php');
        $ujian=(string)file_get_contents($root.'/app/Views/siswa/pra_semester_ujian.php');
        $js=(string)file_get_contents($root.'/public/assets/js/diagnostik-ujian.js');
        self::assertStringContainsString('GuruPraSemester::simpanSoal',$r);
        self::assertStringContainsString('AdminPraSemester::simpan',$r);
        self::assertStringContainsString('SiswaPraSemester::mulai',$r);
        self::assertStringContainsString('SiswaPraSemester::jawab',$r);
        // Indeks hanya daftar+filter. Form hanya ada di URL detail paket.
        self::assertStringContainsString('name="gen"',$bank);
        self::assertStringContainsString('Kelola soal',$bank);
        self::assertStringNotContainsString('Simpan Soal',$bank);
        self::assertStringNotContainsString('KKM khusus Guru',$bank);
        self::assertStringContainsString('Simpan Soal',$bankDetail);
        self::assertStringContainsString('Kembali ke daftar',$bankDetail);
        self::assertStringNotContainsString('Pilih Gen dan Semester',$bankDetail);
        self::assertStringContainsString("'guru/pra_semester'", $guruCtrl);
        self::assertStringContainsString("'guru/pra_semester_detail'", $guruCtrl);

        foreach (['gen','mapel','semester','status'] as $namaFilter) {
            self::assertStringContainsString('name="'.$namaFilter.'"',$jadwal);
        }
        self::assertStringContainsString('Atur jadwal',$jadwal);
        self::assertStringNotContainsString('Simpan Jadwal Utama',$jadwal);
        self::assertStringNotContainsString('Tambah jadwal remedial atau susulan',$jadwal);
        self::assertStringContainsString('Simpan Jadwal Utama',$jadwalDetail);
        self::assertStringContainsString('Tambah jadwal remedial atau susulan',$jadwalDetail);
        self::assertStringContainsString('Kembali ke daftar',$jadwalDetail);
        self::assertStringNotContainsString('Daftar Pra-Semester</h2>',$jadwalDetail);
        self::assertStringContainsString("'admin/pra_semester_detail'", $adminCtrl);
        self::assertStringContainsString('redirect()->to($url)',$adminCtrl);
        self::assertStringContainsString('data-ujian-jenis="pra_semester"',$ujian);
        self::assertStringContainsString('if (!$desktop)', $ujian); // jadwal HP boleh dibaca
        $siswaCtrl=(string)file_get_contents($root.'/app/Controllers/SiswaPraSemester.php');
        self::assertStringContainsString("'desktop'=>\$this->desktop()", $siswaCtrl);
        self::assertStringContainsString("jenis === 'pra_semester'",$js);
    }
}
