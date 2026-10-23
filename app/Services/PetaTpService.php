<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Fondasi baca-saja peta ATP v2.4.
 *
 * ATP = urutan TP per paket semester, bukan objek BAB. Semua pembacaan
 * dibatasi penugasan Guru aktif pada pasangan Gen–Mapel yang sama.
 */
final class PetaTpService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private function guruId(): int
    {
        $akun = (new AuthService())->penggunaSaatIni();

        if ($akun === null
            || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat melihat peta pembelajaran.');
        }

        return (int) $akun['id'];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function genMapelDiampu(): array
    {
        $guruId = $this->guruId();

        return $this->db->table('penugasan_guru_gen g')
            ->select('g.angkatan_id, g.mapel_id, a.kode AS kode_gen, a.nama AS nama_gen, m.kode AS kode_mapel, m.nama AS nama_mapel')
            ->join('ref_angkatan a', 'a.id = g.angkatan_id')
            ->join('ref_mapel m', 'm.id = g.mapel_id')
            ->where('g.guru_id', $guruId)
            ->orderBy('a.kode', 'ASC')
            ->orderBy('m.nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array{paket:list<array<string,mixed>>,tp:list<array<string,mixed>>}
     */
    public function petaSemester(int $genId, int $mapelId, int $semester): array
    {
        $guruId = $this->guruId();

        if ($genId < 1 || $mapelId < 1 || $semester < 1 || $semester > 6) {
            throw new DomainException('Gen, mata pelajaran, atau semester tidak valid.');
        }

        // Sumber hak akses adalah G1, bukan ref_mapel.guru_id warisan.
        $penugasan = $this->db->table('penugasan_guru_gen')
            ->where('angkatan_id', $genId)
            ->where('mapel_id', $mapelId)
            ->where('guru_id', $guruId)
            ->countAllResults();

        if ($penugasan !== 1) {
            throw new DomainException('Anda tidak ditugaskan pada Gen dan mata pelajaran tersebut.');
        }

        $paket = $this->db->table('paket_semester p')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.tahun_ajaran_id,p.kurikulum_id,p.nomor_versi,p.status')
            ->where('p.angkatan_id', $genId)
            ->where('p.mapel_id', $mapelId)
            ->where('p.semester', $semester)
            ->orderBy('p.nomor_versi', 'DESC')
            ->orderBy('p.id', 'DESC')
            ->get()
            ->getResultArray();

        if ($paket === []) {
            return ['paket' => [], 'tp' => []];
        }

        $ids = array_map(static fn (array $baris): int => (int) $baris['id'], $paket);
        $tp = $this->db->table('tp t')
            ->select('t.id,t.paket_semester_id,t.kode_tp,t.judul,t.rumusan,t.indikator,t.urutan_atp,t.status,t.struktur_versi')
            ->whereIn('t.paket_semester_id', $ids)
            ->orderBy('t.paket_semester_id', 'ASC')
            ->orderBy('t.urutan_atp', 'ASC')
            ->get()
            ->getResultArray();

        return ['paket' => $paket, 'tp' => $tp];
    }
}
