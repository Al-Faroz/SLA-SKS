<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** Peraturan nomor BAB lintas dua semester pada kelas yang sama. */
final class AturanBabService
{
    /** @param list<array<string,mixed>> $babTerdaftar */
    public static function nomorBerikutnya(int $semester, array $babTerdaftar, int $semesterMulai = 1): int
    {
        if ($semester < 1 || $semester > 6 || $semesterMulai < 1 || $semesterMulai > 6 || $semester < $semesterMulai) {
            throw new DomainException('Semester harus berada antara 1 sampai 6.');
        }

        $nomor = [];
        $adaSemesterGenap = false;
        $adaSemesterGanjil = false;
        foreach ($babTerdaftar as $bab) {
            $keSemester = (int) ($bab['semester'] ?? 0);
            $keNomor = (int) ($bab['nomor_bab'] ?? 0);
            if ($keSemester < 1 || $keSemester > 6 || intdiv($keSemester - 1, 2) !== intdiv($semester - 1, 2)
                || $keNomor < 1 || $keNomor > 999 || isset($nomor[$keNomor])) {
                throw new DomainException('Urutan BAB tersimpan tidak konsisten. Periksa ATP sebelum menambahkan BAB.');
            }
            $nomor[$keNomor] = true;
            if ($keSemester % 2 === 0) {
                $adaSemesterGenap = true;
            } else {
                $adaSemesterGanjil = true;
            }
        }

        // Gen yang mulai langsung dari semester genap (mis. Gen2025 mulai S4)
        // tidak mempunyai kewajiban BAB semester ganjil yang dilewati.
        if ($semester % 2 === 0 && ! $adaSemesterGanjil && $semesterMulai !== $semester) {
            throw new DomainException('Buat BAB pada semester ganjil di kelas yang sama terlebih dahulu agar nomor BAB berlanjut.');
        }

        if ($semester % 2 === 1 && $adaSemesterGenap) {
            throw new DomainException('BAB semester genap sudah disusun. Penambahan BAB semester ganjil memerlukan peninjauan urutan terlebih dahulu.');
        }

        $berikutnya = count($nomor) + 1;
        for ($n = 1; $n < $berikutnya; $n++) {
            if (! isset($nomor[$n])) {
                throw new DomainException('Nomor BAB tidak berurutan. Periksa ATP sebelum melanjutkan.');
            }
        }
        if ($berikutnya > 999) {
            throw new DomainException('Nomor BAB telah mencapai batas penyimpanan.');
        }

        return $berikutnya;
    }
}
