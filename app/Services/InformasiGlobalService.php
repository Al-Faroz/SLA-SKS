<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A13-2: Pengumuman global dan FAQ resmi login-only.
 *
 * Admin mengelola keduanya. Guru membuat/mengubah/mengarsip Pengumuman sendiri.
 * Semua akun login aktif membaca hanya konten berstatus terbit.
 * Tidak ada segmentasi Gen/role, pengiriman pesan atau penghapusan histori.
 */
final class InformasiGlobalService
{
    private BaseConnection $db;
    /** @var callable():array<string,mixed> */
    private $identitas;

    public function __construct(?BaseConnection $db = null, ?callable $identitas = null)
    {
        $this->db = $db ?? db_connect();
        $this->identitas = $identitas ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private static function tabel(string $jenis): string
    {
        if (! in_array($jenis, ['pengumuman', 'faq'], true)) {
            throw new DomainException('Jenis informasi tidak tersedia.');
        }

        return $jenis;
    }

    /** @return array<string,mixed> */
    private function akun(): array
    {
        $akun = ($this->identitas)();
        if (! is_array($akun) || (int) ($akun['id'] ?? 0) < 1
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0
            || ! in_array($akun['peran_aktif'] ?? null, ['admin','guru','siswa','pimpinan'], true)) {
            throw new DomainException('Silakan masuk dengan akun aktif untuk melihat informasi.');
        }
        return $akun;
    }

    /** @return array<string,mixed> */
    private function pengelola(string $jenis): array
    {
        self::tabel($jenis);
        $akun = $this->akun();
        if ($akun['peran_aktif'] !== 'admin'
            && ! ($jenis === 'pengumuman' && $akun['peran_aktif'] === 'guru')) {
            throw new DomainException('Peran aktif Anda tidak dapat mengelola informasi ini.');
        }
        return $akun;
    }

    /** @return array{data:list<array<string,mixed>>,jumlah:int,halaman:int,total_halaman:int,per_halaman:int} */
    public function daftar(string $jenis, bool $kelola = false, int $halaman = 1, int $perHalaman = 12): array
    {
        $jenis = self::tabel($jenis);
        $akun = $kelola ? $this->pengelola($jenis) : $this->akun();
        $perHalaman = max(1, min(50, $perHalaman));
        $halaman = max(1, $halaman);
        $query = $this->db->table($jenis);
        if ($kelola) {
            if ($akun['peran_aktif'] === 'guru') {
                $query->where('dibuat_oleh', (int) $akun['id']);
            }
            $query->orderBy('diubah_pada', 'DESC')->orderBy('id', 'DESC');
        } else {
            $query->where('status', 'terbit');
            if ($jenis === 'faq') {
                $query->orderBy('urutan', 'ASC')->orderBy('id', 'ASC');
            } else {
                $query->orderBy('diterbitkan_pada', 'DESC')->orderBy('id', 'DESC');
            }
        }
        $jumlah = $query->countAllResults(false);
        $totalHalaman = max(1, (int) ceil($jumlah / $perHalaman));
        $halaman = min($halaman, $totalHalaman);
        $data = $query->limit($perHalaman, ($halaman - 1) * $perHalaman)
            ->get()->getResultArray();
        return [
            'data' => $data,
            'jumlah' => (int) $jumlah,
            'halaman' => $halaman,
            'total_halaman' => $totalHalaman,
            'per_halaman' => $perHalaman,
        ];
    }

    /** @return array<string,mixed> */
    public function rincianKelola(string $jenis, int $id): array
    {
        $jenis = self::tabel($jenis);
        $akun = $this->pengelola($jenis);
        $row = $id > 0 ? $this->db->table($jenis)->where('id', $id)->get()->getRowArray() : null;
        if (! $row || ($akun['peran_aktif'] === 'guru'
            && (int) $row['dibuat_oleh'] !== (int) $akun['id'])) {
            throw new DomainException('Informasi tidak ditemukan atau tidak dapat Anda kelola.');
        }
        if ($row['status'] === 'arsip') {
            throw new DomainException('Informasi sudah diarsipkan dan tidak dapat disunting.');
        }
        return $row;
    }

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function validasi(string $jenis, array $input): array
    {
        $jenis = self::tabel($jenis);
        $judulKey = $jenis === 'pengumuman' ? 'judul' : 'pertanyaan';
        $isiKey = $jenis === 'pengumuman' ? 'isi' : 'jawaban';
        $judul = $input[$judulKey] ?? null;
        $isi = $input[$isiKey] ?? null;
        $status = $input['status'] ?? 'draf';
        if (! is_string($judul) || ! is_string($isi) || ! is_string($status)) {
            throw new DomainException('Judul dan isi harus berupa teks.');
        }
        $judul = trim($judul);
        $isi = trim($isi);
        $batasJudul = $jenis === 'pengumuman' ? 150 : 240;
        if ($judul === '' || mb_strlen($judul) > $batasJudul) {
            throw new DomainException('Judul/pertanyaan wajib diisi, maksimal ' . $batasJudul . ' karakter.');
        }
        if ($isi === '' || mb_strlen($isi) > 5000) {
            throw new DomainException('Isi/jawaban wajib diisi, maksimal 5.000 karakter.');
        }
        if (! in_array($status, ['draf','terbit'], true)) {
            throw new DomainException('Status informasi tidak valid.');
        }
        $hasil = [$judulKey => $judul, $isiKey => $isi, 'status' => $status];
        if ($jenis === 'faq') {
            $urutan = $input['urutan'] ?? null;
            if (! is_string($urutan) && ! is_int($urutan)) {
                throw new DomainException('Urutan FAQ wajib berupa angka 0–65535.');
            }
            if (! preg_match('/^[0-9]{1,5}$/D', (string) $urutan)
                || (int) $urutan > 65535) {
                throw new DomainException('Urutan FAQ harus angka 0–65535.');
            }
            $hasil['urutan'] = (int) $urutan;
        }
        return $hasil;
    }

    private function kunciBaris(string $jenis, int $id): ?array
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return $this->db->table($jenis)->where('id', $id)->get()->getRowArray();
        }
        return $this->db->query('SELECT * FROM ' . $jenis . ' WHERE id = ? FOR UPDATE', [$id])
            ->getRowArray();
    }

    /** @param array<string,mixed>|null $sebelum
     * @param array<string,mixed> $sesudah
     * @param array<string,mixed> $akun
     */
    private function catat(string $jenis, int $id, string $aksi, ?array $sebelum, array $sesudah, array $akun): void
    {
        $waktu = gmdate('Y-m-d H:i:s');
        $this->db->table('riwayat_informasi')->insert([
            'jenis' => $jenis,
            'objek_id' => $id,
            'aksi' => $aksi,
            'pengguna_id' => (int) $akun['id'],
            'peran_aktif' => $akun['peran_aktif'],
            'sebelum_json' => $sebelum === null ? null
                : json_encode($sebelum, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'sesudah_json' => json_encode($sesudah, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'terjadi_pada' => $waktu,
        ]);
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => (int) $akun['id'],
            'peran_aktif' => $akun['peran_aktif'],
            'peristiwa' => $jenis . '_' . $aksi,
            'catatan' => 'ID ' . $id . ', versi ' . (int) $sesudah['versi'],
            'waktu' => $waktu,
        ]);
    }

    /** @param array<string,mixed> $input */
    public function simpan(string $jenis, array $input, ?int $id = null): int
    {
        $jenis = self::tabel($jenis);
        $akun = $this->pengelola($jenis);
        $isian = self::validasi($jenis, $input);
        $waktu = gmdate('Y-m-d H:i:s');

        $this->db->transBegin();
        try {
            $sebelum = null;
            if ($id !== null) {
                $sebelum = $id > 0 ? $this->kunciBaris($jenis, $id) : null;
                if ($sebelum === null || ($akun['peran_aktif'] === 'guru'
                    && (int) $sebelum['dibuat_oleh'] !== (int) $akun['id'])) {
                    throw new DomainException('Informasi tidak ditemukan atau tidak dapat Anda kelola.');
                }
                if ($sebelum['status'] === 'arsip') {
                    throw new DomainException('Arsip tidak dapat disunting atau diterbitkan kembali.');
                }
                if ($sebelum['status'] === 'terbit' && $isian['status'] !== 'terbit') {
                    throw new DomainException('Pengumuman/FAQ terbit tidak dapat kembali menjadi draf. Gunakan Arsipkan.');
                }
                $versi = filter_var($input['versi'] ?? null, FILTER_VALIDATE_INT);
                if ($versi === false || (int) $versi !== (int) $sebelum['versi']) {
                    throw new DomainException('Informasi berubah di tab lain. Muat ulang sebelum menyimpan.');
                }

                $baru = $isian + [
                    'versi' => (int) $sebelum['versi'] + 1,
                    'diubah_oleh' => (int) $akun['id'],
                    'diubah_pada' => $waktu,
                ];
                if ($sebelum['status'] === 'draf' && $isian['status'] === 'terbit') {
                    $baru['diterbitkan_pada'] = $waktu;
                }
                if ($this->kontenSama($isian, $sebelum)) {
                    throw new DomainException('Tidak ada perubahan untuk disimpan.');
                }
                $this->db->table($jenis)->where('id', $id)
                    ->where('versi', (int) $sebelum['versi'])->update($baru);
                if ($this->db->affectedRows() !== 1) {
                    throw new DomainException('Informasi berubah di tab lain. Muat ulang sebelum menyimpan.');
                }
                $aksi = $sebelum['status'] === 'draf' && $isian['status'] === 'terbit'
                    ? 'terbit' : 'sunting';
            } else {
                $baru = $isian + [
                    'dibuat_oleh' => (int) $akun['id'],
                    'diubah_oleh' => (int) $akun['id'],
                    'versi' => 1,
                    'dibuat_pada' => $waktu,
                    'diubah_pada' => $waktu,
                    'diterbitkan_pada' => $isian['status'] === 'terbit' ? $waktu : null,
                    'diarsipkan_pada' => null,
                ];
                $this->db->table($jenis)->insert($baru);
                $id = (int) $this->db->insertID();
                $aksi = $isian['status'] === 'terbit' ? 'terbit' : 'buat';
            }
            $sesudah = $this->db->table($jenis)->where('id', $id)->get()->getRowArray();
            if ($sesudah === null) {
                throw new RuntimeException('Data informasi gagal diverifikasi.');
            }
            $this->catat($jenis, (int) $id, $aksi, $sebelum, $sesudah, $akun);
            if (! $this->db->transStatus()) {
                throw new RuntimeException('Informasi gagal disimpan. Periksa database.');
            }
            $this->db->transCommit();
            return (int) $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** @param array<string,mixed> $input
     * @param array<string,mixed> $sebelum
     */
    private function kontenSama(array $input, array $sebelum): bool
    {
        foreach ($input as $k => $v) {
            if (! array_key_exists($k, $sebelum) || (string) $v !== (string) $sebelum[$k]) {
                return false;
            }
        }
        return true;
    }

    public function arsipkan(string $jenis, int $id, int $versi): void
    {
        $jenis = self::tabel($jenis);
        $akun = $this->pengelola($jenis);
        $this->db->transBegin();
        try {
            $sebelum = $id > 0 ? $this->kunciBaris($jenis, $id) : null;
            if ($sebelum === null || ($akun['peran_aktif'] === 'guru'
                && (int) $sebelum['dibuat_oleh'] !== (int) $akun['id'])) {
                throw new DomainException('Informasi tidak ditemukan atau tidak dapat Anda kelola.');
            }
            if ($sebelum['status'] === 'arsip') {
                throw new DomainException('Informasi sudah diarsipkan.');
            }
            if ($versi < 1 || $versi !== (int) $sebelum['versi']) {
                throw new DomainException('Informasi berubah di tab lain. Muat ulang sebelum mengarsipkan.');
            }
            $this->db->table($jenis)->where('id', $id)
                ->where('versi', $versi)->update([
                    'status' => 'arsip',
                    'versi' => $versi + 1,
                    'diubah_oleh' => (int) $akun['id'],
                    'diubah_pada' => gmdate('Y-m-d H:i:s'),
                    'diarsipkan_pada' => gmdate('Y-m-d H:i:s'),
                ]);
            if ($this->db->affectedRows() !== 1) {
                throw new DomainException('Informasi berubah. Muat ulang sebelum mengarsipkan.');
            }
            $sesudah = $this->db->table($jenis)->where('id', $id)->get()->getRowArray();
            $this->catat($jenis, $id, 'arsip', $sebelum, $sesudah, $akun);
            if (! $this->db->transStatus()) {
                throw new RuntimeException('Pengarsipan gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
