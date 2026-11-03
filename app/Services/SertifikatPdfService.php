<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use DomainException;
use RuntimeException;

/**
 * Renderer PDF A4 landscape. Semua HTML disusun internal dan di-escape.
 * Gambar privat dimasukkan sebagai data URI; tidak ada HTTP fetch / SSRF.
 */
final class SertifikatPdfService
{
    private static function teks(mixed $v): string
    {
        return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }

    private static function imageUri(?string $path,string $mime): string
    {
        if ($path===null) return '';
        $isi=@file_get_contents($path);
        if (!is_string($isi) || strlen($isi)>12000000) {
            throw new RuntimeException('Aset privat sertifikat tidak dapat dibaca.');
        }
        return 'data:'.$mime.';base64,'.base64_encode($isi);
    }

    /** QR hanya berisi URL verifikasi; tidak ada NISN atau identitas siswa. */
    public static function qr(string $url): string
    {
        if (!filter_var($url,FILTER_VALIDATE_URL)) {
            throw new DomainException('URL verifikasi sertifikat tidak valid.');
        }
        $qr = new QrCode(
            data:$url,
            encoding:new Encoding('UTF-8'),
            errorCorrectionLevel:ErrorCorrectionLevel::Medium,
            size:230,
            margin:7
        );
        return (new PngWriter())->write($qr)->getDataUri();
    }

