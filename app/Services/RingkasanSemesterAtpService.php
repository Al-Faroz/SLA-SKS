<?php

declare(strict_types=1);

namespace App\Services;

/** Statistik tampilan Peta ATP dari data semester yang sudah lolos otorisasi G1. */
final class RingkasanSemesterAtpService
{
    /** @param array<string,mixed> $semester
     *  @return array{paket:int,bab:int,tp:int,esensial:int,non_esensial:int,status:string}
     */
    public static function dariPeta(array $semester): array
    {
        $paket = $semester['paket'] ?? [];
        $bab = $semester['bab'] ?? [];
        $tp = $semester['tp'] ?? [];
        $esensial = 0;
        $nonEsensial = 0;
        foreach ($tp as $baris) {
            if (($baris['jenis_tp'] ?? '') === 'esensial') {
                $esensial++;
            } elseif (($baris['jenis_tp'] ?? '') === 'non_esensial') {
                $nonEsensial++;
            }
        }
        $status = 'belum_ada';
        foreach ($paket as $baris) {
            if (($baris['status'] ?? '') === 'aktif') {
                $status = 'aktif';
                break;
            }
            if (($baris['status'] ?? '') === 'draf') {
                $status = 'draf';
            } elseif ($status === 'belum_ada') {
                $status = 'arsip';
            }
        }
        return [
            'paket' => count($paket),
            'bab' => count($bab),
            'tp' => count($tp),
            'esensial' => $esensial,
            'non_esensial' => $nonEsensial,
            'status' => $status,
        ];
    }

    /** @return string */
    public static function namaKelas(int $semester): string
    {
        return match ($semester) {
            1, 2 => 'VII',
            3, 4 => 'VIII',
            5, 6 => 'IX',
            default => '—',
        };
    }
}
