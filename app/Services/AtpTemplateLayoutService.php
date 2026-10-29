<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Satu-satunya konversi template dasar V25-1 menjadi V25-2.
 * Menghilangkan input Kode TP dan Elemen CP; kode dibuat server.
 * Tidak menambah formula/macro dan tidak memproses data akademik.
 */
final class AtpTemplateLayoutService
{
    public const LAMA = 'SLA-SKS-ATP-MANUAL-V25-1';
    public const BARU = 'SLA-SKS-ATP-MANUAL-V25-2';

    public const HEADER_TP = [
        'Nomor BAB', 'Urutan TP', 'Jenis TP', 'Nama TP (maks. 50)',
        'Deskripsi TP (maks. 225)', 'Judul satu Materi',
        'Jenis media (rencana)', 'Catatan',
    ];

    public static function terapkan(Spreadsheet $buku): void
    {
        $ringkasan = $buku->getSheetByName('RINGKASAN');
        if ($ringkasan === null) {
            throw new DomainException('RINGKASAN ATP tidak tersedia.');
        }
        $versi = (string) $ringkasan->getCell('B4')->getValue();
        if ($versi === self::BARU) {
            // Workbook statis lama V25-2 dapat memuat petunjuk yang keliru.
            // Selaraskan tanpa menghapus isian Guru atau merevisi versi file.
            $panduan = $buku->getSheetByName('MULAI DI SINI');
            if ($panduan !== null) {
                $panduan->setCellValue('A7', '4. Setiap TP, Esensial maupun NonEsensial,'
                    . ' wajib punya satu judul Materi dan jenis media dalam rencana ATP.'
                    . ' Kode TP dibuat otomatis saat impor.');
                $panduan->setCellValue('A8', '5. Baca CP resmi pada REFERENSI CP.'
                    . ' Aturan akses/pembelajaran siswa ditetapkan di aplikasi.');
            }
            return;
        }
        if ($versi !== self::LAMA) {
            throw new DomainException('Versi template sumber ATP tidak didukung.');
        }
        $ringkasan->setCellValueExplicit('B4', self::BARU, DataType::TYPE_STRING);
        $panduan = $buku->getSheetByName('MULAI DI SINI');
        if ($panduan !== null) {
            $panduan->setCellValue('A3', 'VERSI KONTRAK: ' . self::BARU
                . ' (XLSX manual tanpa macro/formula dan tanpa Kode TP/Elemen CP di tabel TP).');
            $panduan->setCellValue('A7', '4. Isi TP: nomor BAB, urutan TP, jenis, nama, deskripsi,'
                . ' rencana satu Materi/media, dan catatan. Kode TP dibuat otomatis saat impor.');
            $panduan->setCellValue('A8', '5. Baca CP resmi pada REFERENSI CP.'
                . ' Kolom Elemen CP tidak perlu diulang di setiap TP.');
        }
        for ($n = 1; $n <= 6; ++$n) {
            $sh = $buku->getSheetByName('SEMESTER ' . $n);
            if ($sh === null) {
                throw new DomainException('Sheet SEMESTER ' . $n . ' tidak tersedia.');
            }
            $sh->setCellValueExplicit('A2', self::BARU, DataType::TYPE_STRING);
            // Ubah sembilan/sepuluh input lama menjadi delapan. Area input
            // template dasar kosong; nilai existing tetap dialihkan bila ada.
            for ($row = 39; $row <= 118; ++$row) {
                $lama = [];
                foreach (['A','B','D','E','F','G','H','J'] as $col) {
                    $lama[] = $sh->getCell($col . $row)->getValue();
                }
                foreach (range('A', 'J') as $i => $col) {
                    $sh->setCellValue($col . $row, $i < 8 ? $lama[$i] : null);
                }
            }
            foreach (self::HEADER_TP as $i => $header) {
                $sh->setCellValue(chr(65 + $i) . '38', $header);
            }
            $sh->setCellValue('I38', null);
            $sh->setCellValue('J38', null);
            // Dipasang ulang pada lokasi baru, bukan mempertahankan validasi
            // D/H lama yang tidak lagi bermakna.
            $sh->getDataValidation('D39')->setType('none');
            $sh->getDataValidation('H39')->setType('none');
            $sh->getDataValidation('C39')->setType('list')
                ->setSqref('C39:C118')
                ->setFormula1('"Esensial,NonEsensial"')
                ->setAllowBlank(true)->setShowDropDown(true);
            $sh->getDataValidation('G39')->setType('list')
                ->setSqref('G39:G118')
                ->setFormula1('"YouTube,PDF,Audio,Gambar"')
                ->setAllowBlank(true)->setShowDropDown(true);
            $sh->setCellValue('A35', '02  —  HUBUNGKAN TIAP TP KE NOMOR BAB BAGIAN A.');
            $sh->setCellValue('A36', 'Semua TP wajib punya Materi: judul F, jenis media G.'
                . ' Kode TP dibuat otomatis oleh server.');
            $sh->setCellValue('A37', 'Urutan TP tiap BAB dimulai dari 1. '
                . 'Kode TP dibangkitkan server dari Mapel–Kelas–BAB–Urutan.');
        }
        // Contoh tetap ilustrasi, tetapi tidak boleh mengajar Guru
        // mengetik Kode TP maupun Elemen CP.
        $contoh = $buku->getSheetByName('CONTOH MAT VII');
        if ($contoh !== null) {
            $contoh->fromArray([
                'Nomor BAB','Urutan TP','Jenis TP','Nama TP',
                'Deskripsi TP','Judul Materi','Jenis media','Catatan'
            ], null, 'A10');
            for ($r = 11; $r <= 13; ++$r) {
                $oldJenis = (string) $contoh->getCell('D' . $r)->getValue();
                $oldNama = (string) $contoh->getCell('E' . $r)->getValue();
                $oldMateri = $contoh->getCell('F' . $r)->getValue();
                $contoh->fromArray([
                    (int) $contoh->getCell('A' . $r)->getValue(),
                    (int) $contoh->getCell('B' . $r)->getValue(),
                    $oldJenis, $oldNama,
                    'Contoh uraian TP, bukan rujukan akademik',
                    $oldMateri, null, null, null, null,
                ], null, 'A' . $r);
            }
            $contoh->setCellValue('I10', null);
            $contoh->setCellValue('J10', null);
        }
    }
}
