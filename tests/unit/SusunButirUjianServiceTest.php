<?php

declare(strict_types=1);

use App\Services\SusunButirUjianService as Butir;
use PHPUnit\Framework\TestCase;

final class SusunButirUjianServiceTest extends TestCase
{
    private static function snapshot(int $count = 16): array
    {
        $soal = [];
        for ($i=1; $i <= $count; ++$i) {
            $soal[] = [
                'id'=>$i, 'urutan'=>$i,'jenis'=>'diagnostik', 'aktif'=>1,
                'pertanyaan'=>'Pertanyaan '.$i,
                'opsi_a'=>'A-'.$i,'opsi_b'=>'B-'.$i,'opsi_c'=>'C-'.$i,
                'opsi_d'=>'D-'.$i,'opsi_e'=>'E-'.$i,'kunci'=>'C',
                'konten_terstruktur_json'=>json_encode([
                    'html_pertanyaan'=>'<p>Pertanyaan '.$i.'</p>',
                    'html_opsi'=>[
                        'A'=>'<p>A-'.$i.'</p>','B'=>'<p>B-'.$i.'</p>',
                        'C'=>'<p>C-'.$i.'</p>','D'=>'<p>D-'.$i.'</p>','E'=>'<p>E-'.$i.'</p>',
                    ],
                    'word_source_id'=>null,
                ]),
            ];
        }
        // Sumatif tidak pernah bercampur ke butir Diagnostik.
        $soal[] = ['id'=>900,'jenis'=>'sumatif','aktif'=>1];
        return ['format'=>'SLA-SKS-TP-V1',
            'tp'=>['id'=>1,'status'=>'siap','diag_tayang'=>10],
            'soal'=>$soal];
    }

    public function testTepatSepuluhSoalUnikDenganPilihanDanKunciTeracak(): void
    {
        $hasil=Butir::diagnostik(self::snapshot());
        self::assertCount(10,$hasil);
        self::assertCount(10,array_unique(array_column($hasil,'soal_asal_id')));
        self::assertSame(range(1,10),array_column($hasil,'posisi'));
        foreach ($hasil as $row) {
            self::assertNotSame(900,$row['soal_asal_id']);
            $id=$row['soal_asal_id'];
            $opsi=json_decode($row['opsi_acak_json'],true);
            self::assertSame(['A','B','C','D','E'],array_keys($opsi));
            self::assertEqualsCanonicalizing(
                ['A-'.$id,'B-'.$id,'C-'.$id,'D-'.$id,'E-'.$id], array_values($opsi)
            );
            self::assertSame('C-'.$id,$opsi[$row['kunci_acak']]);
            $rich=json_decode($row['konten_terstruktur_json'],true);
            foreach ($opsi as $huruf=>$teks) {
                self::assertSame('<p>'.$teks.'</p>',$rich['html_opsi'][$huruf]);
            }
        }
    }

    public function testOutputSiswaTidakMemuatKunciDanAsalSoal(): void
    {
        $item=Butir::diagnostik(self::snapshot())[0];
        $item['id']=11;
        $data=Butir::untukSiswa($item);
        self::assertSame(11,$data['id']);
        self::assertArrayNotHasKey('kunci_acak',$data);
        self::assertArrayNotHasKey('soal_asal_id',$data);
        self::assertArrayNotHasKey('word_source_id',$data);
        self::assertArrayHasKey('opsi',$data);
        self::assertArrayHasKey('html_opsi',$data);
        self::assertArrayNotHasKey('konten_terstruktur_json',$data);
    }

    public function testSoalKurangAtauJawabanKosongDitolak(): void
    {
        $this->expectException(\DomainException::class);
        Butir::diagnostik(self::snapshot(9));
    }

    public function testSalahSatuOpsiKosongDitolak(): void
    {
        $data=self::snapshot();
        $data['soal'][0]['opsi_e']='';
        $this->expectException(\DomainException::class);
        Butir::diagnostik($data);
    }

    public function testIdBankGandaDitolak(): void
    {
        $data=self::snapshot();
        $data['soal'][0]['id']=2;
        $this->expectException(\DomainException::class);
        Butir::diagnostik($data);
    }

    public function testPenilaianSahMenggunakanKunciOpsiTeracak(): void
    {
        $butir=Butir::diagnostik(self::snapshot(10));
        foreach ($butir as $i=>&$row) {
            $row['id']=$i+1;
        }
        unset($row);
        $answers=[];
        foreach ($butir as $row) {
            $answers[$row['id']]=$row['kunci_acak'];
        }
        $hasil=Butir::hitung($butir,$answers);
        self::assertSame(10,$hasil['jumlah_benar']);
        self::assertSame(100.0,$hasil['skor']);
        $answers[1]=null;
        $hasil=Butir::hitung($butir,$answers);
        self::assertSame(9,$hasil['jumlah_benar']);
        self::assertSame(90.0,$hasil['skor']);
    }

    public function testPilihanYangTidakDikenalDitolak(): void
    {
        $butir=Butir::diagnostik(self::snapshot(10));
        $butir[0]['id']=1;
        $this->expectException(\DomainException::class);
        Butir::hitung($butir,[1=>'F']);
    }
}
