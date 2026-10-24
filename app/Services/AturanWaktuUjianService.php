<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Kebijakan transisi deterministik ujian Ketat v2.4 (domain murni).
 *
 * Timestamp dalam Unix epoch detik server. Pemanggil S06B wajib menyimpan
 * perubahan dan segmen audit di dalam transaksi dengan SELECT ... FOR UPDATE.
 * Tidak boleh menulis berdasarkan waktu/insiden yang dikirim klien.
 */
final class AturanWaktuUjianService
{
    public const TIMEOUT_KONEKSI = 20;
    public const JEDA_FULLSCREEN_KEJADIAN = 90;
    public const JEDA_FULLSCREEN_TOTAL = 180;
    public const JEDA_KONEKSI_TOTAL = 300;
    public const PELANGGARAN_FULLSCREEN = 3;

    /** @return array<string,int|string|null> */
    public static function awal(int $durasiDetik, int $sekarang): array
    {
        if ($durasiDetik < 300 || $durasiDetik > 10800 || $sekarang < 1) {
            throw new DomainException('Durasi ujian atau waktu server tidak sah.');
        }
        return [
            'status' => 'berjalan', 'sisa_detik' => $durasiDetik,
            'waktu_mulai' => $sekarang, 'waktu_aktif_terakhir' => $sekarang,
            'last_seen_server_at' => $sekarang, 'jeda_mulai_pada' => null,
            'jeda_fullscreen_total_detik' => 0, 'jeda_koneksi_total_detik' => 0,
            'jumlah_pelanggaran_fullscreen' => 0, 'alasan_batal' => null,
            'waktu_selesai' => null,
        ];
    }

    /**
     * Rekonsiliasi setiap request menggunakan satu jam server.
     * Saat koneksi hilang, 20 detik pertama dihitung sebagai waktu aktif.
     * Setelah itu hanya waktu jeda koneksi yang bertambah (tidak tumpang tindih).
     *
     * @param array<string,mixed> $state
     * @return array<string,mixed>
     */
    public static function rekonsiliasi(array $state, int $sekarang): array
    {
        $status = (string) ($state['status'] ?? '');
        if (in_array($status, ['selesai_sah', 'dibatalkan_menunggu_guru'], true)) {
            return $state;
        }
        if (!in_array($status, ['berjalan', 'jeda_fullscreen', 'jeda_koneksi'], true)) {
            throw new DomainException('Status percobaan tidak sesuai kontrak.');
        }
        $waktu = (int) ($state['waktu_aktif_terakhir'] ?? 0);
        $seen = (int) ($state['last_seen_server_at'] ?? 0);
        if ($sekarang < 1 || $seen < 1 || $waktu < 1 || $sekarang < $seen) {
            throw new DomainException('Waktu server tidak boleh mundur.');
        }

        if ($status === 'berjalan') {
            $cutoff = $seen + self::TIMEOUT_KONEKSI;
            $akhirAktif = min($sekarang, $cutoff);
            $dt = max(0, $akhirAktif - $waktu);
            $state['sisa_detik'] = max(0, (int) $state['sisa_detik'] - $dt);
            $state['waktu_aktif_terakhir'] = $akhirAktif;
            if ($state['sisa_detik'] === 0) {
                $state['status'] = 'selesai_sah';
                $state['waktu_selesai'] = $akhirAktif;
                // Belum ada nilai sampai mesin S06B menghitung semua jawaban.
                return $state;
            }
            if ($sekarang > $cutoff) {
                $state['status'] = 'jeda_koneksi';
                $state['jeda_mulai_pada'] = $cutoff;
                return self::rekonsiliasi($state, $sekarang);
            }
            return $state;
        }

        $mulaiJeda = $state['jeda_mulai_pada'];
        if (!is_int($mulaiJeda) || $mulaiJeda < 1 || $sekarang < $mulaiJeda) {
            throw new DomainException('Segmen jeda tidak sah.');
        }
        $dt = $sekarang - $mulaiJeda;
        if ($status === 'jeda_fullscreen') {
            $total = (int) $state['jeda_fullscreen_total_detik'] + $dt;
            if ($dt >= self::JEDA_FULLSCREEN_KEJADIAN || $total >= self::JEDA_FULLSCREEN_TOTAL) {
                return self::batal($state, $sekarang, 'Batas jeda fullscreen terlampaui.');
            }
        } elseif ((int) $state['jeda_koneksi_total_detik'] + $dt >= self::JEDA_KONEKSI_TOTAL) {
            return self::batal($state, $sekarang, 'Batas jeda koneksi terlampaui.');
        }
        return $state;
    }

    /** @param array<string,mixed> $state */
    public static function heartbeat(array $state, int $sekarang): array
    {
        $state = self::rekonsiliasi($state, $sekarang);
        if ($state['status'] === 'berjalan') {
            $state['last_seen_server_at'] = $sekarang;
        }
        return $state;
    }

    /**
     * Kombinasi blur, visibilitychange dan fullscreenchange pada satu insiden
     * harus diberikan satu identitas oleh adapter. Saat sudah jeda, insiden
     * berikutnya tidak dihitung ulang (termasuk insiden duplikat).
     *
     * @param array<string,mixed> $state
     */
    public static function keluarFullscreen(array $state, int $sekarang): array
    {
        $state = self::rekonsiliasi($state, $sekarang);
        if ($state['status'] !== 'berjalan') {
            return $state;
        }
        $jumlah = (int) $state['jumlah_pelanggaran_fullscreen'] + 1;
        $state['jumlah_pelanggaran_fullscreen'] = $jumlah;
        if ($jumlah >= self::PELANGGARAN_FULLSCREEN) {
            return self::batal($state, $sekarang, 'Pelanggaran fullscreen ketiga.');
        }
        $state['status'] = 'jeda_fullscreen';
        $state['jeda_mulai_pada'] = $sekarang;
        return $state;
    }

    /**
     * Pemulihan memerlukan user gesture fullscreen lagi. Adapter harus
     * memverifikasi status UI dan izin sebelum memanggil fungsi ini.
     *
     * @param array<string,mixed> $state
     */
    public static function pulih(array $state, int $sekarang): array
    {
        $state = self::rekonsiliasi($state, $sekarang);
        if (!in_array($state['status'], ['jeda_fullscreen', 'jeda_koneksi'], true)) {
            return $state;
        }
        $dt = $sekarang - (int) $state['jeda_mulai_pada'];
        $field = $state['status'] === 'jeda_fullscreen'
            ? 'jeda_fullscreen_total_detik' : 'jeda_koneksi_total_detik';
        $state[$field] += $dt;
        $state['status'] = 'berjalan';
        $state['jeda_mulai_pada'] = null;
        $state['waktu_aktif_terakhir'] = $sekarang;
        $state['last_seen_server_at'] = $sekarang;
        return $state;
    }

    /** @param array<string,mixed> $state */
    private static function batal(array $state, int $sekarang, string $alasan): array
    {
        $state['status'] = 'dibatalkan_menunggu_guru';
        $state['alasan_batal'] = $alasan;
        $state['waktu_selesai'] = $sekarang;
        return $state;
    }

    /**
     * Waktu habis adalah selesai sah (penilaian baru dihitung oleh S06B).
     * Jangan izinkan jawab saat pause, batal, atau ujian selesai.
     */
    public static function bolehMenjawab(array $state): bool
    {
        return ($state['status'] ?? null) === 'berjalan'
            && (int) ($state['sisa_detik'] ?? 0) > 0;
    }
}
