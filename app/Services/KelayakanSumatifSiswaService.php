<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * A3: ringkasan syarat Sumatif siswa, baca-saja.
 *
 * Belum memulai percobaan/menyelesaikan TP. A4 wajib memanggil pemeriksaan
 * server kembali dalam transaksi ketika membuka percobaan Sumatif.
 */
final class KelayakanSumatifSiswaService
{
    private BaseConnection $db;
    private MateriSiswaService $materi;

    /** @param (callable():?array)|null $akun */
    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->materi = new MateriSiswaService($this->db, $akun);
    }

    /**
     * Kebijakan murni, tidak mempercayai flag dari browser.
     * Jalur belajar wajib tepat satu Materi, media selesai, Resume final, HOTS final.
     *
     * @param array{media?:bool,resume?:bool,hots?:bool} $bukti
     * @return array{siap:bool,selesai:bool,jalur:string,bukti:array,pesan:string}
     */
    public static function evaluasi(string $status, int $jumlahMateri, array $bukti = []): array
    {
        $hasil = [
            'siap' => false, 'selesai' => false, 'jalur' => 'terkunci',
            'bukti' => [
                'media' => $bukti['media'] === true,
                'resume' => $bukti['resume'] === true,
                'hots' => $bukti['hots'] === true,
            ],
            'pesan' => 'Selesaikan Diagnostik sah sebelum menuju Sumatif.',
        ];
        if ($status === 'tuntas') {
            $hasil['selesai'] = true;
            $hasil['jalur'] = 'tuntas';
            $hasil['pesan'] = 'TP sudah selesai; tidak membuka percobaan baru.';
            return $hasil;
        }
        if (!in_array($status, ['belajar', 'sumatif'], true)) {
            return $hasil;
        }
        if ($jumlahMateri !== 1) {
            $hasil['pesan'] = 'Snapshot TP belum memiliki tepat satu Materi. Hubungi Guru.';
            return $hasil;
        }
        if ($status === 'sumatif') {
            // A1/A2: status ini ditulis setelah Diagnostik memenuhi KKM.
            $hasil['siap'] = true;
            $hasil['jalur'] = 'diagnostik_lulus';
            $hasil['pesan'] = 'Diagnostik memenuhi KKM: Materi, Resume dan HOTS boleh dilewati. Sumatif tetap wajib.';
            return $hasil;
        }
        $hasil['jalur'] = 'belajar';
        $hasil['siap'] = $hasil['bukti']['media']
            && $hasil['bukti']['resume'] && $hasil['bukti']['hots'];
        $hasil['pesan'] = $hasil['siap']
            ? 'Satu Materi, Resume final dan HOTS final telah lengkap. Syarat menuju Sumatif terpenuhi.'
            : 'Selesaikan Materi, kirim final Resume, lalu kirim final HOTS.';
        return $hasil;
    }

    /**
     * Semua ID diambil dari MateriSiswaService::daftar() yang sudah memeriksa
     * akun siswa, Gen–Mapel, TP, progres dan checksum snapshot terpin.
     */
    public function periksa(int $tpId): array
    {
        $ctx = $this->materi->daftar($tpId);
        $p = $ctx['progres'];
        $items = $ctx['materi'];
        $bukti = [];
        if ((string) $p['status'] === 'belajar' && count($items) === 1) {
            $key = [
                'progres_tp_id' => (int) $p['id'],
                'versi_konten_tp_id' => (int) $p['versi_konten_tp_id'],
                'materi_id' => (int) $items[0]['id'],
            ];
            foreach ([
                'media' => ['progres_materi', 'media_selesai'],
                'resume' => ['resume_materi', 'final'],
                'hots' => ['jawaban_hots_materi', 'final'],
            ] as $nama => [$tabel, $status]) {
                $row = $this->db->table($tabel)->select('status')
                    ->where($key)->get()->getRowArray();
                $bukti[$nama] = ($row['status'] ?? null) === $status;
            }
        }
        return self::evaluasi((string) $p['status'], count($items), $bukti);
    }
}
