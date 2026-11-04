<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Ringkasan v2.5 per CP operasional, bukan per ref_cp (format v2.3).
 * Menjaga tautan elemen ke identitas/riwayat ref_cp yang sudah ada.
 */
final class CpOperasionalService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Ketentuan khusus SKI KBC Fase D (keputusan pemilik 28-10-2026):
     * HANYA referensi KBC25-SKI-D-PK / Pemahaman Konsep boleh aktif.
     * Mapel lain boleh mempunyai lebih dari satu elemen.
     */
    public static function bolehTerbit(string $kodeMapel, string $kodeFase,
        string $namaKurikulum, string $kodeCp, string $elemen): bool
    {
        if (strtoupper(trim($kodeMapel)) !== 'SKI' || strtoupper(trim($kodeFase)) !== 'D'
            || trim($namaKurikulum) !== 'Kurikulum Berbasis Cinta (KBC)') {
            return true;
        }
        return trim($kodeCp) === 'KBC25-SKI-D-PK'
            && trim($elemen) === 'Pemahaman Konsep';
    }

    /** @return list<array<string,mixed>> */
    public function daftar(): array
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'admin'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Admin yang dapat melihat CP operasional.');
        }
        if (! $this->db->tableExists('cp_mapel')
            || ! $this->db->tableExists('cp_mapel_elemen')) {
            throw new DomainException('Database CP operasional v4 belum terpasang.');
        }

        $data = $this->db->table('cp_mapel cm')
            ->select('cm.id,cm.kurikulum_id,cm.mapel_id,cm.fase_id,'
                . 'm.kode AS kode_mapel,m.nama AS nama_mapel,'
                . 'k.nama AS nama_kurikulum,k.versi AS versi_kurikulum,'
                . 'f.kode AS kode_fase,'
                . 'r.id AS ref_cp_id,r.kode AS kode_cp,'
                . 'v.id AS versi_id,v.elemen,v.nomor_versi,v.status')
            ->join('ref_mapel m', 'm.id=cm.mapel_id')
            ->join('ref_kurikulum k', 'k.id=cm.kurikulum_id')
            ->join('ref_fase f', 'f.id=cm.fase_id')
            ->join('cp_mapel_elemen ce', 'ce.cp_mapel_id=cm.id', 'left')
            ->join('ref_cp_versi v', 'v.id=ce.cp_versi_id', 'left')
            ->join('ref_cp r', 'r.id=v.cp_id', 'left')
            ->orderBy('m.nama', 'ASC')
            ->orderBy('cm.id', 'ASC')
            ->orderBy('v.elemen', 'ASC')
            ->orderBy('v.nomor_versi', 'DESC')
            ->limit(5000)->get()->getResultArray();
        $hasil = [];
        foreach ($data as $row) {
            $id = (int) $row['id'];
            if (! isset($hasil[$id])) {
                $hasil[$id] = [
                    'id' => $id,
                    'kurikulum' => $row['nama_kurikulum'],
                    'versi_kurikulum' => $row['versi_kurikulum'],
                    'kode_mapel' => $row['kode_mapel'],
                    'nama_mapel' => $row['nama_mapel'],
                    'fase' => $row['kode_fase'],
                    'elemen_aktif' => [],
                ];
            }
            if ($row['status'] === 'aktif' && $row['versi_id'] !== null) {
                $hasil[$id]['elemen_aktif'][] = [
                    'nama' => $row['elemen'],
                    'kode' => $row['kode_cp'],
                    'referensi_id' => (int) $row['ref_cp_id'],
                    'versi_id' => (int) $row['versi_id'],
                ];
            }
        }
        return array_values($hasil);
    }
}
