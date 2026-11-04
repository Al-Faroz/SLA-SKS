<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use ZipArchive;

/**
 * Pemeriksaan awal berkas ATP sebelum masuk parser/pratinjau.
 * Tidak membaca/mengubah database atau menyimpan konten akademik.
 *
 * Parser tetap bertanggung jawab atas batas ekspansi ZIP, macro,
 * header, versi, formula, serta seluruh aturan bisnis Excel.
 */
final class AtpValidasiUnggahService
{
    public const MAKSIMUM_BYTE = 5 * 1024 * 1024;

    public static function periksa(string $path, string $namaAsli, int $ukuran): void
    {
        if (strtolower((string) pathinfo($namaAsli, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new DomainException(
                'Format file tidak sesuai. Unggah Excel ATP resmi (.xlsx), bukan .xls atau .xlsm.'
            );
        }
        if ($ukuran < 1 || $ukuran > self::MAKSIMUM_BYTE
            || ! is_file($path) || ! is_readable($path)) {
            throw new DomainException('File ATP harus tersedia dan berukuran maksimal 5 MB.');
        }
        $ukuranFisik = filesize($path);
        if ($ukuranFisik === false || $ukuranFisik < 1
            || $ukuranFisik > self::MAKSIMUM_BYTE) {
            throw new DomainException('Ukuran file Excel tidak valid atau melebihi 5 MB.');
        }

        // Berbagai instalasi finfo (PHP Windows, Linux dan shared hosting)
        // mengenali XLSX sebagai OpenXML, ZIP atau octet-stream.
        // Octet-stream hanya diterima jika kontainer XLSX benar-benar valid.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
        if (! in_array($mime, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/x-zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ], true)) {
            throw new DomainException(
                'Berkas yang diunggah bukan Excel .xlsx yang dikenal sistem.'
            );
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new DomainException('File Excel rusak atau bukan berkas .xlsx yang sah.');
        }
        try {
            if ($zip->locateName('xl/workbook.xml') === false
                || $zip->locateName('[Content_Types].xml') === false) {
                throw new DomainException(
                    'Struktur Excel tidak lengkap. Unduh ulang template ATP V25-2.'
                );
            }
        } finally {
            $zip->close();
        }
    }
}
