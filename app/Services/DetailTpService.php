<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Rincian TP dan jumlah Materi, hanya untuk Guru dengan penugasan G1 aktif.
 * Data diturunkan dari paket di server, bukan Gen–Mapel yang dikirim lewat URL.
 */
final class DetailTpService
{
    private BaseConnection $db;
    private PetaTpService $peta;

    /** @param (callable():?array)|null $penggunaSaatIni */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->peta = new PetaTpService($this->db, $penggunaSaatIni);
    }

    /**
     * @return array{tp:array<string,mixed>,paket:array<string,mixed>,jumlah_materi:int,materi:list<array<string,mixed>>}
     */
    public function lihat(int $tpId): array
    {
        if ($tpId < 1) {
            throw new DomainException('TP tidak valid.');
        }

        // Identitas paket dibaca server. Cek G1 dilakukan ulang oleh PetaTpService.
        $objek = $this->db->table('tp t')
            ->select('t.id AS tp_id, p.id AS paket_id, p.angkatan_id, p.mapel_id, p.semester')
            ->join('paket_semester p', 'p.id = t.paket_semester_id')
            ->where('t.id', $tpId)
            ->get()
            ->getRowArray();

        if ($objek === null) {
            throw new DomainException('TP tidak ditemukan.');
        }

        $peta = $this->peta->petaSemester(
            (int) $objek['angkatan_id'],
            (int) $objek['mapel_id'],
            (int) $objek['semester']
        );

        $paket = null;
        foreach ($peta['paket'] as $baris) {
            if ((int) $baris['id'] === (int) $objek['paket_id']) {
                $paket = $baris;
                break;
            }
        }

        $tp = null;
        foreach ($peta['tp'] as $baris) {
            if ((int) $baris['id'] === $tpId && (int) $baris['paket_semester_id'] === (int) $objek['paket_id']) {
                $tp = $baris;
                break;
            }
        }

        if ($paket === null || $tp === null) {
            throw new DomainException('TP tidak tersedia untuk penugasan Anda.');
        }

        $materi = $this->db->table('materi')
            ->select('id,urutan,judul,jenis,aktif')
            ->where('tp_id', $tpId)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return ['tp' => $tp, 'paket' => $paket, 'jumlah_materi' => count($materi), 'materi' => $materi];
    }
}
