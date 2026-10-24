<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Rekonsiliasi durasi Materi menurut jam SERVER dan rangkaian heartbeat.
 * Sumber event putar/visibilitas browser adalah sinyal, bukan bukti anti-curang.
 * Interval 5–20 detik; jeda panjang, tab background, atau lompatan seek
 * tidak menerima kredit untuk interval yang hilang.
 */
final class AturanAktivitasMateriService
{
    public const DETAK_MINIMAL = 5;
    public const DETAK_MAKSIMAL = 20;
    public const GAMBAR_MINIMAL = 30;
    public const PERSEN_PUTAR = 90;

    public static function awal(string $jenis, int $now, ?int $posisi, ?int $durasi): array
    {
        self::validasiMedia($jenis, $posisi, $durasi);
        return [
            'status' => 'berjalan', 'waktu_aktif_detik' => 0,
            'waktu_putar_detik' => 0, 'posisi_terakhir_detik' => $posisi,
            'posisi_terjauh_detik' => $posisi ?? 0,
            'durasi_media_detik' => $durasi,
            'heartbeat_terakhir_pada' => $now,
        ];
    }

    /** @param array<string,mixed> $awal */
    public static function detak(array $awal, string $jenis, int $now,
        ?int $posisi, ?int $durasi, int $minimal): array
    {
        self::validasiMedia($jenis, $posisi, $durasi);
        if ($minimal < 1 || $minimal > 10800) {
            throw new DomainException('Pengaturan waktu Materi tidak sah.');
        }
        if (($awal['status'] ?? '') === 'media_selesai') {
            return $awal;
        }
        if (($awal['status'] ?? '') !== 'berjalan') {
            throw new DomainException('Status media tidak sah.');
        }
        $last = (int) ($awal['heartbeat_terakhir_pada'] ?? 0);
        if ($last < 1 || $now < $last) throw new DomainException('Waktu server tidak sah.');
        $dt = $now - $last;
        $kredit = 0;
        if ($jenis === 'gambar') {
            // Tidak ada cara membuktikan pandangan mata; UI hanya mengirim saat
            // gambar >=50% di viewport, tab terlihat dan browser fokus.
            if ($dt >= self::DETAK_MINIMAL && $dt <= self::DETAK_MAKSIMAL) {
                $kredit = $dt;
            }
        } else {
            $tersimpan = $awal['durasi_media_detik'] ?? null;
            if ($tersimpan !== null && abs((int) $tersimpan - (int) $durasi) > 2) {
                throw new DomainException('Durasi media berubah; muat ulang pemutar.');
            }
            $sebelum = $awal['posisi_terakhir_detik'];
            $beda = $sebelum === null ? 0 : $posisi - (int) $sebelum;
            if ($dt >= self::DETAK_MINIMAL && $dt <= self::DETAK_MAKSIMAL
                && $beda >= 0 && $beda <= $dt + 2) {
                $kredit = min($dt, $beda);
            }
            // Seek maju tidak memberikan kredit, tetapi posisi di-reset
            // sehingga pemutaran setelah seek masih bisa dipantau.
            $awal['posisi_terakhir_detik'] = $posisi;
            $awal['posisi_terjauh_detik'] = max(
                (int) ($awal['posisi_terjauh_detik'] ?? 0), $posisi);
            $awal['durasi_media_detik'] = $tersimpan ?? $durasi;
            $awal['waktu_putar_detik'] = (int) $awal['waktu_putar_detik'] + $kredit;
        }
        $awal['waktu_aktif_detik'] = (int) $awal['waktu_aktif_detik'] + $kredit;
        $awal['heartbeat_terakhir_pada'] = $now;
        $cukup = $jenis === 'gambar'
            ? (int) $awal['waktu_aktif_detik'] >= max(self::GAMBAR_MINIMAL, $minimal)
            : (int) $awal['waktu_aktif_detik'] >= $minimal
                && (int) $awal['waktu_putar_detik'] >= (int) ceil($durasi * .9)
                && (int) $awal['posisi_terjauh_detik'] >= (int) ceil($durasi * .9);
        if ($cukup) $awal['status'] = 'media_selesai';
        return $awal;
    }

    private static function validasiMedia(string $jenis, ?int $posisi, ?int $durasi): void
    {
        if (!in_array($jenis, ['gambar','audio','youtube'], true)) {
            throw new DomainException('Jenis media belum mendukung pelacakan ini.');
        }
        if ($jenis === 'gambar') {
            if ($posisi !== null || $durasi !== null) {
                throw new DomainException('Gambar tidak memerlukan posisi pemutar.');
            }
            return;
        }
        if ($durasi === null || $durasi < 5 || $durasi > 10800 || $posisi === null
            || $posisi < 0 || $posisi > $durasi + 1) {
            throw new DomainException('Metadata durasi/posisi pemutaran tidak sah.');
        }
    }
}
