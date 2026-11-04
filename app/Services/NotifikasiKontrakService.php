<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * A13-6, tahap pra-database: definisi kejadian dan kunci idempoten MURNI.
 *
 * Bukan layanan penyimpanan/pengiriman. Service penerima, audit, hak tautan,
 * outbox dan status dibaca memerlukan audit dump pasca V4-0012 lebih dahulu.
 * Jangan memanggil susun() sebagai bukti notifikasi sudah terkirim.
 */
final class NotifikasiKontrakService
{
    /**
     * Pemetaan jenis yang sudah disebut Master 07/08/09/12/16.
     * Konten generik tidak memuat NISN, nama, nilai, kunci soal atau alasan privat.
     * @var array<string,array{peran:list<string>,judul:string,pesan:string}>
     */
    private const PERISTIWA = [
        'nilai_resume_final' => [
            'peran' => ['siswa'],
            'judul' => 'Penilaian Resume selesai',
            'pesan' => 'Periksa hasil tugas Resume melalui Laporan Akademik.',
        ],
        'nilai_hots_final' => [
            'peran' => ['siswa'],
            'judul' => 'Penilaian HOTS selesai',
            'pesan' => 'Periksa hasil tugas HOTS melalui Laporan Akademik.',
        ],
        'permintaan_akses_ulang' => [
            'peran' => ['guru'],
            'judul' => 'Permintaan akses ulang ujian',
            'pesan' => 'Ada permintaan akses ulang yang perlu diperiksa sesuai penugasan Gen–Mapel.',
        ],
        'izin_akses_ulang' => [
            'peran' => ['siswa'],
            'judul' => 'Akses ulang ujian diizinkan',
            'pesan' => 'Periksa TP terkait untuk melanjutkan tahap ujian yang belum selesai.',
        ],
        'pra_semester_dibatalkan' => [
            'peran' => ['siswa'],
            'judul' => 'Status ujian Pra-Semester',
            'pesan' => 'Periksa status ujian dan informasi jadwal susulan melalui halaman Pra-Semester.',
        ],
        'tp_siap_tersedia' => [
            'peran' => ['siswa'],
            'judul' => 'TP tersedia',
            'pesan' => 'Periksa Pembelajaran untuk mengetahui TP yang dapat diakses.',
        ],
        'koreksi_nilai_diterapkan' => [
            'peran' => ['siswa'],
            'judul' => 'Nilai akademik diperbarui',
            'pesan' => 'Hasil koreksi nilai yang sah dapat dilihat melalui Laporan Akademik.',
        ],
        'sertifikat_awal_terbit' => [
            'peran' => ['siswa'],
            'judul' => 'Sertifikat diterbitkan',
            'pesan' => 'Sertifikat yang berlaku tersedia pada menu Sertifikat.',
        ],
        'sertifikat_pengganti_terbit' => [
            'peran' => ['siswa'],
            'judul' => 'Versi sertifikat diperbarui',
            'pesan' => 'Buka menu Sertifikat untuk melihat versi yang berlaku.',
        ],
        'permohonan_koreksi_nilai' => [
            'peran' => ['admin'],
            'judul' => 'Permohonan koreksi nilai',
            'pesan' => 'Ada permohonan koreksi akademik yang perlu ditinjau sesuai hak Admin.',
        ],
        'keputusan_koreksi_nilai' => [
            'peran' => ['guru'],
            'judul' => 'Keputusan koreksi nilai',
            'pesan' => 'Periksa hasil keputusan permohonan koreksi sesuai hak Anda.',
        ],
    ];

    /** @return list<string> */
    public static function jenis(): array
    {
        return array_keys(self::PERISTIWA);
    }

    /**
     * Validasi teknis payload dari layanan akademik yang kelak sudah
     * mengotorisasi pemilik sumber/penerima; BELUM memverifikasi database.
     *
     * @return array{jenis:string,penerima_id:int,peran_penerima:string,sumber_id:int,versi_sumber:int,judul:string,pesan:string,kunci_idempoten:string}
     */
    public static function susun(
        string $jenis,
        int $penerimaId,
        string $peranPenerima,
        int $sumberId,
        int $versiSumber = 1
    ): array {
        $definisi = self::PERISTIWA[$jenis] ?? null;
        if ($definisi === null) {
            throw new DomainException('Jenis peristiwa notifikasi tidak didukung.');
        }
        if ($penerimaId < 1 || $sumberId < 1 || $versiSumber < 1) {
            throw new DomainException('Referensi peristiwa dan penerima harus valid.');
        }
        if (! in_array($peranPenerima, $definisi['peran'], true)) {
            throw new DomainException('Peran penerima tidak sesuai dengan peristiwa.');
        }

        // Sumber adalah ID kejadian sah (bukan ID bebas yang diketik pengguna).
        // Penerima yang berbeda atau versi kejadian yang berbeda harus unik.
        $kunci = hash('sha256', implode('|', [
            $jenis, (string) $penerimaId, (string) $sumberId, (string) $versiSumber,
        ]));

        return [
            'jenis' => $jenis,
            'penerima_id' => $penerimaId,
            'peran_penerima' => $peranPenerima,
            'sumber_id' => $sumberId,
            'versi_sumber' => $versiSumber,
            'judul' => $definisi['judul'],
            'pesan' => $definisi['pesan'],
            'kunci_idempoten' => $kunci,
        ];
    }
}
