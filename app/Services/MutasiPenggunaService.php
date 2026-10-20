<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/** Perubahan akun Admin v2.3: seluruh mutasi beserta audit dalam satu transaksi. */
final class MutasiPenggunaService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private function admin(): array
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'admin'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Hanya Admin aktif yang dapat mengelola akun.');
        }
        return $akun;
    }

    public static function identitasValid(string $jenis, string $username, string $nama, array $peran): bool
    {
        if (! PenggunaAdminService::jenisPeranSesuai($jenis, $peran)
            || mb_strlen(trim($nama)) < 3 || mb_strlen(trim($nama)) > 150) {
            return false;
        }

        return $jenis === 'siswa'
            ? preg_match('/^[0-9]{10}$/D', $username) === 1
            : preg_match('/^[A-Za-z0-9_.-]{3,80}$/D', $username) === 1;
    }

    /** @return list<string> */
    private function peran(int $id): array
    {
        $roles = (new AuthService($this->db))->daftarPeran($id);
        sort($roles);
        return $roles;
    }

    /** @return array<string,mixed> */
    public function detail(string $jenis, int $id): array
    {
        $this->admin();
        $akun = (new PenggunaAdminService($this->db))->target($jenis, $id);
        $akun['daftar_peran'] = $this->peran($id);

        if ($jenis === 'siswa') {
            $siswa = $this->db->table('siswa')->select('id,nisn,angkatan_id,semester_mulai')
                ->where('pengguna_id', $id)->get()->getRowArray();
            if ($siswa === null) {
                throw new DomainException('Biodata siswa belum ditemukan. Periksa patch SQL 0004.');
            }
            $akun['siswa'] = $siswa;
            if ($siswa['angkatan_id'] !== null) {
                $gen = $this->db->table('ref_angkatan')->select('nama')
                    ->where('id', $siswa['angkatan_id'])->get()->getRowArray();
                $akun['nama_gen'] = $gen['nama'] ?? null;
            }
        }
        return $akun;
    }

    /** @param list<string> $lama @param list<string> $baru */
    private function cekHak(array $sebelum, int $adminId, array $lama, array $baru, string $statusBaru): void
    {
        $id = (int) $sebelum['id'];
        if ($id === $adminId && $statusBaru === 'nonaktif') {
            throw new DomainException('Admin tidak boleh menonaktifkan akunnya sendiri.');
        }
        if ($id === $adminId && $lama !== $baru) {
            throw new DomainException('Peran akun Admin sendiri tidak boleh diubah dari menu ini.');
        }

        $lepasAdmin = $sebelum['status'] === 'aktif' && in_array('admin', $lama, true)
            && (! in_array('admin', $baru, true) || $statusBaru === 'nonaktif');
        if ($lepasAdmin) {
            // Mengunci baris seluruh Admin aktif untuk menghindari dua penonaktifan bersamaan.
            $admins = $this->db->query(
                "SELECT p.id FROM pengguna p JOIN pengguna_peran r ON r.pengguna_id=p.id
                 WHERE r.peran='admin' AND p.status='aktif' ORDER BY p.id FOR UPDATE"
            )->getResultArray();
            if (count($admins) <= 1) {
                throw new DomainException('Admin aktif terakhir tidak boleh dinonaktifkan atau kehilangan peran Admin.');
            }
        }

        $lepasGuru = in_array('guru', $lama, true)
            && (! in_array('guru', $baru, true) || $statusBaru === 'nonaktif');
        if ($lepasGuru && $this->db->table('penugasan_guru_gen')
            ->where('guru_id', $id)->countAllResults() > 0) {
            throw new DomainException('Guru masih memiliki penugasan Gen–Mapel. Alihkan penugasannya lebih dahulu.');
        }
    }

    /** @param list<string> $lama @param list<string> $baru */
    private function audit(string $jenis, string $aksi, int $adminId, array $sebelum, string $username,
        string $nama, array $lama, array $baru, string $status): void
    {
        $this->db->table('riwayat_identitas')->insert([
            'pengguna_id' => (int) $sebelum['id'],
            'admin_id' => $adminId,
            'jenis' => $jenis,
            'aksi' => $aksi,
            'username_sebelum' => $sebelum['username'],
            'username_sesudah' => $username,
            'nama_sebelum' => $sebelum['nama'],
            'nama_sesudah' => $nama,
            'peran_sebelum' => implode(',', $lama),
            'peran_sesudah' => implode(',', $baru),
            'status_sebelum' => $sebelum['status'],
            'status_sesudah' => $status,
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);

        // Audit umum tidak memuat NISN/username maupun nilai sandi.
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $adminId,
            'peran_aktif' => 'admin',
            'peristiwa' => $aksi === 'ubah_status' ? 'akun_status_diubah' : 'akun_identitas_diubah',
            'catatan' => 'Perubahan ' . $jenis . ' pengguna ID ' . $sebelum['id'] . '.',
            'alamat_ip' => service('request')->getIPAddress(),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Koreksi NISN + username atomik, mempertahankan ID siswa, Gen, semester dan semua histori.
     * @param list<string> $peranBaru
     */
    public function ubahIdentitas(string $jenis, int $id, string $username, string $nama, array $peranBaru): void
    {
        $admin = $this->admin();
        $username = trim($username);
        $nama = trim($nama);
        $peranBaru = array_values(array_unique($peranBaru));
        sort($peranBaru);

        if (! self::identitasValid($jenis, $username, $nama, $peranBaru)) {
            throw new DomainException('Nama, NISN/username atau kombinasi peran tidak valid.');
        }
        if ($id < 1) {
            throw new DomainException('Akun tidak ditemukan.');
        }

        $this->db->transBegin();
        try {
            $lama = $this->db->query(
                'SELECT id,username,nama,status,versi_sesi FROM pengguna WHERE id=? FOR UPDATE',
                [$id]
            )->getRowArray();
            if ($lama === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            $peranLama = $this->peran($id);
            if (! PenggunaAdminService::jenisPeranSesuai($jenis, $peranLama)) {
                throw new DomainException('Jenis akun tidak sesuai.');
            }
            $this->cekHak($lama, (int) $admin['id'], $peranLama, $peranBaru, $lama['status']);

            if ($this->db->table('pengguna')->where('username', $username)
                ->where('id !=', $id)->countAllResults() > 0) {
                throw new DomainException('Username/NISN telah dipakai akun lain.');
            }

            $siswa = null;
            if ($jenis === 'siswa') {
                $siswa = $this->db->query(
                    'SELECT id,nisn FROM siswa WHERE pengguna_id=? FOR UPDATE', [$id]
                )->getRowArray();
                if ($siswa === null) {
                    throw new DomainException('Biodata siswa tidak ditemukan.');
                }
                if ($this->db->table('siswa')->where('nisn', $username)
                    ->where('pengguna_id !=', $id)->countAllResults() > 0) {
                    throw new DomainException('NISN telah dipakai siswa lain.');
                }
            }

            if ($nama === $lama['nama'] && $username === $lama['username']
                && $peranLama === $peranBaru) {
                throw new DomainException('Tidak ada perubahan identitas untuk disimpan.');
            }

            $this->db->table('pengguna')->where('id', $id)->update([
                'username' => $username, 'nama' => $nama,
                'versi_sesi' => (int) $lama['versi_sesi'] + 1,
            ]);
            if ($siswa !== null && $siswa['nisn'] !== $username) {
                $this->db->table('siswa')->where('id', $siswa['id'])->update([
                    'nisn' => $username, 'diperbarui_pada' => gmdate('Y-m-d H:i:s'),
                ]);
            }
            if ($jenis === 'pengelola' && $peranLama !== $peranBaru) {
                foreach (array_diff($peranLama, $peranBaru) as $role) {
                    $this->db->table('pengguna_peran')
                        ->where('pengguna_id', $id)->where('peran', $role)->delete();
                }
                foreach (array_diff($peranBaru, $peranLama) as $role) {
                    $this->db->table('pengguna_peran')->insert([
                        'pengguna_id' => $id, 'peran' => $role,
                    ]);
                }
            }
            $this->audit($jenis, 'ubah_identitas', (int) $admin['id'], $lama, $username,
                $nama, $peranLama, $peranBaru, $lama['status']);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Perubahan identitas gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function ubahStatus(string $jenis, int $id, string $status): void
    {
        $admin = $this->admin();
        if (! PenggunaAdminService::validasiJenis($jenis) || $id < 1
            || ! in_array($status, ['aktif', 'nonaktif'], true)) {
            throw new DomainException('Akun atau status tidak valid.');
        }

        $this->db->transBegin();
        try {
            $lama = $this->db->query(
                'SELECT id,username,nama,status,versi_sesi FROM pengguna WHERE id=? FOR UPDATE',
                [$id]
            )->getRowArray();
            if ($lama === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            $peran = $this->peran($id);
            if (! PenggunaAdminService::jenisPeranSesuai($jenis, $peran)) {
                throw new DomainException('Jenis akun tidak sesuai.');
            }
            if ($lama['status'] === $status) {
                throw new DomainException('Status akun sudah ' . $status . '.');
            }
            $this->cekHak($lama, (int) $admin['id'], $peran, $peran, $status);
            $this->db->table('pengguna')->where('id', $id)->update([
                'status' => $status,
                'versi_sesi' => (int) $lama['versi_sesi'] + 1,
            ]);
            $this->audit($jenis, 'ubah_status', (int) $admin['id'], $lama,
                $lama['username'], $lama['nama'], $peran, $peran, $status);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Perubahan status gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
