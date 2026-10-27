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
        return [
            'tahun' => $this->db->table('ref_tahun_ajaran')
                ->select('id,nama')->orderBy('id', 'DESC')->get()->getResultArray(),
            'kurikulum' => $this->db->table('ref_kurikulum')
                ->select('id,nama,versi')->orderBy('id', 'DESC')->get()->getResultArray(),
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
            if ($semester < (int) $penugasan['semester_mulai']) {
                throw new DomainException('Semester ini berada sebelum semester mulai Gen.');
            }

            if ($this->db->table('ref_semester')->where('id', $semester)->countAllResults() !== 1
                || $this->db->table('ref_tahun_ajaran')->where('id', $tahunId)->countAllResults() !== 1
                || $this->db->table('ref_kurikulum')->where('id', $kurikulumId)->countAllResults() !== 1) {
                throw new DomainException('Semester, tahun ajaran, atau kurikulum belum terdaftar.');
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
