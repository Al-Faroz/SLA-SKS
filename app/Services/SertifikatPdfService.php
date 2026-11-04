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
        $jumlahBab=0;
        $jumlahTp=0;
        $panjangNamaTp=0;
        foreach ($tp as $item) {
            if (!is_array($item)) continue;
            $jumlahTp++;
            $panjangNamaTp=max(
                $panjangNamaTp,
                mb_strlen((string)($item['nama_tp']??''))
            );
            $nomor=(string)($item['nomor_bab']??'');
            if ($babLama!==$nomor) {
                $jumlahBab++;
                $rows.='<tr class="bab-row"><td colspan="4">'
                    .'<span class="bab-label">BAB '.self::teks($nomor).'</span>'
                    .' <span class="bab-nama">— '
                    .self::teks($item['nama_bab']??'').'</span></td></tr>';
                $babLama=$nomor;
            }
            $esensial=($item['jenis_tp']??'')==='esensial';
            $nilai=$esensial?'—':(
                ($item['nilai_tp']??null)===null ? '—'
                    : number_format((float)$item['nilai_tp'],2,',','.')
            );
            $rows.='<tr class="tp-row"><td class="kode">'
                .self::teks($item['kode_tp']??'')
                .'</td><td class="tujuan">'
                .self::teks($item['nama_tp']??'')
                .'</td><td class="jenis">'.($esensial?'Esensial':'NonEsensial')
                .'</td><td class="num">'.self::teks($nilai).'</td></tr>';
        }
        if ($rows==='') {
            $rows='<tr class="tp-row"><td colspan="4">Belum ada TP.</td></tr>';
        }

        // A11-3: pakai tinggi sertifikat penuh hanya saat tabel cukup pendek.
        // Paket dengan banyak TP tetap memakai aliran halaman normal agar footer
        // tidak menutupi baris atau terpotong saat dicetak.
        $satuHalaman=$jumlahTp<=4 && $jumlahBab<=3
            && $panjangNamaTp<=80
            && mb_strlen((string)($siswa['nama']??''))<=65;
        $kelasHalaman=$satuHalaman?'card card--full':'card card--flow';

        $backgroundHtml=$latar!==''?'<img class="background" src="'.$latar.'" alt="">':'';
        $ttdHtml=$tandaTangan!==''
            ? '<img class="ttd" src="'.$tandaTangan.'" alt="Tanda tangan kepala madrasah">'
            : '<div class="ttd-kosong">Contoh area TTD</div>';
        $qrHtml=$qr!==''
            ? '<img class="qr" src="'.$qr.'" alt="QR verifikasi sertifikat">'
            : '<div class="qr-kosong">Pratinjau tanpa QR</div>';
        $watermark=$pratinjau
            ? '<div class="watermark">PRATINJAU · BUKAN SERTIFIKAT TERBIT</div>'
            : '';

        $namaSekolah=self::teks($sekolah['nama_madrasah']??'');
        $alamatSekolah=self::teks($sekolah['alamat_madrasah']??'');
        $nomorSertifikat=self::teks($snapshot['nomor']??'PRATINJAU');
        $namaSiswa=self::teks($siswa['nama']??'');
        $nisnSiswa=self::teks($siswa['nisn']??'—');
        $namaMapel=self::teks($paket['nama_mapel']??'');
        $semester=self::teks($paket['semester']??'');
        $kota=self::teks($sekolah['kota_penerbitan']??'');
        $tanggal=self::teks($snapshot['tanggal']??'');
        $kepala=self::teks($sekolah['kepala_madrasah_nama']??'');
        $nip=self::teks($sekolah['kepala_madrasah_nip']??'');

        $html=<<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { size: A4 landscape; margin: 8mm 9mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #193E34; }
    .background {
        position: fixed; top: -8mm; left: -9mm;
        width: 297mm; height: 210mm; z-index: -2;
    }
    .page-frame {
        position: fixed; top: 0; left: 0;
        width: 278mm; height: 192mm;
        border: 1px solid #8BB9A7;
        background: rgba(255,255,255,.91);
        z-index: -1;
    }
    .card {
        position: relative;
        padding: 7mm 9mm;
        background: transparent;
        border: 0;
    }
    .card--full, .card--flow { height: auto; }
    .kop { text-align: center; border-bottom: 1.5pt solid #246D54; padding-bottom: 2mm; }
    .kop .identitas { font: bold 16pt DejaVu Serif, serif; line-height: 1.24; }
    .kop .alamat { font-size: 8.6pt; margin-top: 1mm; color: #43695D; }
    .garis-emas { width: 36mm; height: 1.6pt; margin: 1.5mm auto 0; background: #B28C49; }
    .judul { margin: 2mm 0; text-align: center; }
    h1 {
        margin: .4mm 0 .8mm; color: #173E33;
        font: bold 19pt DejaVu Serif, serif; letter-spacing: 1.2pt;
    }
    .nomor { margin: 0; font-size: 9pt; color: #3E6657; }
    table { border-collapse: collapse; width: 100%; }
    .biodata {
        margin: 1mm 0 2mm; table-layout: fixed;
        background: #F3F8F5; border: 1px solid #D1E1D7;
    }
    .biodata td {
        width: 50%; padding: 1.1mm 4mm;
        vertical-align: top; border-bottom: 1px solid #E3ECE6;
    }
    .biodata .label { display: block; font-size: 7.4pt; color: #537568; letter-spacing: .45pt; }
    .biodata .isi { display: block; font-size: 10pt; font-weight: bold; margin-top: .3mm; line-height: 1.2; }
    .biodata .isi--nama { font-size: 12pt; color: #123D30; }
    .label-tabel {
        margin: .4mm 0 1mm; font-size: 8.5pt;
        font-weight: bold; letter-spacing: 1.1pt; color: #275E4B;
    }
    .tp-table { table-layout: fixed; border: 1px solid #BCD5C8; font-size: 8.7pt; }
    .tp-table thead { display: table-header-group; }
    .tp-table th {
        background: #DCEAE2; color: #1D4A3C;
        font-size: 8.5pt; text-align: left; padding: 1.4mm 2.2mm;
        border: 1px solid #BCD5C8;
    }
    .tp-table td {
        vertical-align: top; line-height: 1.2;
        border: 1px solid #D2E1D8; padding: 1.2mm 2mm;
        overflow-wrap: break-word;
    }
    .tp-table .kode { width: 17%; white-space: nowrap; }
    .tp-table .tujuan { width: 51%; }
    .tp-table .jenis { width: 20%; }
    .tp-table .num { width: 12%; text-align: center; white-space: nowrap; font-weight: bold; }
    .tp-table .bab-row td {
        background: #E9F2EA; font-weight: bold; color: #225842;
        padding: 1.2mm 2.3mm; page-break-after: avoid;
    }
    .tp-table .bab-label { font-weight: bold; }
    .tp-table .tp-row { page-break-inside: avoid; }
    .footer { margin-top: 4mm; padding-top: 2mm; border-top: 1px solid #CADFD1; page-break-inside: avoid; }
    .card--full .footer {
        position: fixed; bottom: 4mm;
        left: 18mm; width: 260mm;
        margin-top: 0;
    }
    .footer td { width: 33.333%; vertical-align: top; text-align: center; padding: 0 2mm; }
    .footer .qr-cell { text-align: left; }
    .footer .qr { width: 23mm; height: 23mm; display: block; }
    .footer .qr-caption { margin: 1mm 0 0; font-size: 7.2pt; color: #466D5D; }
    .footer .catatan { padding-top: 5mm; font-size: 8pt; line-height: 1.65; color: #536F63; }
    .footer .tanggal { font-size: 8.8pt; margin-bottom: 1mm; }
    .footer .jabatan { font-size: 8.7pt; margin-bottom: 1.5mm; }
    .footer .ttd { height: 16mm; max-width: 48mm; }
    .footer .ttd-kosong { padding: 5mm 3mm; border: 1px dashed #8DA99B; font-size: 8pt; }
    .footer .nama { margin-top: .6mm; font-size: 9pt; font-weight: bold; text-decoration: underline; }
    .footer .nip { margin-top: .5mm; font-size: 8pt; }
    .qr-kosong { padding: 4mm 1mm; border: 1px dashed #8DA99B; text-align: center; font-size: 8pt; }
    .watermark {
        position: fixed; top: 88mm; left: 20mm; font-size: 23pt;
        font-weight: bold; color: rgba(158,36,36,.28);
        transform: rotate(-15deg); z-index: 5;
    }
</style>
</head>
<body>
$backgroundHtml
<div class="page-frame" aria-hidden="true"></div>
$watermark
<main class="$kelasHalaman">
    <header class="kop">
        <div class="identitas">$namaSekolah</div>
        <div class="alamat">$alamatSekolah</div>
        <div class="garis-emas"></div>
    </header>
    <section class="judul">
        <h1>SERTIFIKAT PEMBELAJARAN</h1>
        <p class="nomor">Nomor: $nomorSertifikat</p>
    </section>
    <table class="biodata" aria-label="Identitas penerima sertifikat">
        <tr>
            <td><span class="label">NAMA SISWA</span><span class="isi isi--nama">$namaSiswa</span></td>
            <td><span class="label">NISN</span><span class="isi">$nisnSiswa</span></td>
        </tr>
        <tr>
            <td><span class="label">MATA PELAJARAN</span><span class="isi">$namaMapel</span></td>
            <td><span class="label">SEMESTER</span><span class="isi">$semester</span></td>
        </tr>
    </table>
    <div class="label-tabel">RINCIAN TUJUAN PEMBELAJARAN</div>
    <table class="tp-table">
        <colgroup><col style="width:17%"><col style="width:51%"><col style="width:20%"><col style="width:12%"></colgroup>
        <thead><tr>
            <th>Kode TP</th><th>Tujuan Pembelajaran</th><th>Jenis</th><th>Nilai TP</th>
        </tr></thead>
        <tbody>$rows</tbody>
    </table>
    <footer class="footer">
        <table><tr>
            <td class="qr-cell">
                $qrHtml
                <div class="qr-caption">Pindai QR untuk memeriksa keaslian</div>
            </td>
            <td class="catatan">
                Dokumen ini mencatat hasil tujuan pembelajaran
                pada mata pelajaran dan semester yang tercantum.
            </td>
            <td class="tanda">
                <div class="tanggal">$kota, $tanggal</div>
                <div class="jabatan">Kepala Madrasah</div>
                $ttdHtml
                <div class="nama">$kepala</div>
                <div class="nip">NIP: $nip</div>
            </td>
        </tr></table>
    </footer>
</main>
</body>
</html>
HTML;
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
        // Jika bahkan dokumen pendek melewati satu halaman (mis. nama panjang),
        // matikan footer absolut dan gunakan alur dokumen agar tak bertumpuk.
        if ($satuHalaman && $pdf->getCanvas()->get_page_count() > 1) {
            $html=str_replace('class="card card--full"',
                'class="card card--flow"',$html);
            $pdf=new Dompdf($opt);
            $pdf->loadHtml($html,'UTF-8');
            $pdf->setPaper('A4','landscape');
            $pdf->render();
        }
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
                ['nomor_bab'=>1,'nama_bab'=>'BAB Contoh Pertama','kode_tp'=>'SIM.8.1.2',
                    'nama_tp'=>'Penerapan Keteladanan','jenis_tp'=>'non_esensial','nilai_tp'=>90],
                ['nomor_bab'=>2,'nama_bab'=>'BAB Contoh Kedua','kode_tp'=>'SIM.8.2.1',
                    'nama_tp'=>'Kajian Sejarah','jenis_tp'=>'esensial','nilai_tp'=>null],
                ['nomor_bab'=>2,'nama_bab'=>'BAB Contoh Kedua','kode_tp'=>'SIM.8.2.2',
                    'nama_tp'=>'Refleksi Pembelajaran','jenis_tp'=>'non_esensial','nilai_tp'=>82],
            ],
        ];
        return $this->render($snapshot,$background,$ttd,'',true);
    }
}
