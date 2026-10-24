<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Mencatat pertama kali siswa mengaktifkan TP: snapshot versi terpin,
 * tidak pernah mengambil soal/kunci untuk browser, dan tidak membuat attempt.
 * Diagnostik akan menjadi langkah berikutnya di modul mesin ujian.
 */
final class ProgresTpSiswaService
{
    private BaseConnection $db;
    private KatalogPembelajaranSiswaService $katalog;
    /** @var callable():?array */
    private $penggunaSaatIni;

    /** @param (callable():?array)|null $penggunaSaatIni */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->penggunaSaatIni = $penggunaSaatIni
            ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
        $this->katalog = new KatalogPembelajaranSiswaService($this->db, $this->penggunaSaatIni);
    }

    /** @return array<string,mixed> */
    private function akun(): array
    {
        $akun = ($this->penggunaSaatIni)();
        if ($akun === null || ($akun['peran_aktif'] ?? null) !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Akses ini hanya untuk Siswa aktif.');
        }
        return $akun;
    }

    /**
     * Hanya memaparkan status progres milik siswa login.
     * Tidak membuat catatan ketika siswa mengunjungi halaman via GET.
     *
     * @return array<string,mixed>|null
     */
    public function status(int $tpId): ?array
    {
        $this->akun();
        $data = $this->katalog->rincianTp($tpId);
        $row = $this->db->table('progres_tp')
            ->select('id,versi_konten_tp_id,versi_paket_yang_ditugaskan,status,dimulai_pada')
            ->where('siswa_id', (int) $data['siswa']['id'])
            ->where('tp_id', $tpId)
            ->orderBy('id', 'ASC')->get(1)->getRowArray();
        if ($row !== null && !self::statusDiizinkan((string) $row['status'])) {
            throw new DomainException('Status progres TP tidak dikenal.');
        }
        return $row;
    }

    public static function statusDiizinkan(string $status): bool
    {
        return in_array($status, [
            'belum_mulai', 'diagnostik', 'belajar', 'sumatif', 'perlu_bantuan', 'tuntas',
        ], true);
    }

    /** @return array<string,mixed> */
    public function mulai(int $tpId): array
    {
        if ($tpId < 1) {
            throw new DomainException('TP tidak tersedia.');
        }
        $akun = $this->akun();

        // Validasi otorisasi awal tanpa menulis apa pun; periksa ulang di dalam lock.
        $this->katalog->rincianTp($tpId);
        $this->db->transBegin();
        try {
            // Satu siswa, satu urutan lock konsisten. Dua klik paralel menunggu
            // lock siswa ini; baris UNIQUE menjadi pengaman tambahan.
            $siswa = $this->db->query(
                'SELECT id,angkatan_id FROM siswa WHERE pengguna_id=? FOR UPDATE',
                [(int) $akun['id']]
            )->getRowArray();
            if ($siswa === null) {
                throw new DomainException('Data siswa tidak tersedia.');
            }
            $tp = $this->db->query(
                'SELECT id,paket_semester_id,status FROM tp WHERE id=? FOR UPDATE', [$tpId]
            )->getRowArray();
            if ($tp === null || $tp['status'] !== 'siap') {
                throw new DomainException('TP belum Siap atau telah berubah. Muat ulang halaman.');
            }
            $context = $this->katalog->rincianTp($tpId); // role, Gen, semester, urutan, paket aktif
            if ((int) $context['siswa']['id'] !== (int) $siswa['id']
                || (int) $context['paket']['id'] !== (int) $tp['paket_semester_id']) {
                throw new DomainException('Penugasan TP berubah. Muat ulang halaman.');
            }

            $lama = $this->db->table('progres_tp')
                ->where('siswa_id', $siswa['id'])->where('tp_id', $tpId)
                ->orderBy('id', 'ASC')->get(1)->getRowArray();
            if ($lama !== null) {
                // Idempoten; tidak menambah progres, tidak memindahkan versi.
                $this->db->transCommit();
                return $lama;
            }

            $versi = $this->db->table('versi_konten_tp')
                ->select('id,tp_id,nomor_versi,snapshot_json,checksum')
                ->where('tp_id', $tpId)->orderBy('nomor_versi', 'DESC')
                ->get(1)->getRowArray();
            if ($versi === null || !self::validasiSnapshot($versi, $tpId)) {
                throw new DomainException('Snapshot TP belum sah. Hubungi Guru pengampu.');
            }

            $nomorPaket = (int) ($context['paket']['nomor_versi'] ?? 0);
            if ($nomorPaket < 1) {
                throw new DomainException('Versi paket belum ditetapkan.');
            }
            $waktu = gmdate('Y-m-d H:i:s');
            $this->db->table('progres_tp')->insert([
                'siswa_id' => (int) $siswa['id'],
                'tp_id' => $tpId,
                'paket_semester_id' => (int) $context['paket']['id'],
                'versi_paket_yang_ditugaskan' => $nomorPaket,
                'versi_konten_tp_id' => (int) $versi['id'],
                'status' => 'diagnostik',
                'dimulai_pada' => $waktu,
                'diubah_pada' => $waktu,
            ]);
            $id = (int) $this->db->insertID();
            if ($id < 1) {
                throw new RuntimeException('Gagal mencatat progres TP.');
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id' => (int) $akun['id'], 'peran_aktif' => 'siswa',
                'peristiwa' => 'tp_mulai_versi_terpin',
                'catatan' => 'TP #' . $tpId . ', versi konten #' . (int) $versi['id']
                    . ', progres #' . $id,
                'waktu' => $waktu,
            ]);
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Pencatatan progres TP gagal.');
            }
            $this->db->transCommit();
            return [
                'id' => $id, 'siswa_id' => (int) $siswa['id'], 'tp_id' => $tpId,
                'paket_semester_id' => (int) $context['paket']['id'],
                'versi_paket_yang_ditugaskan' => $nomorPaket,
                'versi_konten_tp_id' => (int) $versi['id'], 'status' => 'diagnostik',
                'dimulai_pada' => $waktu,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Checksum dan kaitan TP dicek sebelum satu siswa memakai versi historis. */
    public static function validasiSnapshot(array $versi, int $tpId): bool
    {
        if ((int) ($versi['tp_id'] ?? 0) !== $tpId
            || (int) ($versi['id'] ?? 0) < 1
            || (int) ($versi['nomor_versi'] ?? 0) < 1
            || !is_string($versi['snapshot_json'] ?? null)
            || !is_string($versi['checksum'] ?? null)) {
            return false;
        }
        $data = json_decode($versi['snapshot_json'], true);
        if (!is_array($data) || ($data['format'] ?? null) !== 'SLA-SKS-TP-V1'
            || (int) ($data['tp']['id'] ?? 0) !== $tpId
            || ($data['tp']['status'] ?? null) !== 'siap'
            || !is_array($data['materi'] ?? null)
            || !is_array($data['soal'] ?? null)) {
            return false;
        }
        return hash_equals(strtolower((string) $versi['checksum']),
            hash('sha256', $versi['snapshot_json']));
    }
}
