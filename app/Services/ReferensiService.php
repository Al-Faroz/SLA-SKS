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

    /**
     * Memenuhi syarat saat menyimpan: akun harus aktif DAN benar-benar punya role Guru.
     * Gabungan Admin+Guru/Guru+Pimpinan tetap sah walau peran aktifnya saat ini berbeda.
     *
     * @param list<string> $peran
     */
    public static function layakMenjadiGuru(string $status, array $peran): bool
    {
        return $status === 'aktif' && in_array('guru', $peran, true);
    }

    /**
     * Semua penetapan G1 aktif hanya per pasangan Gen + mapel.
     * Tidak membaca ref_mapel.guru_id warisan sebagai izin.
     *
     * @return array<string,mixed>
     */
    public function daftarMapel(?int $genDipilih = null, int $halamanMapel = 1, int $halamanRiwayat = 1): array
    {
        $this->adminId();

        $angkatan = $this->db->table('ref_angkatan')
            ->select('id,kode,nama')->orderBy('id', 'DESC')
            ->get()->getResultArray();
        $ids = array_map(static fn (array $r): int => (int) $r['id'], $angkatan);
        // GET yang tidak dikenal tidak membuka penugasan Gen lain.
        if ($genDipilih !== null && ! in_array($genDipilih, $ids, true)) {
            throw new DomainException('Gen tidak ditemukan. Silakan pilih Gen yang tersedia.');
        }
        $genAktif = $genDipilih ?? ($ids[0] ?? null);

        // Halaman penetapan Guru dipotong langsung pada SQL; data tidak seluruhnya dikirim ke view.
        $mapelPerHalaman = 25;
        $mapelTotal = (int) $this->db->table('ref_mapel')->countAllResults();
        $mapelJumlahHalaman = max(1, (int) ceil($mapelTotal / $mapelPerHalaman));
        $mapelHalaman = min(max(1, $halamanMapel), $mapelJumlahHalaman);
        $mapel = $this->db->query(
            'SELECT m.id, m.kode, m.nama, pg.guru_id, p.nama AS guru_nama
             FROM ref_mapel m
             LEFT JOIN penugasan_guru_gen pg ON pg.mapel_id = m.id AND pg.angkatan_id = ?
             LEFT JOIN pengguna p ON p.id = pg.guru_id
             ORDER BY m.nama ASC, m.id ASC
             LIMIT ? OFFSET ?',
            [$genAktif ?? 0, $mapelPerHalaman, ($mapelHalaman - 1) * $mapelPerHalaman]
        )->getResultArray();

        $guru = $this->db->table('pengguna p')
            ->distinct()->select('p.id,p.nama')
            ->join('pengguna_peran pp', 'pp.pengguna_id = p.id')
            ->where('pp.peran', 'guru')->where('p.status', 'aktif')
            ->orderBy('p.nama', 'ASC')
            ->get()->getResultArray();

        // Riwayat tetap lintas Gen seperti halaman lama; kini seluruh halaman dapat dibuka.
        $riwayatPerHalaman = 25;
        $riwayatTotal = (int) $this->db->table('riwayat_penugasan_guru_gen')->countAllResults();
        $riwayatJumlahHalaman = max(1, (int) ceil($riwayatTotal / $riwayatPerHalaman));
        $riwayatHalaman = min(max(1, $halamanRiwayat), $riwayatJumlahHalaman);
        $riwayat = $this->db->table('riwayat_penugasan_guru_gen r')
            ->select('r.id,r.alasan,r.waktu,g.kode AS gen_kode,m.nama AS mapel_nama,p1.nama AS guru_lama,p2.nama AS guru_baru')
            ->join('ref_angkatan g', 'g.id = r.angkatan_id')
            ->join('ref_mapel m', 'm.id = r.mapel_id')
            ->join('pengguna p1', 'p1.id = r.guru_sebelum_id', 'left')
            ->join('pengguna p2', 'p2.id = r.guru_sesudah_id', 'left')
            ->orderBy('r.id', 'DESC')
            ->limit($riwayatPerHalaman, ($riwayatHalaman - 1) * $riwayatPerHalaman)
            ->get()->getResultArray();

        return [
            'mapel' => $mapel,
            'guru' => $guru,
            'angkatan' => $angkatan,
            'angkatanAktif' => $genAktif,
            'riwayat' => $riwayat,
            'mapelTotal' => $mapelTotal,
            'mapelHalaman' => $mapelHalaman,
            'mapelPerHalaman' => $mapelPerHalaman,
            'riwayatTotal' => $riwayatTotal,
            'riwayatHalaman' => $riwayatHalaman,
            'riwayatPerHalaman' => $riwayatPerHalaman,
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

    /**
     * Penetapan Guru Gen–Mapel dilakukan dalam transaksi dengan row lock mapel
     * (menyerialkan operasi pada mapel sama bahkan sebelum baris penugasan ada).
     */
    public function tetapkanGuru(int $mapelId, int $angkatanId, ?int $guruId, string $alasan): void
    {
        $adminId = $this->adminId();
        $alasan = trim($alasan);
        if ($mapelId < 1 || $angkatanId < 1 || ($guruId !== null && $guruId < 1)
            || mb_strlen($alasan) < 5 || mb_strlen($alasan) > 255) {
            throw new DomainException('Pilih Gen, mapel, Guru, dan isi alasan minimal 5 karakter.');
        }

        $this->transaksi(function () use ($mapelId, $angkatanId, $guruId, $adminId, $alasan): void {
            $mapel = $this->db->query('SELECT id FROM ref_mapel WHERE id = ? FOR UPDATE', [$mapelId])
                ->getRowArray();
            $gen = $this->db->table('ref_angkatan')->select('id')->where('id', $angkatanId)
                ->get()->getRowArray();
            if ($mapel === null || $gen === null) {
                throw new DomainException('Gen atau mata pelajaran tidak ditemukan.');
            }

            $lama = $this->db->query(
                'SELECT id,guru_id FROM penugasan_guru_gen
                 WHERE angkatan_id = ? AND mapel_id = ? FOR UPDATE',
                [$angkatanId, $mapelId]
            )->getRowArray();
            $guruLamaId = $lama === null || $lama['guru_id'] === null
                ? null : (int) $lama['guru_id'];

            if ($guruLamaId === $guruId) {
                throw new DomainException('Tidak ada perubahan Guru pengampu pada Gen ini.');
            }

            if ($guruId !== null) {
                $calon = $this->db->query(
                    'SELECT id,status FROM pengguna WHERE id = ? FOR UPDATE',
                    [$guruId]
                )->getRowArray();
                $peran = $this->db->table('pengguna_peran')->select('peran')
                    ->where('pengguna_id', $guruId)->get()->getResultArray();
                $namaPeran = array_column($peran, 'peran');
                if ($calon === null || ! self::layakMenjadiGuru((string) $calon['status'], $namaPeran)) {
                    throw new DomainException('Guru harus berstatus aktif dan mempunyai peran Guru.');
                }
            }

            $waktu = gmdate('Y-m-d H:i:s');
            if ($lama === null) {
                $this->db->table('penugasan_guru_gen')->insert([
                    'angkatan_id' => $angkatanId,
                    'mapel_id' => $mapelId,
                    'guru_id' => $guruId,
                    'dibuat_oleh' => $adminId,
                    'diperbarui_oleh' => $adminId,
                    'dibuat_pada' => $waktu,
                    'diperbarui_pada' => $waktu,
                ]);
            } else {
                $this->db->table('penugasan_guru_gen')->where('id', (int) $lama['id'])
                    ->update([
                        'guru_id' => $guruId,
                        'diperbarui_oleh' => $adminId,
                        'diperbarui_pada' => $waktu,
                    ]);
            }

            $this->db->table('riwayat_penugasan_guru_gen')->insert([
                'angkatan_id' => $angkatanId,
                'mapel_id' => $mapelId,
                'guru_sebelum_id' => $guruLamaId,
                'guru_sesudah_id' => $guruId,
                'admin_id' => $adminId,
                'alasan' => $alasan,
                'waktu' => $waktu,
            ]);
            $this->audit($adminId, 'guru_gen_mapel_diubah',
                'Gen ID ' . $angkatanId . '; Mapel ID ' . $mapelId
                . ': Guru ' . ($guruLamaId ?? 'kosong') . ' ke ' . ($guruId ?? 'kosong'));
        });
    }
}
