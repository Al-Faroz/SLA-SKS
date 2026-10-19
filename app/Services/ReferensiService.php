<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Data induk K2: hanya Admin, tidak ada penghapusan atau pergantian progres.
 */
final class ReferensiService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public static function validasiSemesterMulai(int $semester): bool
    {
        return $semester >= 1 && $semester <= 6;
    }

    public static function validasiTahunAjaran(string $nama): bool
    {
        if (! preg_match('/^([0-9]{4})\/([0-9]{4})$/D', $nama, $cocok)) {
            return false;
        }

        return (int) $cocok[2] === (int) $cocok[1] + 1;
    }

    private function adminId(): int
    {
        $akun = (new AuthService())->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'admin'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Hanya Admin aktif yang dapat mengelola referensi.');
        }

        return (int) $akun['id'];
    }

    /** @return mixed */
    private function transaksi(callable $pekerjaan)
    {
        $this->db->transBegin();

        try {
            $hasil = $pekerjaan();
            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Perubahan tidak dapat disimpan. Coba kembali.');
            }

            $this->db->transCommit();
            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function audit(int $adminId, string $peristiwa, string $catatan): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id' => $adminId,
            'peran_aktif' => 'admin',
            'peristiwa' => $peristiwa,
            'catatan' => $catatan,
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /** @return array{angkatan:list<array<string,mixed>>,tahun:list<array<string,mixed>>} */
    public function daftarAngkatanDanTahun(): array
    {
        $this->adminId();

        return [
            'angkatan' => $this->db->table('ref_angkatan')
                ->select('id,kode,nama,semester_mulai')
                ->orderBy('id', 'DESC')->get()->getResultArray(),
            'tahun' => $this->db->table('ref_tahun_ajaran')
                ->select('id,nama')->orderBy('id', 'DESC')->get()->getResultArray(),
        ];
    }

    public function buatAngkatan(string $kode, string $nama, int $semesterMulai): void
    {
        $adminId = $this->adminId();
        $kode = strtoupper(trim($kode));
        $nama = trim($nama);

        if (! preg_match('/^[A-Z0-9._-]{2,20}$/D', $kode)
            || mb_strlen($nama) < 3 || mb_strlen($nama) > 100
            || ! self::validasiSemesterMulai($semesterMulai)) {
            throw new DomainException('Isi kode Gen 2–20 karakter, nama 3–100 karakter, dan semester mulai 1–6.');
        }

        $this->transaksi(function () use ($adminId, $kode, $nama, $semesterMulai): void {
            if ($this->db->table('ref_angkatan')->where('kode', $kode)->countAllResults() > 0) {
                throw new DomainException('Kode Gen sudah terdaftar.');
            }

            $this->db->table('ref_angkatan')->insert([
                'kode' => $kode,
                'nama' => $nama,
                'semester_mulai' => $semesterMulai,
                'dibuat_oleh' => $adminId,
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit($adminId, 'gen_dibuat', 'Gen ' . $kode . '; semester ' . $semesterMulai);
        });
    }

    public function buatTahunAjaran(string $nama): void
    {
        $adminId = $this->adminId();
        $nama = trim($nama);
        if (! self::validasiTahunAjaran($nama)) {
            throw new DomainException('Gunakan format tahun ajaran YYYY/YYYY, misalnya 2026/2027.');
        }

        $this->transaksi(function () use ($adminId, $nama): void {
            if ($this->db->table('ref_tahun_ajaran')->where('nama', $nama)->countAllResults() > 0) {
                throw new DomainException('Tahun ajaran ini sudah terdaftar.');
            }

            $this->db->table('ref_tahun_ajaran')->insert([
                'nama' => $nama,
                'dibuat_oleh' => $adminId,
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit($adminId, 'tahun_ajaran_dibuat', $nama);
        });
    }

    /** @return array{mapel:list<array<string,mixed>>,guru:list<array<string,mixed>>,riwayat:list<array<string,mixed>>} */
    public function daftarMapel(): array
    {
        $this->adminId();

        return [
            'mapel' => $this->db->table('ref_mapel m')
                ->select('m.id,m.kode,m.nama,m.guru_id,p.nama AS guru_nama')
                ->join('pengguna p', 'p.id = m.guru_id', 'left')
                ->orderBy('m.nama', 'ASC')->get()->getResultArray(),
            'guru' => $this->db->table('pengguna p')
                ->select('p.id,p.nama,p.username')
                ->join('pengguna_peran pp', 'pp.pengguna_id = p.id')
                ->where('pp.peran', 'guru')->where('p.status', 'aktif')
                ->orderBy('p.nama', 'ASC')->get()->getResultArray(),
            'riwayat' => $this->db->table('riwayat_guru_mapel r')
                ->select('r.id,r.alasan,r.waktu,m.nama AS mapel_nama,p1.nama AS guru_lama,p2.nama AS guru_baru')
                ->join('ref_mapel m', 'm.id = r.mapel_id')
                ->join('pengguna p1', 'p1.id = r.guru_sebelum_id', 'left')
                ->join('pengguna p2', 'p2.id = r.guru_sesudah_id', 'left')
                ->orderBy('r.id', 'DESC')->limit(25)->get()->getResultArray(),
        ];
    }

    public function buatMapel(string $kode, string $nama): void
    {
        $adminId = $this->adminId();
        $kode = strtoupper(trim($kode));
        $nama = trim($nama);
        if (! preg_match('/^[A-Z0-9._-]{2,24}$/D', $kode)
            || mb_strlen($nama) < 3 || mb_strlen($nama) > 140) {
            throw new DomainException('Kode mapel harus 2–24 karakter dan nama mapel 3–140 karakter.');
        }

        $this->transaksi(function () use ($adminId, $kode, $nama): void {
            if ($this->db->table('ref_mapel')->where('kode', $kode)->countAllResults() > 0) {
                throw new DomainException('Kode mata pelajaran sudah digunakan.');
            }

            $this->db->table('ref_mapel')->insert([
                'kode' => $kode,
                'nama' => $nama,
                'guru_id' => null,
                'dibuat_oleh' => $adminId,
                'dibuat_pada' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit($adminId, 'mapel_dibuat', 'Mapel ' . $kode);
        });
    }

    public function tetapkanGuru(int $mapelId, ?int $guruId, string $alasan): void
    {
        $adminId = $this->adminId();
        $alasan = trim($alasan);
        if ($mapelId < 1 || ($guruId !== null && $guruId < 1)
            || mb_strlen($alasan) < 5 || mb_strlen($alasan) > 255) {
            throw new DomainException('Pilih mapel dan isi alasan pergantian minimal 5 karakter.');
        }

        $this->transaksi(function () use ($mapelId, $guruId, $adminId, $alasan): void {
            // Row lock mencegah catatan pergantian guru terlewat oleh permintaan bersamaan.
            $mapel = $this->db->query(
                'SELECT id, guru_id FROM ref_mapel WHERE id = ? FOR UPDATE',
                [$mapelId]
            )->getRowArray();
            if ($mapel === null) {
                throw new DomainException('Mata pelajaran tidak ditemukan.');
            }

            $lamaId = $mapel['guru_id'] === null ? null : (int) $mapel['guru_id'];
            if ($guruId === $lamaId) {
                throw new DomainException('Guru yang dipilih sudah menjadi pengampu aktif.');
            }
            if ($guruId !== null) {
                $guru = $this->db->table('pengguna p')
                    ->select('p.id')
                    ->join('pengguna_peran pp', 'pp.pengguna_id = p.id')
                    ->where('p.id', $guruId)->where('p.status', 'aktif')
                    ->where('pp.peran', 'guru')->get()->getRowArray();
                if ($guru === null) {
                    throw new DomainException('Guru tidak aktif atau tidak mempunyai peran Guru.');
                }
            }

            $this->db->table('ref_mapel')->where('id', $mapelId)
                ->update(['guru_id' => $guruId]);
            $this->db->table('riwayat_guru_mapel')->insert([
                'mapel_id' => $mapelId,
                'guru_sebelum_id' => $lamaId,
                'guru_sesudah_id' => $guruId,
                'admin_id' => $adminId,
                'alasan' => $alasan,
                'waktu' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->audit($adminId, 'guru_mapel_diubah', 'Mapel ID ' . $mapelId
                . ': guru ' . ($lamaId ?? 'kosong') . ' ke ' . ($guruId ?? 'kosong'));
        });
    }
}
