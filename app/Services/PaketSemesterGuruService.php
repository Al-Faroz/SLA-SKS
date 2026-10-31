<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Pembuatan paket DRAF untuk Guru G1.
 * Belum mengaktifkan paket, menugaskan siswa, atau menerbitkan TP.
 */
final class PaketSemesterGuruService
{
    private BaseConnection $db;

    /** @var callable():?array */
    private $akunAktif;

    /** @param (callable():?array)|null $akunAktif */
    public function __construct(?BaseConnection $db = null, ?callable $akunAktif = null)
    {
        $this->db = $db ?? db_connect();
        $this->akunAktif = $akunAktif ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function guruId(): int
    {
        $akun = ($this->akunAktif)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat mengelola paket.');
        }
        return (int) $akun['id'];
    }

    /**
     * Jangan membuka fitur paket v2.5 pada DB v3 atau instalasi v4 parsial.
     * Hanya membaca versi/struktur; tidak mengganti koneksi dari .env.
     */
    public function skemaV4Siap(): bool
    {
        return $this->db->tableExists('bab')
            && $this->db->tableExists('cp_mapel')
            && $this->db->tableExists('schema_version')
            && $this->db->table('schema_version')
                ->where('versi', 'V4-0001-R2')->countAllResults() === 1;
    }

    /** @return array{tahun:list<array<string,mixed>>,kurikulum:list<array<string,mixed>>} */
    public function referensi(int $genId, int $mapelId): array
    {
        $guruId = $this->guruId();
        if ($genId < 1 || $mapelId < 1
            || $this->db->table('penugasan_guru_gen')->where([
                'angkatan_id' => $genId,
                'mapel_id' => $mapelId,
                'guru_id' => $guruId,
            ])->countAllResults() !== 1) {
            throw new DomainException('Penugasan Gen–Mapel tidak ditemukan.');
        }
        if (! $this->skemaV4Siap()) {
            return ['tahun' => [], 'kurikulum' => []];
        }

        // Hanya tampilkan kurikulum yang memiliki CP Fase D untuk mapel ini.
        // Guru tidak diarahkan membuat paket yang tidak mungkin diisi TP.
        return [
            'tahun' => $this->db->table('ref_tahun_ajaran')
                ->select('id,nama')->orderBy('id', 'DESC')->get()->getResultArray(),
            'kurikulum' => $this->db->table('ref_kurikulum k')
                ->distinct()->select('k.id,k.nama,k.versi')
                ->join('cp_mapel c', 'c.kurikulum_id=k.id')
                ->join('ref_fase f', 'f.id=c.fase_id')
                ->join('cp_mapel_elemen e', 'e.cp_mapel_id=c.id')
                ->join('ref_cp_versi v', 'v.id=e.cp_versi_id')
                ->where('c.mapel_id', $mapelId)
                ->where('f.kode', 'D')
                ->where('v.status', 'aktif')
                ->orderBy('k.id', 'DESC')->get()->getResultArray(),
        ];
    }

    /** @return array{gen_id:int,mapel_id:int,semester:int,tahun_id:int,kurikulum_id:int} */
    public static function validasiInput(int $genId, int $mapelId, int $semester, int $tahunId, int $kurikulumId): array
    {
        if ($genId < 1 || $mapelId < 1 || $semester < 1 || $semester > 6
            || $tahunId < 1 || $kurikulumId < 1) {
            throw new DomainException('Pilih Gen–Mapel, semester 1–6, tahun ajaran, dan kurikulum yang valid.');
        }
        return [
            'gen_id' => $genId, 'mapel_id' => $mapelId, 'semester' => $semester,
            'tahun_id' => $tahunId, 'kurikulum_id' => $kurikulumId,
        ];
    }

    /**
     * Validasi input form enam semester; satu tahun ajaran dipilih untuk
     * masing-masing semester agar tidak menyalin Tahun Ajaran paket lain.
     *
     * @param array<int|string,int|string> $tahunSemester
     * @return array<int,int>
     */
    public static function validasiEnam(int $genId, int $mapelId, int $kurikulumId, array $tahunSemester): array
    {
        if ($genId < 1 || $mapelId < 1 || $kurikulumId < 1
            || count($tahunSemester) !== 6) {
            throw new DomainException('Lengkapi Gen, Mapel, Kurikulum dan Tahun Ajaran Semester 1–6.');
        }
        $hasil = [];
        for ($n = 1; $n <= 6; ++$n) {
            $value = $tahunSemester[$n] ?? null;
            if ((! is_int($value) && (! is_string($value) || ! ctype_digit($value)))
                || strlen((string) $value) > 12 || (int) $value < 1) {
                throw new DomainException('Pilih Tahun Ajaran yang valid untuk Semester ' . $n . '.');
            }
            $hasil[$n] = (int) $value;
        }
        return $hasil;
    }

