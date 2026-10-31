<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\NamedFormula;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * V25-3: Guru memilih Nomor BAB dari dropdown; urutan TP dalam BAB
 * otomatis pada B39:B118. Formula Excel hanya sarana pengisian, dan
 * TIDAK PERNAH menjadi sumber identitas/otorisasi saat impor.
 */
final class AtpOtomatisTpService
{
    public const VERSI = 'SLA-SKS-ATP-MANUAL-V25-3';

    public static function rumus(int $baris): string
    {
        if ($baris < 39 || $baris > 118) {
            throw new DomainException('Baris urutan TP di luar area template.');
        }
        return '=IF(OR(A' . $baris . '="",D' . $baris
            . '=""),"",COUNTIFS($A$39:A' . $baris . ',A'
            . $baris . ',$D$39:D' . $baris . ',"<>"))';
    }

    public static function namaDaftarBab(int $semester): string
    {
        if ($semester < 1 || $semester > 6) {
            throw new DomainException('Semester di luar enam semester Fase D.');
        }
        $namaSheet = "'SEMESTER " . $semester . "'!";
        return '=OFFSET(' . $namaSheet . '$A$12,1,0,MAX(1,COUNT('
            . $namaSheet . '$A$13:$A$32)),1)';
    }

    public static function daftarBabSah(Spreadsheet $buku, int $semester): bool
    {
        $nama = 'BABS_' . $semester;
        $daftar = $buku->getDefinedName($nama);
        $sheet = $buku->getSheetByName('SEMESTER ' . $semester);
        if ($daftar === null || $sheet === null
            || ltrim($daftar->getValue(), '=') !== ltrim(self::namaDaftarBab($semester), '=')) {
            return false;
        }
        $validasi = $sheet->getDataValidation('A39');
        return $validasi->getType() === 'list'
            && $validasi->getSqref() === 'A39:A118'
            && ltrim($validasi->getFormula1(), '=') === $nama
            && ! $validasi->getShowDropDown()
            && $validasi->getShowErrorMessage();
    }

    public static function formulaSah(Worksheet $sheet, string $alamat): bool
    {
        if (! preg_match('/\ASEMESTER [1-6]\z/D', $sheet->getTitle())
            || ! preg_match('/\AB(39|[4-9][0-9]|10[0-9]|11[0-8])\z/D', $alamat)
            || $sheet->getCell($alamat)->getDataType() !== DataType::TYPE_FORMULA) {
            return false;
        }
        return (string) $sheet->getCell($alamat)->getValue()
            === self::rumus((int) substr($alamat, 1));
    }

    /**
     * Update V25-2 menjadi V25-3 pada template master yang masih kosong.
     * Idempoten untuk master V25-3; tidak boleh dipanggil terhadap XLSX
     * yang telah diisi oleh Guru.
     */
    public static function terapkan(Spreadsheet $buku): void
    {
        $ringkasan = $buku->getSheetByName('RINGKASAN');
        if ($ringkasan === null) {
            throw new DomainException('Sheet RINGKASAN ATP tidak ada.');
        }
        $versi = (string) $ringkasan->getCell('B4')->getValue();
        if (! in_array($versi, [AtpTemplateLayoutService::BARU, self::VERSI], true)) {
            throw new DomainException('Template sumber harus V25-2 atau V25-3.');
        }
        $ringkasan->setCellValueExplicit('B4', self::VERSI, DataType::TYPE_STRING);

        $mulai = $buku->getSheetByName('MULAI DI SINI');
        if ($mulai !== null) {
            $mulai->setCellValue('A3', 'KONTRAK ' . self::VERSI
                . ' — Excel manual dengan dropdown BAB dan rumus urutan TP terkontrol, tanpa macro.');
            $mulai->setCellValue('A7', '4. Isi BAB pada baris 13–32.'
                . ' Pada daftar TP pilih Nomor BAB di A dan isi Nama TP di D.'
                . ' Kolom B terhitung otomatis per BAB, jangan ditimpa.');
            $mulai->setCellValue('A8', '5. Isi Jenis TP, Deskripsi TP, Judul Materi dan Jenis Media'
                . ' untuk setiap TP, termasuk Esensial. CP resmi ada di REFERENSI CP.');
        }

        for ($semester = 1; $semester <= 6; ++$semester) {
            $sheet = $buku->getSheetByName('SEMESTER ' . $semester);
            if ($sheet === null) {
                throw new DomainException('Sheet SEMESTER ' . $semester . ' tidak ada.');
            }
            $sheet->setCellValueExplicit('A2', self::VERSI, DataType::TYPE_STRING);
            $sheet->setCellValue('A36', 'Pilih Nomor BAB (kolom A) dari dropdown;'
                . ' Urutan TP (B) dihitung otomatis per BAB.');
            $sheet->setCellValue('A37', 'Isi Nama TP (D) serta Materi (F) dan Media (G)'
                . ' untuk kedua jenis TP; Kode TP dibuat server.');

            $nama = 'BABS_' . $semester;
            if ($buku->getDefinedName($nama) !== null) {
                $buku->removeDefinedName($nama);
            }
            // 20 sel input BAB A13:A32, tidak A12:A31. MAX(1,..)
            // mencegah daftar dengan ukuran nol sebelum BAB diisi.
            $buku->addNamedFormula(new NamedFormula(
                $nama, $sheet, self::namaDaftarBab($semester)
            ));

            $dv = $sheet->getDataValidation('A39');
            $dv->setType('list')
                ->setSqref('A39:A118')
                ->setAllowBlank(true)
                ->setShowDropDown(false) // OOXML: false = panah dropdown terlihat di Excel
                ->setShowErrorMessage(true)
                ->setErrorTitle('Nomor BAB tidak terdaftar')
                ->setError('Pilih Nomor BAB dari daftar BAB pada bagian atas sheet ini.')
                ->setFormula1($nama);

            // Semua dropdown harus tampak dan menolak isian di luar daftar.
            // OOXML showDropDown=true justru menyembunyikan panah Excel.
            foreach ([
                ['C39', 'C39:C118', '"Esensial,NonEsensial"', 'Jenis TP tidak terdaftar',
                    'Pilih jenis TP dari daftar yang tersedia.'],
                ['G39', 'G39:G118', '"YouTube,PDF,Audio,Gambar"', 'Jenis media tidak terdaftar',
                    'Pilih jenis media dari daftar yang tersedia.'],
            ] as [$alamat, $rentang, $daftarPilihan, $judulGalat, $pesanGalat]) {
                $sheet->getDataValidation($alamat)
                    ->setType('list')->setSqref($rentang)->setFormula1($daftarPilihan)
                    ->setAllowBlank(true)->setShowDropDown(false)
                    ->setShowErrorMessage(true)->setErrorTitle($judulGalat)
                    ->setError($pesanGalat);
            }

            foreach (['D39', 'H39'] as $alamat) {
                $obsolete = $sheet->getDataValidation($alamat);
                $obsolete->setType('none')->setFormula1('')
                    ->setShowErrorMessage(false)->setShowInputMessage(false);
            }
            for ($row = 39; $row <= 118; ++$row) {
                $sheet->getCell('B' . $row)->setValueExplicit(
                    self::rumus($row), DataType::TYPE_FORMULA
                );
            }
        }
        // Meminta Excel menghitung ulang setelah Guru memilih BAB/Nama TP.
        // Excel otomatis merekalkulasi formula setelah A/D diisi; server
        // tetap mengabaikan semua cached formula values saat impor.
    }
}
