<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Mengembalikan TP Siap yang BELUM PERNAH digunakan siswa ke editor normal.
 * Snapshot sebelumnya tetap disimpan. Publikasi ulang membuat versi berikutnya.
 *
 * Seluruh penulisan diserialkan oleh lock baris TP yang juga dipakai siswa
 * pada ProgresTpSiswaService::mulai().
 */
final class SuntingTpService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** Pengunci server — dipanggil setelah SELECT tp FOR UPDATE. */
    public function pastikanBelumDipakai(int $tpId): void
    {
        if ($tpId < 1) {
            throw new DomainException('TP tidak ditemukan.');
        }
        // progres_tp adalah bukti pertama saat siswa memulai TP; jangan
        // hanya memeriksa ujian selesai atau nilai yang sudah muncul.
        if ($this->db->table('progres_tp')->where('tp_id', $tpId)->countAllResults() > 0) {
            throw new DomainException('TP sudah digunakan siswa. Seluruh kontennya terkunci.');
        }
        // Lindungi aktivitas siswa historis yang mungkin belum mempunyai
        // progres_tp, tanpa mengubah ataupun membersihkan data tersebut.
        foreach (['progres_materi', 'resume_materi', 'tugas_hots_materi'] as $tabel) {
            if ($this->db->tableExists($tabel) && $this->db->fieldExists('materi_id', $tabel)
                && $this->db->table($tabel . ' aktivitas')
                    ->join('materi m', 'm.id=aktivitas.materi_id')
                    ->where('m.tp_id', $tpId)->countAllResults() > 0) {
                throw new DomainException('TP sudah mempunyai aktivitas siswa sehingga terkunci.');
            }
        }
    }

    public function buka(int $tpId): void
    {
        $this->db->transBegin();
        try {
            $tp = $this->db->query(
                'SELECT id,status,struktur_versi FROM tp WHERE id=? FOR UPDATE', [$tpId]
            )->getRowArray();
            if ($tp === null || $tp['status'] !== 'siap') {
                throw new DomainException('Hanya TP Siap yang dapat dibuka untuk disunting.');
            }
            // Hak G1 diperiksa ulang dalam Service setelah lock didapat.
            (new DetailTpService($this->db))->lihat($tpId);
            $this->pastikanBelumDipakai($tpId);

            // Tidak menghapus snapshot lama, file privat, atau bank soal.
            // Hubungan soal dengan snapshot lama dapat dilepas karena belum
            // satu pun siswa memakai TP; snapshot_json tetap utuh.
            $this->db->table('soal')->where('tp_id', $tpId)
                ->update(['versi_konten_tp_id' => null]);
            $this->db->table('tp')->where('id', $tpId)
                ->where('status', 'siap')->where('struktur_versi', $tp['struktur_versi'])
                ->update(['status' => 'draf', 'struktur_versi' => (int) $tp['struktur_versi'] + 1]);
            if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false) {
                throw new RuntimeException('TP berubah. Muat ulang halaman sebelum menyunting.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
