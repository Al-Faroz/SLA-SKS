<?php

declare(strict_types=1);

use App\Services\SertifikatPdfService;
use PHPUnit\Framework\TestCase;

/**
 * QA cetak A11-3: PDF sintetis tanpa NISN maupun data siswa sebenarnya.
 * Berkas preview ke cache hanya untuk inspeksi pdfinfo/pdftotext CI.
 */
final class SertifikatTataLetakPdfTest extends TestCase
{
    private static function snapshot(int $totalTp, int $perBab): array
    {
        $list=[];
        for ($i=1;$i<=$totalTp;$i++) {
            $bab=(int)ceil($i/$perBab);
            $list[]=[
                'nomor_bab'=>$bab,
                'nama_bab'=>'Materi Uji Layout Semester dan Pembelajaran',
                'kode_tp'=>'DEMO.8.'.$bab.'.'.$i,
                'nama_tp'=>'Tujuan Pembelajaran Contoh '.$i
                    .' dengan judul akademik yang realistis',
                'jenis_tp'=>$i%2===0?'non_esensial':'esensial',
                'nilai_tp'=>$i%2===0 ? 0 : null,
            ];
        }
        return [
            'nomor'=>'PRATINJAU/2026/UJI-LAYOUT',
            'tanggal'=>'03-11-2026',
            'siswa'=>[
                'nama'=>'SISWA SIMULASI TANPA DATA PRIBADI',
                'nisn'=>'XXXXXXXXXX',
            ],
            'madrasah'=>[
                'nama_madrasah'=>'MADRASAH CONTOH UNTUK UJI LAYOUT',
                'alamat_madrasah'=>'Jalan Contoh Nomor 12, Jawa Timur',
                'kota_penerbitan'=>'Kota Contoh',
                'kepala_madrasah_nama'=>'KEPALA MADRASAH CONTOH',
                'kepala_madrasah_nip'=>'000000000000000000',
            ],
            'paket'=>['nama_mapel'=>'Sejarah Pembelajaran Contoh','semester'=>4],
            'tp'=>$list,
        ];
    }

    public function testContohEmpatTpA4Landscape(): void
    {
        $pdf=(new SertifikatPdfService())->render(
            self::snapshot(4,2),null,null,'',true
        );
        self::assertStringStartsWith('%PDF-',$pdf);
        self::assertGreaterThan(5000,strlen($pdf));
        $path=WRITEPATH.'cache/a11-preview-empat-tp.pdf';
        self::assertNotFalse(file_put_contents($path,$pdf));
    }

    public function testTabelDuaPuluhEmpatTpTidakMenghilangkanBaris(): void
    {
        $pdf=(new SertifikatPdfService())->render(
            self::snapshot(24,4),null,null,'',true
        );
        self::assertStringStartsWith('%PDF-',$pdf);
        self::assertGreaterThan(5000,strlen($pdf));
        $path=WRITEPATH.'cache/a11-preview-duapuluhempat-tp.pdf';
        self::assertNotFalse(file_put_contents($path,$pdf));
    }

    public function testEmpatTpDenganQrDanTtdSintetisSatuHalaman(): void
    {
        $dir=\App\Services\SertifikatAsetService::direktori();
        if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) {
            self::fail('Folder privat untuk uji gambar TTD gagal dibuat.');
        }
        $nama=bin2hex(random_bytes(24));
        $path=$dir.DIRECTORY_SEPARATOR.$nama.'.png';
        $gambar=imagecreatetruecolor(320,120);
        $putih=imagecolorallocate($gambar,255,255,255);
        $hijau=imagecolorallocate($gambar,20,90,64);
        imagefill($gambar,0,0,$putih);
        imageline($gambar,30,80,220,32,$hijau);
        imageline($gambar,60,75,270,66,$hijau);
        imagepng($gambar,$path);
        imagedestroy($gambar);
        try {
            $ttd=[
                'nama_acak'=>$nama,
                'mime'=>'image/png',
                'sha256'=>hash_file('sha256',$path),
            ];
            $data=self::snapshot(4,2);
            $data['siswa']['nisn']='0000000000';
            $pdf=(new SertifikatPdfService())->render(
                $data,null,$ttd,
                'https://example.invalid/verifikasi/'.str_repeat('a',48),
                false
            );
            self::assertStringStartsWith('%PDF-',$pdf);
            self::assertGreaterThan(5000,strlen($pdf));
            self::assertNotFalse(file_put_contents(
                WRITEPATH.'cache/a11-preview-empat-tp-qr-ttd.pdf',$pdf
            ));
        } finally {
            @unlink($path);
        }
    }

    public function testContohDuaTpTemplateBawaanTetapBerfungsi(): void
    {
        $pdf=(new SertifikatPdfService())->pratinjau(null,null);
        self::assertStringStartsWith('%PDF-',$pdf);
    }
}
