<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DomainException;

/**
 * A13-7, tahap pra-database: validasi teknis naskah resmi sekolah.
 *
 * Tidak membuat kebijakan, tidak menetapkan retensi atau janji hukum,
 * tidak menyimpan atau menerbitkan dokumen. Tahap publikasi berversi
 * menunggu dump SQL terbaru dan naskah yang telah disahkan madrasah.
 */
final class KebijakanPrivasiValidasiService
{
    /**
     * @param array<string,mixed> $input
     * @return array{versi:string,tanggal_berlaku:string,teks:string,checksum_sha256:string}
     */
    public static function validasiNaskah(array $input): array
    {
        $versi = $input['versi'] ?? null;
        $tanggal = $input['tanggal_berlaku'] ?? null;
        $teks = $input['teks'] ?? null;

        if (! is_string($versi) || ! is_string($tanggal) || ! is_string($teks)) {
            throw new DomainException('Versi, tanggal berlaku, dan naskah harus berupa teks.');
        }
        $versi = trim($versi);
        $tanggal = trim($tanggal);
        $teks = trim(str_replace(["\r\n", "\r"], "\n", $teks));

        // Pembatasan teknis untuk mencegah input tak terkendali; bukan
        // validasi hukum atas isi kebijakan yang harus disahkan sekolah.
        if (! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,39}$/D', $versi)) {
            throw new DomainException('Versi hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung (maksimal 40 karakter).');
        }
        if (! preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $tanggal)) {
            throw new DomainException('Tanggal berlaku harus menggunakan format YYYY-MM-DD.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        if ($date === false || $date->format('Y-m-d') !== $tanggal) {
            throw new DomainException('Tanggal berlaku tidak valid.');
        }
        if ($teks === '' || mb_strlen($teks, 'UTF-8') > 50000) {
            throw new DomainException('Naskah resmi wajib diisi, maksimal 50.000 karakter.');
        }
        // HTML tidak dieksekusi: adapter View wajib memakai esc(teks).
        return [
            'versi' => $versi,
            'tanggal_berlaku' => $tanggal,
            'teks' => $teks,
            'checksum_sha256' => hash('sha256', $teks),
        ];
    }

    /**
     * Penerbitan kelak memerlukan pernyataan dari Admin bahwa naskah
     * telah disahkan sekolah; checkbox bukan bukti hukum/pengesahan.
     * Penegakan role dan versi historis dilakukan pada adapter database.
     */
    public static function pastikanKonfirmasiPengesahan(mixed $konfirmasi): void
    {
        if ($konfirmasi !== '1') {
            throw new DomainException('Admin harus memastikan naskah sudah disahkan madrasah sebelum diterbitkan.');
        }
    }
}
