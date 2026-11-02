<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A6 — Rekap baca-saja untuk Guru G1, tanpa membuat/menyinkronkan nilai.
 * Setiap baris adalah satu siswa × satu TP pada Gen–Mapel dan semester terpilih.
 */
final class RekapNilaiTpGuruService
{
    private BaseConnection $db;
    private $akun;

    /** @param (callable():?array)|null $akun */
    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function guruId(): int
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Rekap nilai hanya boleh diakses Guru aktif.');
        }
        return (int) $akun['id'];
    }

    /** @return list<array<string,mixed>> */
    public function penugasan(): array
    {
        $this->guruId();
        return (new PetaTpService($this->db, $this->akun))->genMapelDiampu();
    }

    /**
     * Tidak cukup mempercayai pilihan dropdown; Service membuktikan G1 aktif
     * dan mencocokkan Gen siswa dengan Gen paket pada setiap baris.
     */
    private function cakupan(array $pilihan, int $guruId): array
    {
        $gen = (int) ($pilihan['angkatan_id'] ?? 0);
        $mapel = (int) ($pilihan['mapel_id'] ?? 0);
        if ($gen < 1 || $mapel < 1) {
            throw new DomainException('Pilih Gen–Mapel penugasan yang sah.');
        }
        $benar = $this->db->table('penugasan_guru_gen')
            ->where('angkatan_id', $gen)
            ->where('mapel_id', $mapel)
            ->where('guru_id', $guruId)
            ->countAllResults();
        if ($benar !== 1) {
            throw new DomainException('Guru tidak ditugaskan pada Gen–Mapel ini.');
        }
        return [$gen, $mapel];
    }

    /**
     * Daftar siswa Gen untuk sheet tersendiri, termasuk saat semester belum
     * memiliki TP. Tetap wajib lewat pemeriksaan G1 server.
     *
     * @return list<array{id:int|string,nama:string}>
     */
    public function daftarSiswa(array $pilihan, int $maks = 5000): array
    {
        $guru = $this->guruId();
        [$gen, $mapel] = $this->cakupan($pilihan, $guru);
        if ($maks < 1 || $maks > 5000) {
            throw new DomainException('Batas ekspor siswa tidak valid.');
        }
        $baris = $this->db->table('siswa s')
            ->select('s.id,u.nama')
            ->join('pengguna u', 'u.id=s.pengguna_id')
            ->where('s.angkatan_id', $gen)
            ->where('EXISTS (SELECT 1 FROM penugasan_guru_gen g WHERE g.angkatan_id='
                . (int) $gen . ' AND g.mapel_id=' . (int) $mapel
                . ' AND g.guru_id=' . (int) $guru . ')', null, false)
            ->orderBy('u.nama','ASC')->orderBy('s.id','ASC')
            ->limit($maks + 1)->get()->getResultArray();
        if (count($baris) > $maks) {
            throw new DomainException('Jumlah siswa melebihi batas keamanan ekspor. Hubungi Admin.');
        }
        return $baris;
    }

    /** Nilai nol adalah nilai sah; NULL berarti belum ada. */
    public static function tampilNilai(mixed $angka, bool $esensial = false): string
    {
        if ($esensial) return '—';
        if ($angka === null) return 'Belum ada';
        return number_format((float) $angka, 2, ',', '.');
    }

    /** Menghindari percampuran nilai kosong, nol sah, dan aktivitas yang dilewati. */
    public static function teksKomponen(array $baris, string $kolom): string
    {
        if (!in_array($kolom, ['nilai_resume','nilai_hots','nilai_sumatif','nilai_tp'], true)) {
            throw new DomainException('Kolom nilai rekap tidak valid.');
        }
        if (($baris['jenis_tp'] ?? '') === 'esensial') return '—';
        if (($baris['jalur'] ?? '') === 'lulus_diagnostik'
            && in_array($kolom, ['nilai_resume','nilai_hots'], true)) {
            return 'Dilewati';
        }
        return self::tampilNilai($baris[$kolom] ?? null);
    }

    /** Status penilaian tidak sama dengan status penyelesaian TP. */
    public static function labelNilai(?string $status, bool $esensial): string
    {
        if ($esensial) return 'Tanpa nilai (Esensial)';
        return match ($status) {
            'final' => 'Final',
            'sementara' => 'Sementara',
            default => 'Belum ada nilai',
        };
    }

    /**
     * @return array{baris:array,total:int,halaman:int,jumlah_halaman:int,
     *     jumlah_siswa:int,jumlah_final:int,jumlah_sementara:int,jumlah_belum:int}
     */
    public function rekap(?array $pilihan, int $semester, int $halaman = 1, int $perHalaman = 25): array
    {
        $guruId = $this->guruId();
        if ($semester < 1 || $semester > 6 || $halaman < 1 || $halaman > 100000
            || $perHalaman < 1 || $perHalaman > 500) {
            throw new DomainException('Semester atau halaman rekap tidak valid.');
        }
        $hasil = [
            'baris'=>[], 'total'=>0, 'halaman'=>1, 'jumlah_halaman'=>1,
            'jumlah_siswa'=>0, 'jumlah_final'=>0, 'jumlah_sementara'=>0,
            'jumlah_belum'=>0,
        ];
        if ($pilihan === null) return $hasil;
        [$gen, $mapel] = $this->cakupan($pilihan, $guruId);
        // Query baca-saja. Join nilai memakai progres siswa yang sama,
        // tidak memakai TP dari Gen lain maupun status 'siap' sebagai gate rekap.
        $dari = ' FROM paket_semester p'
            . ' JOIN tp t ON t.paket_semester_id=p.id AND t.status<>\'arsip\''
            . ' JOIN siswa s ON s.angkatan_id=p.angkatan_id'
            . ' JOIN pengguna u ON u.id=s.pengguna_id'
            . ' LEFT JOIN bab b ON b.id=t.bab_id AND b.paket_semester_id=p.id'
            . ' LEFT JOIN progres_tp pr ON pr.tp_id=t.id'
            . ' AND pr.siswa_id=s.id AND pr.paket_semester_id=p.id'
            . ' LEFT JOIN nilai_tp n ON n.progres_tp_id=pr.id'
            . ' WHERE p.angkatan_id=? AND p.mapel_id=? AND p.semester=?'
            . ' AND EXISTS (SELECT 1 FROM penugasan_guru_gen g'
            . ' WHERE g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id'
            . ' AND g.guru_id=?)';
        $args = [$gen, $mapel, $semester, $guruId];
        $jumlah = $this->db->query(
            'SELECT COUNT(*) AS total,'
            . ' SUM(CASE WHEN t.jenis_tp=\'non_esensial\''
            . ' AND n.status_nilai=\'final\' THEN 1 ELSE 0 END) AS final,'
            . ' SUM(CASE WHEN t.jenis_tp=\'non_esensial\''
            . ' AND n.status_nilai=\'sementara\' THEN 1 ELSE 0 END) AS sementara,'
            . ' SUM(CASE WHEN t.jenis_tp=\'non_esensial\''
            . ' AND n.id IS NULL THEN 1 ELSE 0 END) AS belum'
            . $dari, $args
        )->getRowArray();
        $total = (int) ($jumlah['total'] ?? 0);
        $pages = max(1, (int) ceil($total / $perHalaman));
        $halaman = min($pages, $halaman);
        $offset = ($halaman - 1) * $perHalaman;
        $siswa = $this->db->table('siswa')->where('angkatan_id', $gen)->countAllResults();
        $hasil = [
            'baris'=>[], 'total'=>$total, 'halaman'=>$halaman, 'jumlah_halaman'=>$pages,
            'jumlah_siswa'=>$siswa,
            'jumlah_final'=>(int) ($jumlah['final'] ?? 0),
            'jumlah_sementara'=>(int) ($jumlah['sementara'] ?? 0),
            'jumlah_belum'=>(int) ($jumlah['belum'] ?? 0),
        ];
        if ($total === 0) return $hasil;
        $hasil['baris'] = $this->db->query(
            'SELECT s.id AS siswa_id,u.nama AS nama_siswa,'
            . ' t.id AS tp_id,t.kode_tp,t.jenis_tp,t.status AS status_tp,'
            . ' COALESCE(NULLIF(t.nama_tp,\'\'),t.judul) AS nama_tp,'
            . ' b.nomor_bab,b.nama_bab,p.semester,'
            . ' pr.status AS status_progres,'
            . ' CASE WHEN t.jenis_tp=\'esensial\' THEN NULL ELSE n.jalur END AS jalur,'
            . ' CASE WHEN t.jenis_tp=\'esensial\' THEN NULL ELSE n.nilai_resume END AS nilai_resume,'
            . ' CASE WHEN t.jenis_tp=\'esensial\' THEN NULL ELSE n.nilai_hots END AS nilai_hots,'
            . ' CASE WHEN t.jenis_tp=\'esensial\' THEN NULL ELSE n.nilai_sumatif END AS nilai_sumatif,'
            . ' CASE WHEN t.jenis_tp=\'esensial\' THEN NULL ELSE n.nilai_tp END AS nilai_tp,'
            . ' CASE WHEN t.jenis_tp=\'esensial\' THEN NULL ELSE n.status_nilai END AS status_nilai'
            . $dari
            . ' ORDER BY u.nama ASC,s.id ASC,b.nomor_bab ASC,'
            . ' t.urutan_dalam_bab ASC,t.id ASC'
            . ' LIMIT ' . $perHalaman . ' OFFSET ' . $offset, $args
        )->getResultArray();
        return $hasil;
    }
}
