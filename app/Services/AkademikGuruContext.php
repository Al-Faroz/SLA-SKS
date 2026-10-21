<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/** Otorisasi dan transaksi bersama alur akademik Guru, bukan created_by. */
final class AkademikGuruContext
{
    public readonly BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public static function buka(?BaseConnection $db = null): self
    {
        return new self($db ?? db_connect());
    }

    /** @return array<string,mixed> */
    public function guru(): array
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'guru'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Aktifkan peran Guru dan pastikan sesi valid.');
        }
        return $akun;
    }

    /** @return array<string,mixed> */
    public function admin(): array
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'admin'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Tindakan ini hanya untuk Admin aktif.');
        }
        return $akun;
    }

    public function periksaGenMapel(int $angkatan, int $mapel): int
    {
        $guru = $this->guru();
        if ($angkatan <= 0 || $mapel <= 0 ||
            $this->db->table('penugasan_guru_gen')
                ->where('angkatan_id', $angkatan)->where('mapel_id', $mapel)
                ->where('guru_id', (int) $guru['id'])->countAllResults() !== 1) {
            throw new DomainException('Anda tidak ditugaskan sebagai Guru aktif pada Gen–Mapel ini.');
        }
        return (int) $guru['id'];
    }

    /** @return array<string,mixed> */
    public function paket(int $id): array
    {
        $paket = $this->db->table('paket_semester')->where('id', $id)->get()->getRowArray();
        if ($paket === null) {
            throw new DomainException('Paket semester tidak ditemukan.');
        }
        $this->periksaGenMapel((int) $paket['angkatan_id'], (int) $paket['mapel_id']);
        return $paket;
    }

    /** @return array<string,mixed> */
    public function bab(int $id, bool $wajibDraf = false): array
    {
        $bab = $this->db->table('bab b')->select('b.*,p.angkatan_id,p.mapel_id,p.kurikulum_id,p.semester,p.tahun_ajaran_id')
            ->join('paket_semester p', 'p.id=b.paket_id')
            ->where('b.id', $id)->get()->getRowArray();
        if ($bab === null) {
            throw new DomainException('BAB tidak ditemukan.');
        }
        $this->periksaGenMapel((int) $bab['angkatan_id'], (int) $bab['mapel_id']);
        if ($wajibDraf && $bab['status'] !== 'draf') {
            throw new DomainException('BAB Siap terkunci permanen dari editor, impor dan perubahan biasa.');
        }
        return $bab;
    }

    /** @return list<array<string,mixed>> */
    public function daftarGenMapel(): array
    {
        $guru = $this->guru();
        return $this->db->table('penugasan_guru_gen pg')
            ->select('pg.angkatan_id,pg.mapel_id,a.nama AS gen_nama,a.kode AS gen_kode,m.nama AS mapel_nama')
            ->join('ref_angkatan a', 'a.id=pg.angkatan_id')
            ->join('ref_mapel m', 'm.id=pg.mapel_id')
            ->where('pg.guru_id', (int) $guru['id'])
            ->orderBy('a.nama')->orderBy('m.nama')->get()->getResultArray();
    }

    public function catat(int $id, string $role, string $aksi, string $objek, int $objekId): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $id,
            'peran_aktif' => $role,
            'peristiwa' => $aksi,
            'catatan' => $objek . ' ID ' . $objekId,
            'alamat_ip' => service('request')->getIPAddress(),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @template T @param callable():T $kerja @return T */
    public function transaksi(callable $kerja): mixed
    {
        $this->db->transBegin();
        try {
            $hasil = $kerja();
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi akademik gagal. Tidak ada perubahan yang boleh diterapkan sebagian.');
            }
            $this->db->transCommit();
            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
