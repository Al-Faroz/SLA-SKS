<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Kebijakan murni sesi Pra-Semester (utama, remedial, susulan).
 *
 * Adapter mendatang wajib:
 * - memverifikasi peran Admin dan paket Gen–Mapel–Semester,
 * - menyimpan jadwal + peserta susulan secara terotorisasi,
 * - memanggil periksaMulai DI DALAM transaksi sebelum INSERT attempt.
 * Tidak ada izin ulang siswa dari tombol yang bypass jadwal.
 */
final class AturanJadwalPraSemesterService
{
    private const JENIS = ['utama','remedial','susulan'];

    /**
     * @param array{jenis:string,tahap:string,mulai:string,akhir:string,peserta_ditetapkan:bool,durasi_menit:int} $jadwal
     * @param list<array<string,mixed>> $percobaan
     * @return array{boleh_buat:bool,boleh_lanjut:bool,percobaan_aktif_id:?int,tahap:?string,fase:string,alasan:list<string>,status_jadwal:string,durasi_detik:int}
     */
    public static function periksaMulai(
        array $jadwal, array $percobaan, int $sekarang,
        bool $semuaTpTuntas, bool $bankSiap,
        bool $desktop, bool $fullscreen
    ): array {
        $jenis = $jadwal['jenis'] ?? null;
        $tahap = $jadwal['tahap'] ?? null;
        $ditetapkan = $jadwal['peserta_ditetapkan'] ?? null;
        $durasi = $jadwal['durasi_menit'] ?? null;
        if (!in_array($jenis,self::JENIS,true)
            || !in_array($tahap,['normal','remedial'],true)
            || !is_bool($ditetapkan)
            || !is_int($durasi) || $durasi < 5 || $durasi > 180
            || ($jenis === 'utama' && $tahap !== 'normal')
            || ($jenis === 'remedial' && $tahap !== 'remedial')) {
            throw new DomainException('Jenis, tahap, peserta, atau durasi sesi tidak sah.');
        }
        $jendela = AturanPraSemesterService::jadwal(
            $jadwal['mulai'] ?? null,$jadwal['akhir'] ?? null,$sekarang
        );
        $hasil = PenilaianPraSemesterService::ringkas($percobaan);
        $alasan = [];
        if (!$semuaTpTuntas) $alasan[]='Selesaikan seluruh TP NonEsensial terlebih dahulu.';
        if (!$bankSiap) $alasan[]='Bank soal Pra-Semester belum siap.';
        if (!$desktop) $alasan[]='Ujian hanya untuk desktop/laptop.';
        if (!$fullscreen) $alasan[]='Fullscreen wajib diaktifkan.';

        // Siswa yang telah memulai dapat melanjutkan percobaan yang SAMA
        // meskipun jendela boleh mulai Admin sudah ditutup.
        if ($hasil['percobaan_aktif_id'] !== null) {
            return [
                'boleh_buat'=>false,
                'boleh_lanjut'=>$alasan === [],
                'percobaan_aktif_id'=>$hasil['percobaan_aktif_id'],
                'tahap'=>$hasil['tahap_berikut'],
                'fase'=>$hasil['fase'],
                'alasan'=>$alasan,
                'status_jadwal'=>$jendela['status'],
                'durasi_detik'=>$durasi*60,
            ];
        }
        if (!$jendela['boleh_mulai']) $alasan[]=$jendela['pesan'];
        if ($jenis === 'susulan' && !$ditetapkan) {
            $alasan[]='Siswa tidak tercatat sebagai peserta susulan Admin.';
        }
        // Jadwal remedial untuk paket dapat dibuka serentak, tetapi
        // mesin hanya membuat attempt pada siswa yang telah lulus gate.
        $bolehTahap = match ($hasil['fase']) {
            'belum_mulai' => $tahap === 'normal'
                && ($jenis === 'utama' || ($jenis === 'susulan' && $ditetapkan)),
            'butuh_susulan_normal' => $jenis === 'susulan' && $tahap === 'normal' && $ditetapkan,
            'menunggu_remedial' => $jenis === 'remedial' && $tahap === 'remedial',
            'butuh_susulan_remedial' => $jenis === 'susulan' && $tahap === 'remedial' && $ditetapkan,
            default => false,
        };
        if (!$bolehTahap) {
            $alasan[]='Tahap atau jenis jadwal tidak sesuai dengan kesempatan sah siswa.';
        }
        return [
            'boleh_buat'=>$alasan === [],
            'boleh_lanjut'=>false,
            'percobaan_aktif_id'=>null,
            'tahap'=>$hasil['tahap_berikut'],
            'fase'=>$hasil['fase'],
            'alasan'=>$alasan,
            'status_jadwal'=>$jendela['status'],
            'durasi_detik'=>$durasi*60,
        ];
    }
}
