<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * K2B: administrasi akun tertutup berdasarkan tipe, dengan audit dan reset sandi per akun.
 * Tidak menggunakan ref_mapel.guru_id dan tidak menghapus data.
 */
final class PenggunaAdminService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public static function validasiJenis(string $jenis): bool
    {
        return in_array($jenis, ['pengelola', 'siswa'], true);
    }

    /** @param list<string> $peran */
    public static function jenisPeranSesuai(string $jenis, array $peran): bool
    {
        if (! self::validasiJenis($jenis) || ! AuthService::peranValid($peran)) {
            return false;
        }

        return $jenis === 'siswa'
            ? $peran === ['siswa']
            : ! in_array('siswa', $peran, true);
    }

    /** Sandi sementara menggunakan CSPRNG, tidak pernah disimpan plaintext. */
    public static function buatSandiSementara(): string
    {
        return bin2hex(random_bytes(16));
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

    private function pastikanJenis(string $jenis): void
    {
        if (! self::validasiJenis($jenis)) {
            throw new DomainException('Jenis pengguna tidak dikenali.');
        }
    }

    private function basisDaftar(string $jenis, string $cari, string $status, ?int $genFilter): BaseBuilder
    {
        $builder = $this->db->table('pengguna p')
            ->join('pengguna_peran pp', 'pp.pengguna_id = p.id');

        if ($jenis === 'siswa') {
            $builder->where('pp.peran', 'siswa');
            if ($genFilter !== null) {
                $builder->join('siswa sf', 'sf.pengguna_id = p.id', 'left');
                if ($genFilter === 0) {
                    $builder->where('sf.angkatan_id IS NULL', null, false);
                } else {
                    $builder->where('sf.angkatan_id', $genFilter);
                }
            }
        } else {
            $builder->whereIn('pp.peran', ['admin', 'guru', 'pimpinan']);
        }

        if ($cari !== '') {
            $builder->groupStart()->like('p.nama', $cari)
                ->orLike('p.username', $cari)->groupEnd();
        }
        if (in_array($status, ['aktif', 'nonaktif'], true)) {
            $builder->where('p.status', $status);
        }

        return $builder;
    }

    /** @return array{pengguna:array, total:int, halaman:int, perHalaman:int, cari:string, status:string, gen:array, genFilter:?int} */
    public function daftar(string $jenis, string $cari = '', string $status = '', int $halaman = 1, ?int $genFilter = null): array
    {
        $this->admin();
        $this->pastikanJenis($jenis);
        $cari = mb_substr(trim($cari), 0, 80);
        $halaman = max(1, $halaman);
        $perHalaman = 25;

        $hitungan = $this->basisDaftar($jenis, $cari, $status, $genFilter)
            ->select('COUNT(DISTINCT p.id) AS total', false)->get()->getRowArray();
        $total = (int) ($hitungan['total'] ?? 0);
        $halaman = min($halaman, max(1, (int) ceil($total / $perHalaman)));

        $builder = $this->basisDaftar($jenis, $cari, $status, $genFilter)
            ->distinct()->select('p.id,p.nama,p.username,p.status,p.wajib_ganti_sandi');

        if ($jenis === 'siswa') {
            $builder->join('siswa s', 's.pengguna_id = p.id', 'left')
                ->join('ref_angkatan a', 'a.id = s.angkatan_id', 'left')
                ->select('s.semester_mulai,s.angkatan_id,a.nama AS gen_nama');
        }

        $pengguna = $builder->orderBy('p.nama', 'ASC')->orderBy('p.id', 'ASC')
            ->limit($perHalaman, ($halaman - 1) * $perHalaman)->get()->getResultArray();

        if ($jenis === 'pengelola' && $pengguna !== []) {
            $id = array_map(static fn (array $row): int => (int) $row['id'], $pengguna);
            $rows = $this->db->table('pengguna_peran')->select('pengguna_id,peran')
                ->whereIn('pengguna_id', $id)->orderBy('peran')->get()->getResultArray();
            $peran = [];
            foreach ($rows as $row) {
                $peran[(int) $row['pengguna_id']][] = $row['peran'];
            }
            foreach ($pengguna as &$item) {
                $item['daftar_peran'] = $peran[(int) $item['id']] ?? [];
            }
            unset($item);
        }

        return [
            'pengguna' => $pengguna,
            'total' => $total,
            'halaman' => $halaman,
            'perHalaman' => $perHalaman,
            'cari' => $cari,
            'status' => $status,
            'genFilter' => $genFilter,
            'gen' => $jenis === 'siswa' ? $this->db->table('ref_angkatan')
                ->select('id,kode,nama,semester_mulai')->orderBy('id', 'DESC')
                ->get()->getResultArray() : [],
        ];
    }

    /**
     * @param list<string> $peran
     * @return int ID akun baru
     */
    public function buat(string $jenis, string $username, string $nama, string $sandi, array $peran,
        ?int $angkatanId = null, ?int $semesterMulai = null): int
    {
        $admin = $this->admin();
        $this->pastikanJenis($jenis);
        $username = trim($username);
        $nama = trim($nama);
        $peran = array_values(array_unique($peran));

        if (! self::jenisPeranSesuai($jenis, $peran)
            || mb_strlen($nama) < 3 || mb_strlen($nama) > 150
            || strlen($sandi) < 8 || strlen($sandi) > 200
            || ($jenis === 'siswa' && ! preg_match('/^[0-9]{10}$/D', $username))
            || ($jenis === 'pengelola' && ! preg_match('/^[A-Za-z0-9_.-]{3,80}$/D', $username))) {
            throw new DomainException('Periksa NISN/username, nama, sandi (minimal 8 karakter) dan peran.');
        }

        if ($jenis === 'siswa' && ($angkatanId === null || $angkatanId < 1
            || ($semesterMulai !== null && ($semesterMulai < 1 || $semesterMulai > 6)))) {
            throw new DomainException('Pilih Gen dan semester mulai yang valid.');
        }

        $this->db->transBegin();
        try {
            if ($this->db->table('pengguna')->where('username', $username)->countAllResults() > 0) {
                throw new DomainException('Username/NISN sudah digunakan.');
            }

            if ($jenis === 'siswa' && $this->db->table('ref_angkatan')
                ->where('id', $angkatanId)->countAllResults() !== 1) {
                throw new DomainException('Gen tidak ditemukan.');
            }

            $this->db->table('pengguna')->insert([
                'username' => $username, 'nama' => $nama,
                'sandi_hash' => password_hash($sandi, PASSWORD_DEFAULT),
                'status' => 'aktif', 'wajib_ganti_sandi' => 1,
            ]);
            $id = (int) $this->db->insertID();

            foreach ($peran as $role) {
                $this->db->table('pengguna_peran')->insert([
                    'pengguna_id' => $id, 'peran' => $role,
                ]);
            }

            if ($jenis === 'siswa') {
                $waktu = gmdate('Y-m-d H:i:s');
                $this->db->table('siswa')->insert([
                    'pengguna_id' => $id, 'nisn' => $username, 'angkatan_id' => $angkatanId,
                    'semester_mulai' => $semesterMulai,
                    'dibuat_pada' => $waktu, 'diperbarui_pada' => $waktu,
                ]);
            }

            $this->catat((int) $admin['id'], 'akun_dibuat',
                'Akun ' . $jenis . ' ID ' . $id . ' dibuat.');
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Akun gagal disimpan.');
            }
            $this->db->transCommit();

            return $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public function target(string $jenis, int $id): array
    {
        $this->admin();
        $this->pastikanJenis($jenis);
        if ($id < 1) {
            throw new DomainException('Akun tidak ditemukan.');
        }
        $akun = $this->db->table('pengguna')->select('id,nama,username,status')
            ->where('id', $id)->get()->getRowArray();
        if ($akun === null) {
            throw new DomainException('Akun tidak ditemukan.');
        }

        $peran = (new AuthService($this->db))->daftarPeran($id);
        if (! self::jenisPeranSesuai($jenis, $peran)) {
            throw new DomainException('Akun tidak termasuk jenis pengguna yang dipilih.');
        }

        return $akun;
    }

    public function resetSandi(string $jenis, int $id): string
    {
        $admin = $this->admin();
        if ((int) $admin['id'] === $id) {
            throw new DomainException('Gunakan menu Ganti Sandi untuk akun Admin sendiri.');
        }

        $this->db->transBegin();
        try {
            $target = $this->db->query(
                'SELECT id,nama,username,versi_sesi FROM pengguna WHERE id = ? FOR UPDATE', [$id]
            )->getRowArray();
            if ($target === null) {
                throw new DomainException('Akun tidak ditemukan.');
            }
            // Pemeriksaan jenis dilakukan ulang di dalam transaksi, bukan hanya pada layar GET.
            $peran = (new AuthService($this->db))->daftarPeran($id);
            if (! self::jenisPeranSesuai($jenis, $peran)) {
                throw new DomainException('Jenis akun yang dipilih tidak sesuai.');
            }

            // Hanya hash yang disimpan; plaintext tidak masuk sesi, URL maupun log audit.
            $sementara = self::buatSandiSementara();
            $this->db->table('pengguna')->where('id', $id)->update([
                'sandi_hash' => password_hash($sementara, PASSWORD_DEFAULT),
                'wajib_ganti_sandi' => 1,
                'versi_sesi' => (int) $target['versi_sesi'] + 1,
                'jumlah_gagal' => 0,
                'terkunci_sampai' => null,
            ]);
            $this->catat((int) $admin['id'], 'reset_sandi_akun',
                'Reset sandi ' . $jenis . ' ID ' . $id . ' oleh Admin.');
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Reset sandi tidak dapat disimpan.');
            }

            $this->db->transCommit();
            return $sementara;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Siswa K1 lama boleh diberi Gen hanya bila sebelumnya belum ditempatkan.
     * Penimpaan Gen terisi tidak tersedia sampai aturan perpindahan/progres siap.
     */
    public function tempatkanSiswa(int $akunId, int $genId): void
    {
        $admin = $this->admin();
        if ($akunId < 1 || $genId < 1) {
            throw new DomainException('Pilih siswa dan Gen yang valid.');
        }

        $this->db->transBegin();
        try {
            $target = $this->db->query(
                'SELECT id,angkatan_id FROM siswa WHERE pengguna_id = ? FOR UPDATE', [$akunId]
            )->getRowArray();
            if ($target === null || $target['angkatan_id'] !== null) {
                throw new DomainException('Siswa tidak ditemukan atau sudah memiliki Gen.');
            }
            if ($this->db->table('ref_angkatan')->where('id', $genId)
                ->countAllResults() !== 1) {
                throw new DomainException('Gen tidak ditemukan.');
            }

            $this->db->table('siswa')->where('id', (int) $target['id'])->update([
                'angkatan_id' => $genId,
                'diperbarui_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->catat((int) $admin['id'], 'siswa_gen_ditetapkan',
                'Akun siswa ID ' . $akunId . ' ditempatkan pada Gen ID ' . $genId);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Penempatan Gen gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function catat(int $adminId, string $peristiwa, string $catatan): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $adminId,
            'peran_aktif' => 'admin',
            'peristiwa' => $peristiwa,
            'catatan' => $catatan,
            'alamat_ip' => service('request')->getIPAddress(),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }
}
