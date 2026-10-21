<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * K3A — Referensi kurikulum dan CP resmi v2.3.
 * Rumusan/metadata versi terbit tidak dapat diubah; riwayat tidak dihapus.
 * Admin mengelola referensi, bukan materi/soal/nilai milik Guru.
 */
final class CpReferensiService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private function admin(): int
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'admin'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Hanya Admin aktif dapat mengelola referensi CP.');
        }

        return (int) $akun['id'];
    }

    /** @param callable():mixed $aksi */
    private function transaksi(callable $aksi): mixed
    {
        $this->db->transBegin();
        try {
            $hasil = $aksi();
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi CP gagal disimpan.');
            }
            $this->db->transCommit();
            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function audit(int $admin, string $peristiwa, int $objekId): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $admin,
            'peran_aktif' => 'admin',
            'peristiwa' => $peristiwa,
            'catatan' => 'Referensi CP/kurikulum ID ' . $objekId,
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public static function kurikulumValid(string $nama, string $versi, string $sumber, string $dasar): bool
    {
        return mb_strlen(trim($nama)) >= 3 && mb_strlen(trim($nama)) <= 120
            && mb_strlen(trim($versi)) >= 1 && mb_strlen(trim($versi)) <= 50
            && mb_strlen(trim($sumber)) >= 3 && mb_strlen(trim($sumber)) <= 255
            && mb_strlen(trim($dasar)) >= 3 && mb_strlen(trim($dasar)) <= 255;
    }

    /** @param array<string,string> $data */
    public static function versiValid(array $data): bool
    {
        foreach (['elemen' => [3, 150], 'rumusan' => [20, 15000],
            'otoritas' => [3, 150], 'nomor_dokumen' => [3, 120],
        ] as $kunci => [$min, $max]) {
            if (! isset($data[$kunci]) || mb_strlen(trim($data[$kunci])) < $min
                || mb_strlen(trim($data[$kunci])) > $max) {
                return false;
            }
        }

        $sumber = trim($data['sumber_url'] ?? '');
        if (strlen($sumber) < 10 || strlen($sumber) > 1024
            || filter_var($sumber, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($sumber, PHP_URL_SCHEME)),
                ['https', 'http'], true)) {
            return false;
        }

        $date = trim($data['tanggal_berlaku'] ?? '');
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date) !== 1) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));

        return checkdate($m, $d, $y);
    }

    /** @param array<string,string> $input @return array<string,string> */
    private static function normalVersi(array $input): array
    {
        $fields = ['elemen', 'rumusan', 'otoritas', 'nomor_dokumen', 'sumber_url', 'tanggal_berlaku'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = trim($input[$field] ?? '');
        }
        if (! self::versiValid($data)) {
            throw new DomainException('Lengkapi elemen, rumusan CP (20–15000 karakter), otoritas, dokumen, URL http(s), dan tanggal berlaku yang sah.');
        }
        return $data;
    }

    public static function kodeValid(string $kode): bool
    {
        return preg_match('/^[A-Z0-9._-]{2,40}$/D', strtoupper(trim($kode))) === 1;
    }

    /** @return array{kurikulum:list<array<string,mixed>>,mapel:list<array<string,mixed>>,cp:list<array<string,mixed>>} */
    public function daftar(): array
    {
        $this->admin();
        $kurikulum = $this->db->table('ref_kurikulum')
            ->select('id,nama,versi,sumber,dasar_hukum')
            ->orderBy('id', 'DESC')->get()->getResultArray();
        $mapel = $this->db->table('ref_mapel')
            ->select('id,kode,nama')->orderBy('nama')->get()->getResultArray();
        $cp = $this->db->query(
            "SELECT c.id,c.kode,m.nama AS mapel_nama,k.nama AS kurikulum_nama,
                    k.versi AS kurikulum_versi, f.kode AS fase,
                    a.id AS versi_aktif_id,a.nomor_versi AS versi_aktif,
                    d.id AS versi_draf_id,d.nomor_versi AS versi_draf
             FROM ref_cp c
             INNER JOIN ref_mapel m ON m.id=c.mapel_id
             INNER JOIN ref_kurikulum k ON k.id=c.kurikulum_id
             INNER JOIN ref_fase f ON f.id=c.fase_id
             LEFT JOIN ref_cp_versi a ON a.cp_id=c.id AND a.status='aktif'
             LEFT JOIN ref_cp_versi d ON d.cp_id=c.id AND d.status='draf'
             ORDER BY m.nama,c.kode,c.id"
        )->getResultArray();
        return compact('kurikulum', 'mapel', 'cp');
    }

    /** @return array{cp:array<string,mixed>,versi:list<array<string,mixed>>} */
    public function detail(int $id): array
    {
        $this->admin();
        $cp = $this->db->table('ref_cp c')
            ->select('c.id,c.kode,c.kurikulum_id,c.mapel_id,c.fase_id,m.nama AS mapel_nama,k.nama AS kurikulum_nama,k.versi AS kurikulum_versi')
            ->join('ref_mapel m', 'm.id=c.mapel_id')
            ->join('ref_kurikulum k', 'k.id=c.kurikulum_id')
            ->where('c.id', $id)->get()->getRowArray();
        if ($cp === null) {
            throw new DomainException('Referensi CP tidak ditemukan.');
        }
        $versi = $this->db->table('ref_cp_versi')
            ->select('id,cp_id,nomor_versi,elemen,rumusan,otoritas,nomor_dokumen,sumber_url,tanggal_berlaku,status,dibuat_pada,diterbitkan_pada')
            ->where('cp_id', $id)->orderBy('nomor_versi', 'DESC')
            ->get()->getResultArray();

        return compact('cp', 'versi');
    }

    /** @return array<string,mixed> */
    public function detailVersi(int $versiId): array
    {
        $this->admin();
        $versi = $this->db->table('ref_cp_versi')
            ->select('id,cp_id,nomor_versi,elemen,rumusan,otoritas,nomor_dokumen,sumber_url,tanggal_berlaku,status')
            ->where('id', $versiId)->get()->getRowArray();
        if ($versi === null) {
            throw new DomainException('Versi CP tidak ditemukan.');
        }
        return $versi;
    }

    public function buatKurikulum(string $nama, string $versi, string $sumber, string $dasar): void
    {
        $admin = $this->admin();
        $nama = trim($nama);
        $versi = trim($versi);
        $sumber = trim($sumber);
        $dasar = trim($dasar);

        if (! self::kurikulumValid($nama, $versi, $sumber, $dasar)) {
            throw new DomainException('Isi nama, versi, sumber dan dasar hukum kurikulum dengan panjang yang sesuai.');
        }
        $this->transaksi(function () use ($admin, $nama, $versi, $sumber, $dasar): void {
            if ($this->db->table('ref_kurikulum')
                ->where('nama', $nama)->where('versi', $versi)->countAllResults() !== 0) {
                throw new DomainException('Kurikulum dengan nama dan versi tersebut sudah ada.');
            }
            $this->db->table('ref_kurikulum')->insert([
                'nama' => $nama, 'versi' => $versi, 'sumber' => $sumber,
                'dasar_hukum' => $dasar, 'dibuat_oleh' => $admin,
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit($admin, 'kurikulum_dibuat', (int) $this->db->insertID());
        });
    }

    /** @param array<string,string> $data */
    public function buatCp(int $kurikulumId, int $mapelId, string $kode, array $data): int
    {
        $admin = $this->admin();
        $kode = strtoupper(trim($kode));
        $data = self::normalVersi($data);
        if ($kurikulumId < 1 || $mapelId < 1 || ! self::kodeValid($kode)) {
            throw new DomainException('Pilih kurikulum, mapel, dan kode CP 2–40 karakter.');
        }

        return $this->transaksi(function () use ($admin, $kurikulumId, $mapelId, $kode, $data): int {
            if ($this->db->table('ref_kurikulum')->where('id', $kurikulumId)->countAllResults() !== 1
                || $this->db->table('ref_mapel')->where('id', $mapelId)->countAllResults() !== 1) {
                throw new DomainException('Kurikulum atau mapel tidak ditemukan.');
            }
            $this->db->query('SELECT id FROM ref_fase WHERE id=1 FOR UPDATE');
            if ($this->db->table('ref_cp')->where('kurikulum_id', $kurikulumId)
                ->where('mapel_id', $mapelId)->where('fase_id', 1)
                ->where('kode', $kode)->countAllResults() !== 0) {
                throw new DomainException('Kode CP sudah ada untuk kurikulum, mapel, dan Fase D.');
            }

            $this->db->table('ref_cp')->insert([
                'kurikulum_id' => $kurikulumId, 'mapel_id' => $mapelId,
                'fase_id' => 1, 'kode' => $kode,
                'dibuat_oleh' => $admin, 'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $id = (int) $this->db->insertID();
            $this->simpanDraf($id, 1, $data, $admin);
            $this->audit($admin, 'cp_draf_dibuat', $id);

            return $id;
        });
    }

    /** @param array<string,string> $data */
    private function simpanDraf(int $id, int $nomor, array $data, int $admin): void
    {
        $this->db->table('ref_cp_versi')->insert($data + [
            'cp_id' => $id,
            'nomor_versi' => $nomor,
            'status' => 'draf',
            'dibuat_oleh' => $admin,
            'dibuat_pada' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string,string> $data */
    public function versiBaru(int $cpId, array $data): void
    {
        $admin = $this->admin();
        $data = self::normalVersi($data);
        if ($cpId < 1) {
            throw new DomainException('CP tidak ditemukan.');
        }
        $this->transaksi(function () use ($admin, $cpId, $data): void {
            $cp = $this->db->query('SELECT id FROM ref_cp WHERE id=? FOR UPDATE', [$cpId])->getRowArray();
            if ($cp === null) {
                throw new DomainException('CP tidak ditemukan.');
            }
            if ($this->db->table('ref_cp_versi')->where('cp_id', $cpId)
                ->where('status', 'draf')->countAllResults() !== 0) {
                throw new DomainException('Masih ada draf CP. Selesaikan atau terbitkan draf tersebut dahulu.');
            }
            $last = $this->db->table('ref_cp_versi')->selectMax('nomor_versi', 'maks')
                ->where('cp_id', $cpId)->get()->getRowArray();
            $next = (int) ($last['maks'] ?? 0) + 1;
            if ($next > 65535) {
                throw new DomainException('Batas nomor versi CP tercapai.');
            }
            $this->simpanDraf($cpId, $next, $data, $admin);
            $this->audit($admin, 'cp_versi_draf_dibuat', $cpId);
        });
    }

    /** @param array<string,string> $data */
    public function ubahDraf(int $id, array $data): void
    {
        $admin = $this->admin();
        $data = self::normalVersi($data);
        $this->transaksi(function () use ($admin, $id, $data): void {
            $asal = $this->db->table('ref_cp_versi')->select('cp_id')
                ->where('id', $id)->get()->getRowArray();
            if ($asal === null) {
                throw new DomainException('Draf tidak ditemukan.');
            }
            $this->db->query('SELECT id FROM ref_cp WHERE id=? FOR UPDATE', [(int) $asal['cp_id']]);
            $versi = $this->db->query(
                'SELECT id,status FROM ref_cp_versi WHERE id=? FOR UPDATE', [$id]
            )->getRowArray();
            if ($versi === null || $versi['status'] !== 'draf') {
                throw new DomainException('Versi CP telah terbit atau diarsipkan. Buat draf versi baru.');
            }
            $this->db->table('ref_cp_versi')->where('id', $id)->update($data);
            $this->audit($admin, 'cp_draf_diperbaiki', $id);
        });
    }

    public function terbitkan(int $id): void
    {
        $admin = $this->admin();
        $this->transaksi(function () use ($admin, $id): void {
            $asal = $this->db->table('ref_cp_versi')->select('cp_id')
                ->where('id', $id)->get()->getRowArray();
            if ($asal === null) {
                throw new DomainException('Versi CP tidak ditemukan.');
            }
            $cpId = (int) $asal['cp_id'];
            $this->db->query('SELECT id FROM ref_cp WHERE id=? FOR UPDATE', [$cpId]);
            $versi = $this->db->query(
                'SELECT id,status FROM ref_cp_versi WHERE id=? AND cp_id=? FOR UPDATE',
                [$id, $cpId]
            )->getRowArray();
            if ($versi === null || $versi['status'] !== 'draf') {
                throw new DomainException('Hanya draf CP yang dapat diterbitkan.');
            }

            // Isi versi terbit dan referensi historis tetap; hanya status siklus hidup berubah.
            $this->db->table('ref_cp_versi')->where('cp_id', $cpId)
                ->where('status', 'aktif')->update(['status' => 'diarsipkan']);
            $this->db->table('ref_cp_versi')->where('id', $id)->update([
                'status' => 'aktif', 'diterbitkan_oleh' => $admin,
                'diterbitkan_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit($admin, 'cp_versi_diterbitkan', $id);
        });
    }
}
