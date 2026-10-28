<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * CP v2.5: identitas operasional tunggal per Kurikulum–Mapel–Fase.
 * Elemen/sumber dan versi historis tetap disimpan di ref_cp/ref_cp_versi.
 */
final class CpMapelOperasionalService
{
    private BaseConnection $db;

    /** @var callable():?array */
    private $akunAktif;

    /** @param (callable():?array)|null $akunAktif */
    public function __construct(?BaseConnection $db = null, ?callable $akunAktif = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunAktif = $akunAktif ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    /** @return array{cp:array<string,mixed>,elemen:list<array<string,mixed>>} */
    public function detail(int $id): array
    {
        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'admin'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Admin aktif boleh mengelola CP.');
        }
        if ($id < 1) {
            throw new DomainException('Identitas CP operasional tidak valid.');
        }
        $cp = $this->db->table('cp_mapel c')
            ->select('c.id,c.kurikulum_id,c.mapel_id,c.fase_id,'
                . 'm.kode AS kode_mapel,m.nama AS mapel_nama,'
                . 'k.nama AS kurikulum_nama,k.versi AS kurikulum_versi,f.kode AS fase')
            ->join('ref_mapel m', 'm.id=c.mapel_id')
            ->join('ref_kurikulum k', 'k.id=c.kurikulum_id')
            ->join('ref_fase f', 'f.id=c.fase_id')
            ->where('c.id', $id)->where('f.kode', 'D')
            ->get()->getRowArray();
        if ($cp === null) {
            throw new DomainException('CP operasional Fase D tidak ditemukan.');
        }
        $elemen = $this->db->table('ref_cp rc')
            ->select("rc.id AS ref_cp_id,rc.kode,"
                . "a.id AS versi_aktif_id,a.nomor_versi AS versi_aktif,"
                . "a.elemen AS elemen_aktif,a.rumusan AS rumusan_aktif,"
                . "d.id AS versi_draf_id,d.nomor_versi AS versi_draf,"
                . "d.elemen AS elemen_draf,"
                . "ce.id AS id_tautan_aktif", false)
            ->join('ref_cp_versi a', "a.cp_id=rc.id AND a.status='aktif'", 'left')
            ->join('ref_cp_versi d', "d.cp_id=rc.id AND d.status='draf'", 'left')
            ->join('cp_mapel_elemen ce',
                'ce.cp_mapel_id=' . (int) $cp['id'] . ' AND ce.cp_versi_id=a.id', 'left')
            ->where('rc.kurikulum_id', $cp['kurikulum_id'])
            ->where('rc.mapel_id', $cp['mapel_id'])
            ->where('rc.fase_id', $cp['fase_id'])
            ->orderBy('rc.kode', 'ASC')
            ->get()->getResultArray();
        return ['cp' => $cp, 'elemen' => $elemen];
    }
}
