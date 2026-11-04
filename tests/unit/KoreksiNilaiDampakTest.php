<?php

declare(strict_types=1);

use App\Services\KoreksiNilaiDampakService;
use PHPUnit\Framework\TestCase;

/** A14-2: perhitungan murni tanpa DB, tanpa approval atau perubahan nilai. */
final class KoreksiNilaiDampakTest extends TestCase
{
    public function testKoreksiResumeJalurBelajarMemakaiBobotResmiDanMembutuhkanTinjauanSertifikat(): void
    {
        // 20% resume 60 + 20% HOTS 70 + 60% Sumatif terbaik 80 = 74.
        $hasil=KoreksiNilaiDampakService::pratinjau(
            'resume',60,90,'belajar_wajib',60.0,70.0,80.0,'final',74.0
        );
        self::assertSame(74.0,$hasil['nilai_tp_lama']);
        self::assertSame(80.0,$hasil['nilai_tp_usulan']);
        self::assertTrue($hasil['perubahan_sumber']);
        self::assertTrue($hasil['perubahan_nilai_tp']);
        self::assertTrue($hasil['perlu_tinjau_sertifikat']);
        self::assertSame('final',$hasil['status_nilai_usulan']);
        self::assertSame('resume',$hasil['komponen']);
    }

    public function testKoreksiHotsNilaiNolTetapSahBukanNull(): void
    {
        $hasil=KoreksiNilaiDampakService::pratinjau(
            'hots',0,50,'belajar_wajib',70.0,0.0,70.0,'final',56.0
        );
        self::assertSame(0,$hasil['nilai_sumber_lama']);
        self::assertSame(56.0,$hasil['nilai_tp_lama']);
        self::assertSame(66.0,$hasil['nilai_tp_usulan']);
        self::assertTrue($hasil['perubahan_nilai_tp']);
    }

    public function testPerubahanPadaNilaiTpSementaraTidakMenciptakanNilaiFinal(): void
    {
        $hasil=KoreksiNilaiDampakService::pratinjau(
            'resume',0,100,'belajar_wajib',0.0,null,65.0,'sementara',null
        );
        self::assertNull($hasil['nilai_tp_lama']);
        self::assertNull($hasil['nilai_tp_usulan']);
        self::assertSame('sementara',$hasil['status_nilai_usulan']);
        self::assertTrue($hasil['perubahan_sumber']);
        self::assertFalse($hasil['perubahan_nilai_tp']);
        self::assertFalse($hasil['perlu_tinjau_sertifikat']);
    }

    public function testTidakAdaPerubahanSkorTidakMembutuhkanRevisiNilai(): void
    {
        $hasil=KoreksiNilaiDampakService::pratinjau(
            'hots',70,70,'belajar_wajib',60.0,70.0,80.0,'final',74.0
        );
        self::assertFalse($hasil['perubahan_sumber']);
        self::assertFalse($hasil['perubahan_nilai_tp']);
        self::assertFalse($hasil['perlu_tinjau_sertifikat']);
    }

    public function testNilaiAwalAtauStatusYangBergeserDitolak(): void
    {
        $kasus=[
            ['resume',50,90,'belajar_wajib',60.0,70.0,80.0,'final',74.0],
            ['hots',70,80,'belajar_wajib',60.0,70.0,80.0,'final',72.0],
            ['resume',60,90,'belajar_wajib',60.0,70.0,80.0,'sementara',null],
            ['resume',60,90,'belajar_wajib',60.0,70.0,80.0,'final',null],
            ['resume',60,90,'belajar_wajib',60.0,70.0,80.0,'final',INF],
            ['resume',60,90,'belajar_wajib',60.0,null,80.0,'sementara',74.0],
        ];
        foreach ($kasus as $input) {
            try {
                KoreksiNilaiDampakService::pratinjau(...$input);
                self::fail('Data awal berubah/tidak konsisten harus ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('',$e->getMessage());
            }
        }
    }

    public function testJalurYangBukanKomponenGuruTidakBolehDiubahLewatPratinjauIni(): void
    {
        $kasus=[
            ['sumatif',70,80,'belajar_wajib',70.0,60.0,80.0,'final',74.0],
            ['diagnostik',70,80,'belajar_wajib',70.0,60.0,80.0,'final',74.0],
            ['resume',70,80,'lulus_diagnostik',70.0,60.0,80.0,'final',80.0],
            ['hots',70,101,'belajar_wajib',60.0,70.0,80.0,'final',74.0],
            ['resume',-1,20,'belajar_wajib',-1.0,70.0,80.0,'final',61.8],
        ];
        foreach ($kasus as $input) {
            try {
                KoreksiNilaiDampakService::pratinjau(...$input);
                self::fail('Input di luar domain Resume/HOTS harus ditolak.');
            } catch (\DomainException $e) {
                self::assertNotSame('',$e->getMessage());
            }
        }
    }

    public function testKodePenilaianTidakMengubahNilaiSetelahTersimpanTanpaProsedurResmi(): void
    {
        $root=dirname(__DIR__,2).'/';
        $penilaian=file_get_contents($root.'app/Services/PenilaianGuruService.php');
        $agregat=file_get_contents($root.'app/Services/NilaiTpService.php');
        self::assertIsString($penilaian);
        self::assertIsString($agregat);
        self::assertStringContainsString('Sudah dinilai. Gunakan jalur koreksi nilai.',$penilaian);
        self::assertStringContainsString('Nilai TP final sudah terkunci. Gunakan prosedur koreksi nilai.',$agregat);
        // Pratinjau ini bukan bypass: tidak menambah route/admin approval palsu.
        $preview=file_get_contents($root.'app/Services/KoreksiNilaiDampakService.php');
        self::assertIsString($preview);
        self::assertStringNotContainsString('->update(',$preview);
        self::assertStringNotContainsString('->insert(',$preview);
        self::assertStringNotContainsString('NotifikasiService::catat',$preview);
    }
}
