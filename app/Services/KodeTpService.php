<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** Aturan kode dan urutan TP v2.5, tanpa bergantung pada database/UI. */
final class KodeTpService
{
    /**
     * Kode TP = KODEMAPEL.KELAS.NOMOR_BAB.NOMOR_TP, mis. SKI.8.1.1.
     * Guru tidak menentukan kode sesuka hati. Kode dari Excel diuji ulang.
     */
    public static function bentuk(string $kodeMapel, int $kelas, int $nomorBab, int $urutanTp): string
    {
        if ($kodeMapel !== trim($kodeMapel)
            || preg_match('/\A[A-Z][A-Z0-9]{0,23}\z/D', $kodeMapel) !== 1) {
            throw new DomainException('Kode mata pelajaran tidak valid.');
        }
        if (! in_array($kelas, [7, 8, 9], true)) {
            throw new DomainException('Kelas harus VII, VIII, atau IX.');
        }
        if ($nomorBab < 1 || $nomorBab > 999 || $urutanTp < 1 || $urutanTp > 65535) {
            throw new DomainException('Nomor BAB atau urutan TP tidak valid.');
        }
        $kode = $kodeMapel . '.' . $kelas . '.' . $nomorBab . '.' . $urutanTp;
        if (strlen($kode) > 40) {
            throw new DomainException('Kode TP melebihi panjang kolom database.');
        }
        return $kode;
    }

    /** Bandingkan kode yang diunggah tanpa mengabaikan salah ketik/spasi. */
    public static function cocok(string $dariExcel, string $kodeMapel, int $kelas, int $nomorBab, int $urutanTp): bool
    {
        return hash_equals(self::bentuk($kodeMapel, $kelas, $nomorBab, $urutanTp), $dariExcel);
    }

    /** Identitas kelas Fase D berasal dari referensi DB, bukan ID baris. */
    public static function kelasDariNama(string $nama): int
    {
        return match (strtoupper(trim($nama))) {
            'VII' => 7,
            'VIII' => 8,
            'IX' => 9,
            default => throw new DomainException('Nama kelas Fase D tidak dikenali.'),
        };
    }

    /**
     * Urutan dalam BAB selalu mulai dari 1 tanpa lubang/duplikat.
     * @param list<array<string,mixed>> $daftarTp
     */
    public static function urutanBerikutnya(array $daftarTp): int
    {
        $nomor = [];
        foreach ($daftarTp as $tp) {
            $n = (int) ($tp['urutan_dalam_bab'] ?? 0);
            if ($n < 1 || $n > 65535 || isset($nomor[$n])) {
                throw new DomainException('Urutan TP dalam BAB tidak konsisten.');
            }
            $nomor[$n] = true;
        }
        $berikutnya = count($nomor) + 1;
        if ($berikutnya > 65535) {
            throw new DomainException('Jumlah TP dalam BAB melebihi kapasitas penyimpanan.');
        }
        for ($n = 1; $n < $berikutnya; $n++) {
            if (! isset($nomor[$n])) {
                throw new DomainException('Urutan TP berlubang. Perbaiki ATP sebelum menambah TP.');
            }
        }
        return $berikutnya;
    }
}
