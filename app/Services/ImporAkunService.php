<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use RuntimeException;
use Throwable;

/** Impor XLSX dua tahap. Pratinjau = metadata saja; konfirmasi = satu transaksi atomik. */
final class ImporAkunService
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
            throw new DomainException('Hanya Admin aktif yang dapat melakukan impor.');
        }
        return $akun;
    }

    private function audit(int $adminId, string $aksi, string $jenis, string $checksum,
        int $baru, int $ubah, int $tolak): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $adminId,
            'peran_aktif' => 'admin',
            'peristiwa' => $aksi,
            'catatan' => $jenis . ' checksum=' . substr($checksum, 0, 16)
                . ' baru=' . $baru . ' ubah=' . $ubah . ' tolak=' . $tolak,
            'alamat_ip' => service('request')->getIPAddress(),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Pembersihan terkontrol atas hash staging 30 menit kedaluwarsa.
     * Metadata audit tetap dipertahankan; berkas XLSX mentah tidak pernah disalin.
     */
    private function bersihkanKedaluwarsa(): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->db->transBegin();
        try {
            $this->db->query(
                "UPDATE impor_akun_baris b JOIN impor_akun i ON b.impor_id=i.id
                 SET b.sandi_hash=NULL
                 WHERE i.status='pratinjau' AND i.kedaluwarsa_pada < ?", [$now]
            );
            $this->db->table('impor_akun')
                ->where('status', 'pratinjau')
                ->where('kedaluwarsa_pada <', $now)
                ->update(['status' => 'kedaluwarsa']);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Pembersihan pratinjau kedaluwarsa gagal.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** @return array{batch:string,baru:int,ubah:int,tetap:int,tolak:int} */
    public function pratinjau(string $jenis, UploadedFile $file): array
    {
        $admin = $this->admin();
        $this->bersihkanKedaluwarsa();
        $isi = AkunImporXlsxParser::parse($file, $jenis);
        $id = bin2hex(random_bytes(16));
        $sha = hash_file('sha256', $file->getTempName());
        if ($sha === false) {
            throw new DomainException('Checksum file tidak dapat dibaca.');
        }

        $genByKode = [];
        if ($jenis === 'siswa') {
            foreach ($this->db->table('ref_angkatan')->select('id,kode')->get()->getResultArray() as $gen) {
                $genByKode[$gen['kode']] = (int) $gen['id'];
            }
        }

        $seen = [];
        $hasil = [];
        $counts = ['baru' => 0, 'ubah' => 0, 'tetap' => 0, 'ditolak' => 0];
        foreach ($isi as $baris) {
            $username = $baris['username'];
            $peran = $baris['peran'];
            $error = $baris['galat'];
            $angkatanId = null;
            $target = null;
            $versi = null;
            $aksi = 'ditolak';
            $hash = null;
            $namaSebelum = null;
            $peranSebelum = null;

            $dupeKey = mb_strtolower($username, 'UTF-8');
            if ($error === '' && isset($seen[$dupeKey])) {
                $error = 'Username/NISN duplikat dalam file (baris ' . $seen[$dupeKey] . ').';
            }
            $seen[$dupeKey] = $seen[$dupeKey] ?? $baris['nomor'];

            if ($error === '' && $jenis === 'siswa') {
                $kodeGen = $baris['kode_gen'];
                if ($kodeGen === '' || ! isset($genByKode[$kodeGen])) {
                    $error = 'kode_gen wajib ada dan harus cocok dengan daftar Gen.';
                } else {
                    $angkatanId = $genByKode[$kodeGen];
                }
            }

            if ($error === '') {
                $akun = $this->db->table('pengguna')->select('id,nama,status,versi_sesi')
                    ->where('username', $username)->get()->getRowArray();
                if ($akun === null) {
                    if (strlen($baris['sandi']) < 8 || strlen($baris['sandi']) > 200) {
                        $error = 'Akun baru memerlukan sandi_awal 8–200 karakter.';
                    } else {
                        $aksi = 'baru';
                        $hash = password_hash($baris['sandi'], PASSWORD_DEFAULT);
                    }
                } else {
                    $namaSebelum = (string) $akun['nama'];
                    $target = (int) $akun['id'];
                    $versi = (int) $akun['versi_sesi'];
                    if ($baris['sandi'] !== '') {
                        $error = 'Akun lama: kosongkan sandi_awal; reset sandi hanya lewat tombol per akun.';
                    } else {
                        $peranLama = (new AuthService($this->db))->daftarPeran($target);
                        sort($peranLama);
                        $peranSebelum = implode(',', $peranLama);
                        if (! PenggunaAdminService::jenisPeranSesuai($jenis, $peranLama)) {
                            $error = 'Username/NISN dipakai akun dengan jenis yang berbeda.';
                        } elseif ($target === (int) $admin['id']) {
                            $error = 'Akun Admin sendiri tidak diperbarui melalui impor.';
                        } elseif ($jenis === 'siswa') {
                            $dataSiswa = $this->db->table('siswa')
                                ->select('angkatan_id,semester_mulai')
                                ->where('pengguna_id', $target)->get()->getRowArray();
                            if ($dataSiswa === null || (int) $dataSiswa['angkatan_id'] !== $angkatanId
                                || ($baris['semester'] !== null
                                    && (int) $dataSiswa['semester_mulai'] !== $baris['semester'])) {
                                $error = 'Gen/semester akun lama berbeda. Perbaiki melalui administrasi siswa.';
                            } else {
                                $aksi = $akun['nama'] === $baris['nama'] ? 'tetap' : 'ubah';
                            }
                        } else {
                            $aksi = ($akun['nama'] === $baris['nama'] && $peranLama === $peran)
                                ? 'tetap' : 'ubah';
                        }
                    }
                }
            }

            if ($error !== '') {
                $aksi = 'ditolak';
                $hash = null;
            }
            $counts[$aksi]++;
            $hasil[] = [
                'impor_id' => $id,
                'nomor_baris' => $baris['nomor'],
                'tindakan' => $aksi,
                'username' => $username,
                'nama' => $baris['nama'],
                'nama_sebelum' => $namaSebelum,
                'peran' => implode(',', $peran),
                'peran_sebelum' => $peranSebelum,
                'angkatan_id' => $angkatanId,
                'semester_mulai' => $baris['semester'] === -1 ? null : $baris['semester'],
                'sandi_hash' => $hash,
                'target_id' => $target,
                'target_versi' => $versi,
                'galat' => $error === '' ? null : mb_substr($error, 0, 255),
            ];
        }

        $this->db->transBegin();
        try {
            $waktu = time();
            $this->db->table('impor_akun')->insert([
                'id' => $id, 'admin_id' => (int) $admin['id'], 'jenis' => $jenis,
                'versi_template' => AkunXlsxService::VERSI_TEMPLATE,
                'checksum' => $sha, 'ukuran_file' => $file->getSize(),
                'status' => 'pratinjau',
                'jumlah_baru' => $counts['baru'], 'jumlah_ubah' => $counts['ubah'],
                'jumlah_tetap' => $counts['tetap'], 'jumlah_tolak' => $counts['ditolak'],
                'dibuat_pada' => gmdate('Y-m-d H:i:s', $waktu),
                'kedaluwarsa_pada' => gmdate('Y-m-d H:i:s', $waktu + 1800),
            ]);
            foreach ($hasil as $row) {
                $this->db->table('impor_akun_baris')->insert($row);
            }
            $this->audit((int) $admin['id'], 'impor_akun_pratinjau', $jenis, $sha,
                $counts['baru'], $counts['ubah'], $counts['ditolak']);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Pratinjau gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
        return [
            'batch' => $id, 'baru' => $counts['baru'], 'ubah' => $counts['ubah'],
            'tetap' => $counts['tetap'], 'tolak' => $counts['ditolak'],
        ];
    }

    /** @return array{batch:array<string,mixed>,baris:array<int,array<string,mixed>>,kedaluwarsa:bool} */
    public function laporan(string $jenis, string $id): array
    {
        $admin = $this->admin();
        $batch = $this->ambilBatch($jenis, $id, (int) $admin['id']);
        $baris = $this->db->table('impor_akun_baris')
            ->select('nomor_baris,tindakan,username,nama,nama_sebelum,peran,peran_sebelum,galat')
            ->where('impor_id', $id)->orderBy('nomor_baris')->get()->getResultArray();
        $expired = $batch['status'] === 'pratinjau'
            && strtotime($batch['kedaluwarsa_pada'] . ' UTC') < time();
        return ['batch' => $batch, 'baris' => $baris, 'kedaluwarsa' => $expired];
    }

    private function ambilBatch(string $jenis, string $id, int $adminId): array
    {
        if (! PenggunaAdminService::validasiJenis($jenis) || preg_match('/^[a-f0-9]{32}$/D', $id) !== 1) {
            throw new DomainException('Pratinjau impor tidak ditemukan.');
        }
        $batch = $this->db->table('impor_akun')->where('id', $id)
            ->where('jenis', $jenis)->where('admin_id', $adminId)->get()->getRowArray();
        if ($batch === null) {
            throw new DomainException('Pratinjau impor tidak ditemukan atau bukan milik Admin ini.');
        }
        return $batch;
    }

    /** @return array{baru:int,ubah:int,tetap:int,ulang:bool} */
    public function konfirmasi(string $jenis, string $id): array
    {
        $admin = $this->admin();
        $this->ambilBatch($jenis, $id, (int) $admin['id']);

        $this->db->transBegin();
        try {
            $batch = $this->db->query('SELECT * FROM impor_akun WHERE id=? FOR UPDATE', [$id])->getRowArray();
            if ($batch === null || $batch['jenis'] !== $jenis
                || (int) $batch['admin_id'] !== (int) $admin['id']) {
                throw new DomainException('Pratinjau tidak ditemukan.');
            }
            if ($batch['status'] === 'diterapkan') {
                $this->db->transCommit();
                return [
                    'baru' => (int) $batch['jumlah_baru'],
                    'ubah' => (int) $batch['jumlah_ubah'],
                    'tetap' => (int) $batch['jumlah_tetap'],
                    'ulang' => true,
                ];
            }
            if ($batch['status'] !== 'pratinjau'
                || strtotime($batch['kedaluwarsa_pada'] . ' UTC') < time()) {
                throw new DomainException('Pratinjau kedaluwarsa. Unggah ulang template.');
            }
            if ((int) $batch['jumlah_tolak'] > 0 || ((int) $batch['jumlah_baru'] + (int) $batch['jumlah_ubah']) === 0) {
                throw new DomainException('Tidak ada perubahan valid. Perbaiki baris yang ditolak lalu unggah ulang.');
            }

            $rows = $this->db->table('impor_akun_baris')->where('impor_id', $id)
                ->orderBy('nomor_baris')->get()->getResultArray();
            foreach ($rows as $row) {
                if (! in_array($row['tindakan'], ['baru', 'ubah'], true)) {
                    continue;
                }
                $existing = $this->db->query(
                    'SELECT id,username,versi_sesi FROM pengguna WHERE username = ? FOR UPDATE',
                    [$row['username']]
                )->getRowArray();
                if ($row['tindakan'] === 'baru' && $existing !== null) {
                    throw new DomainException('Akun sudah berubah sejak pratinjau. Unggah ulang template.');
                }
                if ($row['tindakan'] === 'ubah' && ($existing === null
                    || (int) $existing['id'] !== (int) $row['target_id']
                    || (int) $existing['versi_sesi'] !== (int) $row['target_versi'])) {
                    throw new DomainException('Data akun berubah sejak pratinjau. Unggah ulang template.');
                }
                if ($jenis === 'siswa') {
                    $gen = $this->db->table('ref_angkatan')->where('id', $row['angkatan_id'])
                        ->countAllResults();
                    if ($gen !== 1) {
                        throw new DomainException('Gen tidak tersedia saat konfirmasi. Unggah ulang.');
                    }
                }
            }

            $mutasi = new MutasiPenggunaService($this->db);
            foreach ($rows as $row) {
                if ($row['tindakan'] === 'tetap') {
                    continue;
                }
                $roles = $jenis === 'siswa' ? ['siswa'] : explode(',', $row['peran']);
                if ($row['tindakan'] === 'baru') {
                    if (! MutasiPenggunaService::identitasValid($jenis, $row['username'], $row['nama'], $roles)
                        || ! is_string($row['sandi_hash']) || $row['sandi_hash'] === '') {
                        throw new DomainException('Data akun baru tidak memenuhi validasi.');
                    }
                    $this->db->table('pengguna')->insert([
                        'username' => $row['username'], 'nama' => $row['nama'],
                        'sandi_hash' => $row['sandi_hash'], 'status' => 'aktif',
                        'wajib_ganti_sandi' => 1,
                    ]);
                    $userId = (int) $this->db->insertID();
                    foreach ($roles as $role) {
                        $this->db->table('pengguna_peran')->insert([
                            'pengguna_id' => $userId, 'peran' => $role,
                        ]);
                    }
                    if ($jenis === 'siswa') {
                        $now = gmdate('Y-m-d H:i:s');
                        $this->db->table('siswa')->insert([
                            'pengguna_id' => $userId, 'nisn' => $row['username'],
                            'angkatan_id' => $row['angkatan_id'],
                            'semester_mulai' => $row['semester_mulai'],
                            'dibuat_pada' => $now, 'diperbarui_pada' => $now,
                        ]);
                    }
                } elseif ($row['tindakan'] === 'ubah') {
                    $mutasi->ubahIdentitas($jenis, (int) $row['target_id'],
                        $row['username'], $row['nama'], $roles);
                }
            }

            // Jangan mempertahankan hash dari pratinjau setelah konfirmasi selesai.
            $this->db->table('impor_akun_baris')->where('impor_id', $id)->update(['sandi_hash' => null]);
            $this->db->table('impor_akun')->where('id', $id)->update([
                'status' => 'diterapkan', 'diterapkan_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit((int) $admin['id'], 'impor_akun_diterapkan', $jenis, $batch['checksum'],
                (int) $batch['jumlah_baru'], (int) $batch['jumlah_ubah'], (int) $batch['jumlah_tolak']);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Konfirmasi impor gagal disimpan.');
            }
            $this->db->transCommit();
            return [
                'baru' => (int) $batch['jumlah_baru'], 'ubah' => (int) $batch['jumlah_ubah'],
                'tetap' => (int) $batch['jumlah_tetap'], 'ulang' => false,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