    /**
     * @param array<string,mixed> $snapshot
     * @param array<string,mixed>|null $background
     * @param array<string,mixed>|null $ttd
     */
    public function render(
        array $snapshot,?array $background,?array $ttd,string $urlVerifikasi,
        bool $pratinjau=false
    ): string {
        $siswa=$snapshot['siswa']??[];
        $sekolah=$snapshot['madrasah']??[];
        $paket=$snapshot['paket']??[];
        $tp=$snapshot['tp']??[];
        if (!is_array($siswa)||!is_array($sekolah)||!is_array($paket)||!is_array($tp)) {
            throw new DomainException('Snapshot sertifikat tidak sah.');
        }
        if (!$pratinjau && (empty($siswa['nisn']) || $ttd===null)) {
            throw new DomainException('NISN dan TTD diperlukan untuk sertifikat.');
        }
        $latar='';
        if ($background!==null) {
            $path=SertifikatAsetService::path($background);
            $latar=self::imageUri($path,(string)$background['mime']);
        }
        $tandaTangan='';
        if ($ttd!==null) {
            $path=SertifikatAsetService::path($ttd);
            $tandaTangan=self::imageUri($path,'image/png');
        }
        $qr=$pratinjau ? '' : self::qr($urlVerifikasi);
        $identitas=static fn (string $key): string => self::teks($sekolah[$key]??'');
        $rows='';
        $babLama=null;
        foreach ($tp as $item) {
            if (!is_array($item)) continue;
            $nomor=(string)($item['nomor_bab']??'');
            if ($babLama!==$nomor) {
                $rows.='<tr class="bab"><td colspan="4">BAB '
                    .self::teks($nomor).' — '.self::teks($item['nama_bab']??'')
                    .'</td></tr>';
                $babLama=$nomor;
            }
            $esensial=($item['jenis_tp']??'')==='esensial';
            $nilai=$esensial?'—':(
                ($item['nilai_tp']??null)===null ? '—'
                    : number_format((float)$item['nilai_tp'],2,',','.')
            );
            $rows.='<tr><td>'.self::teks($item['kode_tp']??'')
                .'</td><td>'.self::teks($item['nama_tp']??'')
                .'</td><td>'.($esensial?'Esensial':'NonEsensial')
                .'</td><td class="num">'.self::teks($nilai).'</td></tr>';
        }
        if ($rows==='') $rows='<tr><td colspan="4">Belum ada TP.</td></tr>';
        $backgroundCss=$latar!==''?'<img class="background" src="'.$latar.'" alt="">':'';
        $ttdHtml=$tandaTangan!==''?'<img class="ttd" src="'.$tandaTangan.'" alt="Tanda tangan kepala madrasah">':'<div class="ttd-kosong">Contoh area TTD</div>';
        $qrHtml=$qr!==''?'<img class="qr" src="'.$qr.'" alt="QR verifikasi">':'<div class="qr-kosong">Pratinjau Tanpa QR</div>';
        $watermark=$pratinjau?'<div class="watermark">PRATINJAU — BUKAN SERTIFIKAT TERBIT</div>':'';
        $html='<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><style>
            @page { size:A4 landscape; margin:12mm 14mm 13mm; }
            body { font-family:DejaVu Sans,sans-serif; color:#15382E; font-size:9px; margin:0; }
            .background { position:fixed; top:-12mm; left:-14mm; width:297mm; height:210mm; z-index:-2; }
            .watermark { position:fixed; top:90mm; left:18mm; transform:rotate(-15deg);
                color:rgba(170,30,30,.25); font-size:24px; font-weight:800; z-index:5; }
            .card { background:rgba(255,255,255,.90); border:1px solid #8CBAA7; padding:10mm 11mm; }
            .kop { text-align:center; padding-bottom:6px; border-bottom:2px solid #207957; }
            .kop .sekolah { font-size:15px; font-weight:bold; line-height:1.4; }
            .kop .sub { font-size:8px; color:#365C50; }
            h1 { text-align:center; letter-spacing:2px; font-size:17px; margin:10px 0 3px; }
            .nomor { text-align:center; font-size:10px; margin:0 0 10px; }
            table { width:100%; border-collapse:collapse; }
            .data th { text-align:left; width:23mm; padding:2px; font-weight:normal; }
            .data td { padding:2px; font-weight:bold; }
            .nilai { margin-top:10px; font-size:8.4px; }
            .nilai th { background:#DAEBE3; border:1px solid #C4DED0; padding:5px; text-align:left; }
            .nilai td { border:1px solid #C4DED0; padding:4px; vertical-align:top; }
            .nilai tr.bab td { background:#ECF4EE; font-weight:bold; }
            .nilai .num { text-align:center; width:20mm; white-space:nowrap; }
            .nilai thead { display:table-header-group; }
            .nilai tr { page-break-inside:avoid; }
            .foot { width:100%; margin-top:13px; page-break-inside:avoid; }
            .foot td { vertical-align:top; text-align:center; width:33.3%; }
            .foot .qr { width:27mm; height:27mm; }
            .foot .ttd { height:19mm; max-width:48mm; object-fit:contain; }
            .foot .sign { padding-top:5px; }
            .foot .nama { font-weight:bold; text-decoration:underline; }
            .qr-kosong,.ttd-kosong { border:1px dashed #9CAFA9; padding:12px 4px; }
            .catatan { font-size:7px; color:#4C675F; }
            </style></head><body>'.$backgroundCss.$watermark.
            '<main class="card"><div class="kop"><div class="sekolah">'
            .$identitas('nama_madrasah').'</div><div class="sub">'
            .$identitas('alamat_madrasah').'</div></div>'
            .'<h1>SERTIFIKAT PEMBELAJARAN</h1><p class="nomor">Nomor: '
            .self::teks($snapshot['nomor']??'PRATINJAU').'</p>'
            .'<table class="data"><tr><th>Nama Siswa</th><td>: '
            .self::teks($siswa['nama']??'').'</td><th>NISN</th><td>: '
            .self::teks($siswa['nisn']??'—').'</td></tr>'
            .'<tr><th>Mata Pelajaran</th><td>: '.self::teks($paket['nama_mapel']??'')
            .'</td><th>Semester</th><td>: '.self::teks($paket['semester']??'').'</td></tr>'
            .'</table><table class="nilai"><thead><tr><th>Kode TP</th><th>Tujuan Pembelajaran</th>'
            .'<th>Jenis</th><th>Nilai TP</th></tr></thead><tbody>'.$rows.'</tbody></table>'
            .'<table class="foot"><tr><td>'.$qrHtml
            .'<div class="catatan">Verifikasi keaslian melalui QR</div></td>'
            .'<td><div class="catatan">TP Esensial tanpa nilai.<br>Pra-Semester tidak dicetak.'
            .'<br>Tidak ada Nilai Akhir Semester.</div></td>'
            .'<td><div>'.self::teks($sekolah['kota_penerbitan']??'').', '
            .self::teks($snapshot['tanggal']??'').'</div><div>Kepala Madrasah</div>'
            .$ttdHtml.'<div class="nama">'.self::teks($sekolah['kepala_madrasah_nama']??'')
            .'</div><div>NIP: '.self::teks($sekolah['kepala_madrasah_nip']??'').'</div></td>'
            .'</tr></table></main></body></html>';
        $opt=new Options();
        $opt->set('isRemoteEnabled',false);
        $opt->set('isPhpEnabled',false);
        $opt->set('isJavascriptEnabled',false);
        $opt->set('defaultFont','DejaVu Sans');
        $opt->set('isHtml5ParserEnabled',true);
        $pdf=new Dompdf($opt);
        $pdf->loadHtml($html,'UTF-8');
        $pdf->setPaper('A4','landscape');
        $pdf->render();
        $output=$pdf->output();
        if (!is_string($output) || !str_starts_with($output,'%PDF-')) {
            throw new RuntimeException('PDF sertifikat gagal dibuat.');
        }
        return $output;
    }

    public function pratinjau(?array $background,?array $ttd): string
    {
        $snapshot=[
            'nomor'=>'CONTOH/0000','tanggal'=>date('d-m-Y'),
            'siswa'=>['nama'=>'NAMA SISWA CONTOH','nisn'=>'XXXXXXXXXX'],
            'madrasah'=>[
                'nama_madrasah'=>'MADRASAH CONTOH','alamat_madrasah'=>'Alamat madrasah (pratinjau)',
                'kota_penerbitan'=>'Kota Contoh','kepala_madrasah_nama'=>'NAMA KEPALA MADRASAH',
                'kepala_madrasah_nip'=>'000000000000000000',
            ],
            'paket'=>['nama_mapel'=>'Mata Pelajaran Contoh','semester'=>4],
            'tp'=>[
                ['nomor_bab'=>1,'nama_bab'=>'BAB Contoh','kode_tp'=>'SIM.8.1.1',
                    'nama_tp'=>'TP Esensial','jenis_tp'=>'esensial','nilai_tp'=>null],
                ['nomor_bab'=>1,'nama_bab'=>'BAB Contoh','kode_tp'=>'SIM.8.1.2',
                    'nama_tp'=>'TP NonEsensial','jenis_tp'=>'non_esensial','nilai_tp'=>88],
            ],
        ];
        return $this->render($snapshot,$background,$ttd,'',true);
    }
}
