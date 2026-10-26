<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Peta ATP: Semester → BAB → TP, dengan fallback baca untuk DB v3.
 * Semua pembacaan dibatasi penugasan Guru aktif Gen–Mapel.
 */
final class PetaTpService
{
    private BaseConnection $db;

    /** @var callable():?array */
    private $penggunaSaatIni;

    /**
     * Penyedia akun dapat diinjeksi untuk menguji Service terhadap DB sementara;
     * aplikasi tetap memakai AuthService dan sesi CI4.
     *
     * @param (callable():?array)|null $penggunaSaatIni
     */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->penggunaSaatIni = $penggunaSaatIni
            ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function guruId(): int
    {
        $akun = ($this->penggunaSaatIni)();

        if ($akun === null
            || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang dapat melihat peta pembelajaran.');
        }

        return (int) $akun['id'];
    }


    /**
     * Memilih hanya dari daftar G1 terotorisasi, tanpa menganggap indeks GET
     * sebagai identitas Gen atau mapel. Pengecekan Service tetap dilakukan ulang.
     *
     * @param list<array<string,mixed>> $penugasan
     * @return array<string,mixed>|null
     */
    public static function pilihPenugasan(array $penugasan, ?int $nomor): ?array
    {
        $nomor ??= 0;
        if ($nomor < 0 || ($nomor !== 0 && ! array_key_exists($nomor, $penugasan))) {
            throw new DomainException('Penugasan Guru tidak ditemukan.');
        }

        return $penugasan[$nomor] ?? null;
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
     * @return array{paket:list<array<string,mixed>>,bab:list<array<string,mixed>>,tp:list<array<string,mixed>>,pola_bab:bool}
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

        $polaBab = $this->db->tableExists('bab');
        if ($paket === []) {
            return ['paket' => [], 'bab' => [], 'tp' => [], 'pola_bab' => $polaBab];
        }

        $ids = array_map(static fn (array $baris): int => (int) $baris['id'], $paket);
        $bab = $polaBab
            ? $this->db->table('bab b')
                ->select('b.id,b.paket_semester_id,b.nomor_bab,b.nama_bab,b.deskripsi_bab')
                ->whereIn('b.paket_semester_id', $ids)
                ->orderBy('b.nomor_bab', 'ASC')
                ->get()->getResultArray()
            : [];

        $pilihanKolom = 't.id,t.paket_semester_id,t.kode_tp,t.judul,t.rumusan,t.indikator,t.urutan_atp,t.status,t.struktur_versi';
        if ($polaBab) {
            $pilihanKolom .= ',t.bab_id,t.jenis_tp,t.nama_tp,t.deskripsi_tp,t.urutan_dalam_bab';
        }
        $daftarTp = $this->db->table('tp t')
            ->select($pilihanKolom)
            ->whereIn('t.paket_semester_id', $ids);
        if ($polaBab) {
            // TP uji yang diarsipkan tidak menjadi konten ATP aktif v2.5.
            $daftarTp->where('t.status !=', 'arsip');
            $daftarTp->orderBy('t.bab_id', 'ASC')
                ->orderBy('t.urutan_dalam_bab', 'ASC');
        } else {
            $daftarTp->orderBy('t.urutan_atp', 'ASC');
        }
        $tp = $daftarTp->get()->getResultArray();

        return ['paket' => $paket, 'bab' => $bab, 'tp' => $tp, 'pola_bab' => $polaBab];
    }
}
