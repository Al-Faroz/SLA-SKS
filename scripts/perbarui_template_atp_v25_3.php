<?php

declare(strict_types=1);

/**
 * Jalankan pada MASTER workbook docs tanpa data Guru:
 * php scripts/perbarui_template_atp_v25_3.php
 * Mengganti template resmi menjadi V25-3 dengan enam dropdown BAB
 * dan 480 formula TP; tidak membuat/memuat data pengguna.
 */
$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

use App\Services\AtpDesainWorkbookService;
use App\Services\AtpOtomatisTpService;
use App\Services\AtpTemplateLayoutService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$file = $root . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';
if (! is_file($file)) {
    fwrite(STDERR, "Template ATP resmi tidak ditemukan.\n");
    exit(1);
}
$buku = IOFactory::load($file);
try {
    $versi = (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue();
    // Jangan skip master V25-3: marker versi saja tidak membuktikan
    // bahwa semua dropdown dan rumus benar-benar tersimpan di XLSX.
    if (! in_array($versi, [
        AtpTemplateLayoutService::LAMA, AtpTemplateLayoutService::BARU,
        AtpOtomatisTpService::VERSI,
    ], true)) {
        throw new DomainException('Versi master ATP tidak didukung: ' . $versi);
    }

    AtpTemplateLayoutService::terapkan($buku);
    AtpOtomatisTpService::terapkan($buku);
    AtpDesainWorkbookService::terapkan($buku);

    for ($n = 1; $n <= 6; ++$n) {
        $sheet = $buku->getSheetByName('SEMESTER ' . $n);
        $dv = $sheet->getDataValidation('A39');
        if (! \App\Services\AtpOtomatisTpService::daftarBabSah($buku, $n)) {
            throw new RuntimeException('Dropdown BAB belum valid Semester ' . $n);
        }
        foreach (range(39,118) as $row) {
            if (! AtpOtomatisTpService::formulaSah($sheet, 'B' . $row)) {
                throw new RuntimeException('Urutan TP belum valid Semester '
                    . $n . ', baris ' . $row);
            }
        }
        if ($sheet->getCell('A13')->getValue() !== null
            || $sheet->getCell('D39')->getValue() !== null) {
            throw new RuntimeException('Template master membawa isian Guru, jangan commit.');
        }
    }

    $tmp = $file . '.v25_3_tmp';
    try {
        $writer = new Xlsx($buku);
        $writer->setPreCalculateFormulas(false);
        $writer->save($tmp);
        // Uji setelah serialisasi, bukan hanya objek PHP di memori.
        // File lama tidak diganti apabila validasi Excel hilang saat disimpan.
        if (! is_file($tmp) || filesize($tmp) < 100) {
            throw new RuntimeException('Hasil XLSX ATP kosong atau gagal dibuat.');
        }
        $verifikasi = IOFactory::createReader('Xlsx')->load($tmp);
        try {
            \App\Services\AtpUnduhanService::periksaTemplateV25Tiga($verifikasi);
        } finally {
            $verifikasi->disconnectWorksheets();
        }
        \App\Services\AtpUnduhanService::periksaDropdownXlsx($tmp);
        \App\Services\AtpUnduhanService::periksaFormatProteksiXlsx($tmp);
        if (! rename($tmp, $file)) {
            throw new RuntimeException('Gagal menyimpan template master V25-3.');
        }
    } finally {
        @unlink($tmp);
    }
    echo "Master ATP V25-3 telah diperbarui: 6 dropdown BAB dan 480 rumus.\n";
} finally {
    $buku->disconnectWorksheets();
}
