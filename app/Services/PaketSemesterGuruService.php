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
                'SELECT g.id FROM penugasan_guru_gen g'
                                . ' WHERE g.angkatan_id=? AND g.mapel_id=? AND g.guru_id=? FOR UPDATE',
                [$genId, $mapelId, $guruId]
            )->getRowArray();
            if ($penugasan === null) {
                throw new DomainException('Anda tidak ditugaskan pada Gen–Mapel yang dipilih.');
            }
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