    /**
     * Satu transaksi atomik untuk enam paket Guru. Semester yang sudah
     * mempunyai paket draf/aktif TIDAK diubah; archived ditolak.
     * Semester sebelum semester_mulai Gen hanya disiapkan Guru dan tidak
     * otomatis diterbitkan atau diberikan kepada Siswa.
     *
     * @param array<int|string,int|string> $tahunSemester
     * @return array{baru:int,tetap:int}
     */
    public function buatEnam(int $genId, int $mapelId, int $kurikulumId, array $tahunSemester): array
    {
        $tahun = self::validasiEnam($genId, $mapelId, $kurikulumId, $tahunSemester);
        $guruId = $this->guruId();
        if (! $this->skemaV4Siap()) {
            throw new DomainException('Pembuatan paket memerlukan database v4 R2.');
        }

        $this->db->transBegin();
        try {
            $g1 = $this->db->query(
                'SELECT g.id FROM penugasan_guru_gen g'
                . ' WHERE g.angkatan_id=? AND g.mapel_id=? AND g.guru_id=? FOR UPDATE',
                [$genId, $mapelId, $guruId]
            )->getRowArray();
            if ($g1 === null) {
                throw new DomainException('Anda tidak ditugaskan pada Gen–Mapel tersebut.');
            }

            // Jangan memodifikasi kurikulum dan tahun ajaran paket lama.
            $paketAda = $this->db->table('paket_semester')
                ->select('id,semester,kurikulum_id,status')
                ->where('angkatan_id', $genId)->where('mapel_id', $mapelId)
                ->get()->getResultArray();
            $tersimpan = [];
            foreach ($paketAda as $paket) {
                $nomor = (int) $paket['semester'];
                if ($nomor < 1 || $nomor > 6 || isset($tersimpan[$nomor])) {
                    throw new DomainException('Terdapat paket semester ganda/tidak valid. Hubungi Admin.');
                }
                if ((string) $paket['status'] === 'arsip'
                    || (int) $paket['kurikulum_id'] !== $kurikulumId) {
                    throw new DomainException('Paket Semester ' . $nomor
                        . ' sudah ada dengan status/kurikulum berbeda; tidak boleh ditimpa.');
                }
                $tersimpan[$nomor] = true;
            }

            if ($this->db->table('ref_kurikulum')->where('id', $kurikulumId)->countAllResults() !== 1) {
                throw new DomainException('Kurikulum tidak tersedia.');
            }
            $refs = $this->db->table('ref_semester s')
                ->select('s.id,k.fase_id')->join('ref_kelas k', 'k.id=s.kelas_id')
                ->whereIn('s.id', [1,2,3,4,5,6])->get()->getResultArray();
            if (count($refs) !== 6) {
                throw new DomainException('Referensi Semester 1–6 belum lengkap.');
            }
            $fase = [];
            foreach ($refs as $ref) {
                $fase[(int) $ref['fase_id']] = true;
            }
            if (count($fase) !== 1) {
                throw new DomainException('Referensi Fase enam semester tidak konsisten.');
            }
            $cp = $this->db->table('cp_mapel')->select('id')->where([
                'kurikulum_id' => $kurikulumId,
                'mapel_id' => $mapelId,
                'fase_id' => (int) array_key_first($fase),
            ])->get()->getRowArray();
            if ($cp === null || $this->db->table('cp_mapel_elemen e')
                ->join('ref_cp_versi v', 'v.id=e.cp_versi_id')
                ->where('e.cp_mapel_id', (int) $cp['id'])
                ->where('v.status', 'aktif')->countAllResults() < 1) {
                throw new DomainException('CP operasional dan elemen aktif belum tersedia.');
            }

            $tahunSah = $this->db->table('ref_tahun_ajaran')
                ->select('id')->whereIn('id', array_values($tahun))
                ->get()->getResultArray();
            $idSah = array_fill_keys(array_map('intval', array_column($tahunSah, 'id')), true);
            foreach ($tahun as $semester => $tahunId) {
                if (! isset($idSah[$tahunId])) {
                    throw new DomainException('Tahun Ajaran Semester ' . $semester . ' tidak tersedia.');
                }
            }
            $baru = 0;
            foreach ($tahun as $semester => $tahunId) {
                if (isset($tersimpan[$semester])) {
                    continue;
                }
                $this->db->table('paket_semester')->insert([
                    'angkatan_id' => $genId,
                    'mapel_id' => $mapelId,
                    'semester' => $semester,
                    'tahun_ajaran_id' => $tahunId,
                    'kurikulum_id' => $kurikulumId,
                    'nomor_versi' => 1,
                    'status' => 'draf',
                    'dibuat_oleh' => $guruId,
                    'dibuat_pada' => date('Y-m-d H:i:s'),
                ]);
                if ($this->db->transStatus() === false || $this->db->affectedRows() !== 1) {
                    throw new DomainException('Gagal menyimpan draf Semester ' . $semester . '.');
                }
                ++$baru;
            }
            if ($this->db->transStatus() === false || $this->db->transCommit() === false) {
                throw new DomainException('Transaksi enam paket tidak dapat diselesaikan.');
            }
            return ['baru' => $baru, 'tetap' => 6 - $baru];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function buat(int $genId, int $mapelId, int $semester, int $tahunId, int $kurikulumId): int
    {
        self::validasiInput($genId, $mapelId, $semester, $tahunId, $kurikulumId);
        $guruId = $this->guruId();
        if (! $this->skemaV4Siap()) {
            throw new DomainException('Pembuatan paket memerlukan database v4 R2 yang sudah terverifikasi.');
        }

        $this->db->transBegin();
        try {
            // Lock G1 menyerialkan dua request pembuatan paket pada konteks sama.
            $penugasan = $this->db->query(
                'SELECT g.id,a.semester_mulai FROM penugasan_guru_gen g'
                . ' JOIN ref_angkatan a ON a.id=g.angkatan_id'
                . ' WHERE g.angkatan_id=? AND g.mapel_id=? AND g.guru_id=? FOR UPDATE',
                [$genId, $mapelId, $guruId]
            )->getRowArray();
            if ($penugasan === null) {
                throw new DomainException('Anda tidak ditugaskan pada Gen–Mapel yang dipilih.');
            }
            // Semester mulai membatasi SISWA, bukan persiapan ATP oleh Guru.

            $refSemester = $this->db->table('ref_semester s')
                ->select('k.fase_id')
                ->join('ref_kelas k', 'k.id=s.kelas_id')
                ->where('s.id', $semester)->get()->getRowArray();
            if ($refSemester === null
                || $this->db->table('ref_tahun_ajaran')->where('id', $tahunId)->countAllResults() !== 1
                || $this->db->table('ref_kurikulum')->where('id', $kurikulumId)->countAllResults() !== 1) {
                throw new DomainException('Semester, tahun ajaran, atau kurikulum belum terdaftar.');
            }

            // Pilihan form bukan izin: ulangi validasi CP di transaksi.
            $cpMapel = $this->db->table('cp_mapel')->select('id')->where([
                'kurikulum_id' => $kurikulumId,
                'mapel_id' => $mapelId,
                'fase_id' => (int) $refSemester['fase_id'],
            ])->get()->getRowArray();
            if ($cpMapel === null || $this->db->table('cp_mapel_elemen e')
                ->join('ref_cp_versi v', 'v.id=e.cp_versi_id')
                ->where('e.cp_mapel_id', (int) $cpMapel['id'])
                ->where('v.status', 'aktif')->countAllResults() < 1) {
                throw new DomainException('CP operasional dengan elemen aktif belum tersedia untuk kurikulum dan mapel ini. Hubungi Admin.');
            }

            // Tanpa fitur revisi paket pada tahap ini: cegah versi ganda,
            // termasuk bila paket lama berstatus arsip.
            if ($this->db->table('paket_semester')->where([
                'angkatan_id' => $genId,
                'mapel_id' => $mapelId,
                'semester' => $semester,
                'tahun_ajaran_id' => $tahunId,
            ])->countAllResults() !== 0) {
                throw new DomainException('Paket semester untuk Gen–Mapel dan tahun ajaran ini sudah ada.');
            }

            $this->db->table('paket_semester')->insert([
                'angkatan_id' => $genId,
                'mapel_id' => $mapelId,
                'semester' => $semester,
                'tahun_ajaran_id' => $tahunId,
                'kurikulum_id' => $kurikulumId,
                'nomor_versi' => 1,
                'status' => 'draf',
                'dibuat_oleh' => $guruId,
                'dibuat_pada' => date('Y-m-d H:i:s'),
            ]);
            if ($this->db->transStatus() === false || $this->db->affectedRows() !== 1) {
                throw new DomainException('Paket draf gagal disimpan.');
            }
            $id = (int) $this->db->insertID();
            $this->db->transCommit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
