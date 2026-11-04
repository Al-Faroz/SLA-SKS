<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Satu KKM efektif untuk Diagnostik dan Sumatif pada TP NonEsensial.
 * Master 02/05/08/10: khusus Guru jika diisi, selain itu default Admin.
 * Kolom tp.kkm historis hanya cache/snapshot; tidak boleh menjadi fallback 75.
 */
final class KkmTpService
{
    private BaseConnection $db;
    /** @var (callable():?array) */
    private $akunSaatIni;

    public function __construct(?BaseConnection $db = null, ?callable $akunSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunSaatIni = $akunSaatIni ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    public static function validasi(?string $raw, bool $bolehKosong): ?int
    {
        if ($raw === null || trim($raw) === '') {
            if ($bolehKosong) {
                return null;
            }
            throw new DomainException('KKM harus diisi dengan angka 1–100.');
        }
        $raw = trim($raw);
        if (!preg_match('/^[0-9]{1,3}$/D', $raw) || (int) $raw < 1 || (int) $raw > 100) {
            throw new DomainException('KKM harus berupa angka bulat antara 1 dan 100.');
        }
        return (int) $raw;
    }

    public function defaultAdmin(): ?int
    {
        $row = $this->db->table('pengaturan')->select('nilai')
            ->where('kunci', 'kkm_default')->get()->getRowArray();
        $raw = $row['nilai'] ?? null;
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }
        try {
            return self::validasi((string) $raw, false);
        } catch (DomainException) {
            return null; // Konfigurasi tidak valid tidak boleh dianggap KKM sah.
        }
    }

    /** @return array{default:?int,khusus:?int,efektif:?int,sumber:string} */
    public function informasi(array $tp): array
    {
        $default = $this->defaultAdmin();
        $khususRaw = $tp['kkm_khusus'] ?? null;
        $khusus = null;
        if ($khususRaw !== null && trim((string) $khususRaw) !== '') {
            try {
                $khusus = self::validasi((string) $khususRaw, false);
            } catch (DomainException) {
                // KKM korup harus gagal pada pemeriksaan readiness, bukan berubah ke default.
                return ['default' => $default, 'khusus' => null, 'efektif' => null, 'sumber' => 'tidak_valid'];
            }
        }
        return [
            'default' => $default,
            'khusus' => $khusus,
            'efektif' => self::pilihEfektif($default, $khusus),
            'sumber' => $khusus !== null ? 'guru' : ($default !== null ? 'admin' : 'belum_tersedia'),
        ];
    }

    private function pengguna(string $peran): int
    {
        $akun = ($this->akunSaatIni)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== $peran
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0
            || (int) ($akun['id'] ?? 0) < 1) {
            throw new DomainException('Akses pengaturan KKM tidak diizinkan.');
        }
        return (int) $akun['id'];
    }

    /** Pilih angka efektif tanpa fallback ke tp.kkm historis. */
    public static function pilihEfektif(?int $default, ?int $khusus): ?int
    {
        return $khusus ?? $default;
    }

    /** Tidak menyalin default Admin menjadi override bila Guru belum mengubah angkanya. */
    public static function normalisasiKhusus(?int $usulan, ?int $default): ?int
    {
        return $usulan !== null && $usulan === $default ? null : $usulan;
    }

    /** KKM Guru opsional, versi TP naik agar review/publikasi lama tidak dapat dipakai lagi. */
    public function simpanKhusus(int $tpId, ?string $raw, int $versi): bool
    {
        $guru = $this->pengguna('guru');
        $nilai = self::validasi($raw, true);
        if ($tpId < 1 || $versi < 1) {
            throw new DomainException('TP atau versi perubahan tidak sah.');
        }
        $this->db->transBegin();
        try {
            $lock = $this->db->query(
                'SELECT id,status,jenis_tp,struktur_versi,kkm_khusus FROM tp WHERE id=? FOR UPDATE', [$tpId]
            )->getRowArray();
            if ($lock === null || $lock['status'] !== 'draf'
                || $lock['jenis_tp'] !== 'non_esensial'
                || (int) $lock['struktur_versi'] !== $versi) {
                throw new DomainException('TP sudah Siap atau telah berubah. Muat ulang halaman.');
            }
            // Service memeriksa G1 langsung dari hubungan Gen–Mapel paket.
            (new DetailTpService($this->db, $this->akunSaatIni))->lihat($tpId);
            $default = $this->defaultAdmin();
            // KKM sama dengan default Admin tetap mewarisi perubahannya.
            $nilai = self::normalisasiKhusus($nilai, $default);
            $efektif = self::pilihEfektif($default, $nilai);
            if ($efektif === null) {
                throw new DomainException('KKM default Admin belum ditetapkan. Isi KKM khusus Guru terlebih dahulu.');
            }
            if (($lock['kkm_khusus'] !== null && (int) $lock['kkm_khusus'] === $nilai)
                || ($lock['kkm_khusus'] === null && $nilai === null)) {
                // Simpan tanpa perubahan bukan galat dan tidak membuat versi palsu.
                $this->db->transCommit();
                return false;
            }
            $this->db->table('tp')->where('id', $tpId)->where('status', 'draf')
                ->where('struktur_versi', $versi)
                ->update([
                    'kkm_khusus' => $nilai,
                    'kkm' => $efektif, // cache konsisten untuk kode lama; snapshot memakai nilai efektif.
                    'struktur_versi' => $versi + 1,
                ]);
            if ($this->db->affectedRows() !== 1 || $this->db->transStatus() === false) {
                throw new RuntimeException('KKM tidak berhasil disimpan. Muat ulang halaman.');
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $guru, 'peran_aktif' => 'guru',
                'peristiwa' => 'kkm_tp_diubah',
                'catatan' => 'TP #' . $tpId . ': KKM ' . $efektif . ' (' . ($nilai === null ? 'default Admin' : 'khusus Guru') . ').',
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Audit perubahan KKM gagal disimpan.');
            }
            $this->db->transCommit();
            return true;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** KKM Admin digunakan untuk TP draf yang tidak punya KKM khusus. */
    public function simpanDefault(?string $raw): void
    {
        $adminId = $this->pengguna('admin');
        $nilai = self::validasi($raw, true);
        $this->db->transBegin();
        try {
            $row = $this->db->query(
                'SELECT nilai FROM pengaturan WHERE kunci=? FOR UPDATE', ['kkm_default']
            )->getRowArray();
            if ($row === null) {
                throw new DomainException('Konfigurasi KKM default belum tersedia pada database.');
            }
            $sebelum = $row['nilai'] === null ? null : trim((string) $row['nilai']);
            if ($sebelum === ($nilai === null ? null : (string) $nilai)) {
                throw new DomainException('KKM default tidak berubah.');
            }
            $this->db->table('pengaturan')->where('kunci', 'kkm_default')->update([
                'nilai' => $nilai === null ? null : (string) $nilai,
                'diperbarui_oleh' => $adminId,
                'diperbarui_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => $adminId, 'peran_aktif' => 'admin',
                'peristiwa' => 'kkm_default_diubah',
                'catatan' => 'KKM default: ' . ($nilai === null ? 'belum ditetapkan' : (string) $nilai) . '.',
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Gagal menyimpan KKM default Admin.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
