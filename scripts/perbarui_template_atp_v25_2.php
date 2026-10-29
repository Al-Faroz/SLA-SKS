<?php

declare(strict_types=1);

/**
 * Satu kali konversi berkas template resmi dalam repository.
 * Jangan jalankan pada workbook yang telah diisi Guru.
 *
 * php scripts/perbarui_template_atp_v25_2.php
 */
$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';
require_once $root . '/app/Services/AtpTemplateLayoutService.php';
require_once $root . '/app/Services/AtpDesainWorkbookService.php';

use App\Services\AtpDesainWorkbookService;
use App\Services\AtpTemplateLayoutService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$file = $root . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';
if (! is_file($file)) {
    fwrite(STDERR, "Template ATP tidak ditemukan.\n");
    exit(1);
}
$buku = IOFactory::load($file);
try {
    $versi = (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue();
    if ($versi === AtpTemplateLayoutService::BARU) {
        echo "Template ATP sudah V25-2.\n";
        exit(0);
    }
    if ($versi !== AtpTemplateLayoutService::LAMA) {
        throw new DomainException('Versi sumber tidak sesuai: ' . $versi);
    }
    AtpTemplateLayoutService::terapkan($buku);
    AtpDesainWorkbookService::terapkan($buku);
    foreach (range(1, 6) as $n) {
        $sheet = $buku->getSheetByName('SEMESTER ' . $n);
        foreach (AtpTemplateLayoutService::HEADER_TP as $i => $header) {
            if ((string) $sheet->getCell(chr(65 + $i) . '38')->getValue() !== $header) {
                throw new RuntimeException('Header TP konversi salah pada semester ' . $n);
            }
        }
        if ((string) $sheet->getCell('I38')->getValue() !== ''
            || (string) $sheet->getCell('J38')->getValue() !== '') {
            throw new RuntimeException('Kolom lama I/J belum dibersihkan.');
        }
    }
    $tmp = $file . '.v25_2_tmp';
    try {
        (new Xlsx($buku))->save($tmp);
        if (! is_file($tmp) || filesize($tmp) === 0 || ! rename($tmp, $file)) {
            throw new RuntimeException('Template konversi gagal disimpan.');
        }
    } finally {
        @unlink($tmp);
    }
    echo "Template ATP V25-2 selesai diperbarui.\n";
} finally {
    $buku->disconnectWorksheets();
}
