<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PenggunaModel;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;

/**
 * Semua keputusan identitas dan izin dilakukan ulang di server.
 * Waktu transaksi dan penguncian dalam UTC.
 */
final class AuthService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** @param list<string> $peran */
    public static function peranValid(array $peran): bool
    {
        $peran = array_values(array_unique($peran));
        sort($peran);

        return in_array($peran, [
            ['admin'], ['guru'], ['pimpinan'], ['siswa'],
            ['admin', 'guru'], ['admin', 'pimpinan'], ['guru', 'pimpinan'],
        ], true);
    }

    private function waktu(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    private function catat(string $peristiwa, ?int $penggunaId, ?string $peran = null): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $penggunaId,
            'peran_aktif' => $peran,
            'peristiwa' => $peristiwa,
            'waktu' => $this->waktu(),
            'alamat_ip' => service('request')->getIPAddress(),
        ]);
    }

    /** @return array<string,mixed>|null */
    public function penggunaSaatIni(): ?array
    {
        $sesi = session();
        $id = (int) $sesi->get('auth_id');

        if ($id < 1) {
            return null;
        }

        $aktivitas = (int) $sesi->get('auth_aktivitas');
        if ($aktivitas <= 0 || time() - $aktivitas > 7200) {
            $sesi->destroy();
            return null;
        }

        $pengguna = (new PenggunaModel())->find($id);
        if (! $pengguna
            || $pengguna['status'] !== 'aktif'
            || (int) $pengguna['versi_sesi'] !== (int) $sesi->get('auth_versi')) {
            $sesi->destroy();
            return null;
        }

        $peran = (string) $sesi->get('peran_aktif');
        $tersedia = $this->daftarPeran($id);
        if (! in_array($peran, $tersedia, true)) {
            $sesi->destroy();
            return null;
        }

        $sesi->set('auth_aktivitas', time());
        $pengguna['peran_aktif'] = $peran;
        $pengguna['daftar_peran'] = $tersedia;

        return $pengguna;
    }

    /** @return list<string> */
    public function daftarPeran(int $penggunaId): array
    {
        $baris = $this->db->table('pengguna_peran')
            ->select('peran')->where('pengguna_id', $penggunaId)
            ->orderBy('peran', 'ASC')->get()->getResultArray();

        return array_column($baris, 'peran');
    }

    public function izinkan(string $peran): bool
    {
        $pengguna = $this->penggunaSaatIni();

        return $pengguna !== null
            && (int) $pengguna['wajib_ganti_sandi'] === 0
            && $pengguna['peran_aktif'] === $peran;
    }

    private function harusPeran(string $peran): array
    {
        $pengguna = $this->penggunaSaatIni();
        if ($pengguna === null
            || (int) $pengguna['wajib_ganti_sandi'] !== 0
            || $pengguna['peran_aktif'] !== $peran) {
            throw new DomainException('Anda tidak memiliki izin untuk tindakan tersebut.');
        }

        return $pengguna;
    }

    /** Balasan generik: keberadaan username tidak pernah dibocorkan. */
    public function masuk(string $username, string $sandi, string $alamatIp): bool
    {
        $username = trim($username);
        if ($username === '' || strlen($username) > 80 || $sandi === '' || strlen($sandi) > 200) {
            return false;
        }

        $kunci = hash('sha256', mb_strtolower($username, 'UTF-8') . '|' . $alamatIp);
        $sekarang = $this->waktu();
        $batas = $this->db->table('batas_login')->where('kunci', $kunci)->get()->getRowArray();

        if ($batas && $batas['terkunci_sampai'] !== null
            && strtotime($batas['terkunci_sampai'] . ' UTC') > time()) {
            return false;
        }

        $pengguna = (new PenggunaModel())->where('username', $username)->first();
        $terkunci = $pengguna && $pengguna['terkunci_sampai'] !== null
            && strtotime($pengguna['terkunci_sampai'] . ' UTC') > time();

        if (! $terkunci && $pengguna && $pengguna['status'] === 'aktif'
            && password_verify($sandi, $pengguna['sandi_hash'])) {
            $peran = $this->daftarPeran((int) $pengguna['id']);
            if ($peran !== [] && self::peranValid($peran)) {
                $this->db->transBegin();
                try {
                    $this->db->table('pengguna')->where('id', $pengguna['id'])->update([
                        'jumlah_gagal' => 0,
                        'terkunci_sampai' => null,
                        'login_terakhir' => $sekarang,
                    ]);
                    $this->db->table('batas_login')->where('kunci', $kunci)->delete();
                    $this->catat('login_berhasil', (int) $pengguna['id'], $peran[0]);
                    if ($this->db->transStatus() === false) {
                        throw new RuntimeException('Gagal menyimpan sesi login.');
                    }
                    $this->db->transCommit();
                } catch (\Throwable $e) {
                    $this->db->transRollback();
                    throw $e;
                }

                $sesi = session();
                $sesi->regenerate(true);
                $sesi->set([
                    'auth_id' => (int) $pengguna['id'],
                    'peran_aktif' => $peran[0],
                    'auth_versi' => (int) $pengguna['versi_sesi'],
                    'auth_aktivitas' => time(),
                ]);

                return true;
            }
        } else {
            // Kurangi perbedaan waktu respons untuk nama akun yang tidak ditemukan.
            if (! $pengguna || $pengguna['status'] !== 'aktif') {
                password_verify($sandi, password_hash('akun-tidak-ada', PASSWORD_DEFAULT));
            }
        }

        $ulang = ! $batas || strtotime($batas['mulai_gagal'] . ' UTC') <= time() - 300;
        $jumlah = min(5, ($ulang ? 0 : (int) $batas['jumlah_gagal']) + 1);
        $sampai = $jumlah >= 5 ? gmdate('Y-m-d H:i:s', time() + 300) : null;

        $this->db->transBegin();
        try {
            $dataBatas = [
                'jumlah_gagal' => $jumlah,
                'mulai_gagal' => $ulang ? $sekarang : $batas['mulai_gagal'],
                'terkunci_sampai' => $sampai,
            ];
            if ($batas) {
                $this->db->table('batas_login')->where('kunci', $kunci)->update($dataBatas);
            } else {
                $dataBatas['kunci'] = $kunci;
                $this->db->table('batas_login')->insert($dataBatas);
            }

            if ($pengguna && $pengguna['status'] === 'aktif' && ! $terkunci) {
                $gagal = $pengguna['terkunci_sampai'] !== null
                    && strtotime($pengguna['terkunci_sampai'] . ' UTC') <= time()
                    ? 1 : min(5, (int) $pengguna['jumlah_gagal'] + 1);
                $this->db->table('pengguna')->where('id', $pengguna['id'])->update([
                    'jumlah_gagal' => $gagal,
                    'terkunci_sampai' => $gagal >= 5 ? gmdate('Y-m-d H:i:s', time() + 300) : null,
                ]);
            }
            $this->catat('login_gagal', $pengguna ? (int) $pengguna['id'] : null);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Gagal mencatat batas login.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return false;
    }

    public function gantiSandi(string $lama, string $baru): void
    {
        $pengguna = $this->penggunaSaatIni();
        if ($pengguna === null) {
            throw new DomainException('Sesi telah berakhir. Silakan masuk ulang.');
        }
        if (strlen($baru) < 8 || strlen($baru) > 200) {
            throw new DomainException('Sandi baru minimal 8 dan maksimal 200 karakter.');
        }
        if (! password_verify($lama, $pengguna['sandi_hash'])) {
            throw new DomainException('Sandi saat ini tidak sesuai.');
        }
        if (password_verify($baru, $pengguna['sandi_hash'])) {
            throw new DomainException('Sandi baru harus berbeda dari sandi sebelumnya.');
        }

        $versi = (int) $pengguna['versi_sesi'] + 1;
        $this->db->transBegin();
        try {
            $this->db->table('pengguna')->where('id', $pengguna['id'])->update([
                'sandi_hash' => password_hash($baru, PASSWORD_DEFAULT),
                'wajib_ganti_sandi' => 0,
                'versi_sesi' => $versi,
                'jumlah_gagal' => 0,
                'terkunci_sampai' => null,
            ]);
            $this->catat('ganti_sandi', (int) $pengguna['id'], $pengguna['peran_aktif']);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Sandi gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        session()->regenerate(true);
        session()->set('auth_versi', $versi);
        session()->set('auth_aktivitas', time());
    }

    public function gantiPeran(string $peran): void
    {
        $pengguna = $this->penggunaSaatIni();
        if ($pengguna === null || (int) $pengguna['wajib_ganti_sandi'] !== 0
            || ! in_array($peran, $pengguna['daftar_peran'], true)) {
            throw new DomainException('Peran yang dipilih tidak diizinkan.');
        }

        session()->regenerate(true);
        session()->set('peran_aktif', $peran);
        session()->set('auth_aktivitas', time());
        $this->catat('ganti_peran', (int) $pengguna['id'], $peran);
    }

    public function keluar(): void
    {
        $pengguna = $this->penggunaSaatIni();
        if ($pengguna) {
            $this->catat('logout', (int) $pengguna['id'], $pengguna['peran_aktif']);
        }
        session()->destroy();
    }

    /** Pembuatan Admin pertama hanya di terminal; tidak ada endpoint browser setup. */
    public function buatAdminAwal(string $username, string $nama, string $sandi): void
    {
        if (! is_cli()) {
            throw new DomainException('Inisialisasi hanya melalui terminal.');
        }
        if (! preg_match('/^[a-zA-Z0-9_.-]{3,80}$/D', $username)
            || mb_strlen($nama) < 3 || mb_strlen($nama) > 150 || strlen($sandi) < 20) {
            throw new DomainException('Identitas atau sandi awal tidak memenuhi syarat.');
        }
        $this->db->transBegin();
        try {
            if ($this->db->table('pengguna')->countAllResults() > 0) {
                throw new DomainException('Pengguna sudah tersedia. Inisialisasi dibatasi satu kali.');
            }
            $this->db->table('pengguna')->insert([
                'username' => $username,
                'nama' => $nama,
                'sandi_hash' => password_hash($sandi, PASSWORD_DEFAULT),
                'status' => 'aktif',
                'wajib_ganti_sandi' => 1,
            ]);
            $id = (int) $this->db->insertID();
            $this->db->table('pengguna_peran')->insert(['pengguna_id' => $id, 'peran' => 'admin']);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $id,
                'peran_aktif' => 'admin',
                'peristiwa' => 'admin_awal_dibuat',
                'waktu' => $this->waktu(),
            ]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Inisialisasi tidak berhasil.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function daftarPengguna(): array
    {
        $this->harusPeran('admin');

        $daftar = (new PenggunaModel())->select('id, username, nama, status, wajib_ganti_sandi')
            ->orderBy('nama', 'ASC')->findAll(200);
        foreach ($daftar as &$item) {
            $item['daftar_peran'] = $this->daftarPeran((int) $item['id']);
        }
        unset($item);

        return $daftar;
    }

    /** @param list<string> $peran */
    public function buatPengguna(string $username, string $nama, string $sandi, array $peran): void
    {
        $admin = $this->harusPeran('admin');
        $username = trim($username);
        $nama = trim($nama);
        if (! preg_match('/^[a-zA-Z0-9_.-]{3,80}$/D', $username)
            || mb_strlen($nama) < 3 || mb_strlen($nama) > 150
            || strlen($sandi) < 8 || strlen($sandi) > 200
            || ! self::peranValid($peran)
            || (in_array('siswa', $peran, true) && ! preg_match('/^[0-9]{10}$/D', $username))) {
            throw new DomainException('Periksa username, nama, sandi, dan kombinasi peran. Siswa harus menggunakan NISN 10 angka.');
        }

        $this->db->transBegin();
        try {
            if ($this->db->table('pengguna')->where('username', $username)->countAllResults() !== 0) {
                throw new DomainException('Username sudah digunakan.');
            }
            $this->db->table('pengguna')->insert([
                'username' => $username,
                'nama' => $nama,
                'sandi_hash' => password_hash($sandi, PASSWORD_DEFAULT),
                'wajib_ganti_sandi' => 1,
                'status' => 'aktif',
            ]);
            $id = (int) $this->db->insertID();
            foreach (array_unique($peran) as $role) {
                $this->db->table('pengguna_peran')->insert(['pengguna_id' => $id, 'peran' => $role]);
            }
            $this->catat('akun_dibuat', (int) $admin['id'], 'admin');
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Gagal membuat akun.');
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
