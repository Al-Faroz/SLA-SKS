<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * S01-S03: katalog siswa baca-saja berdasarkan Gen & penugasan paket aktif.
 * Tidak membuat progres, tidak membuka Diagnostik atau media sebelum jalur belajar siap.
 */
final class KatalogPembelajaranSiswaService
{
    private BaseConnection $db;

    /** @var callable():?array */
    private $penggunaSaatIni;

    /** @param (callable():?array)|null $penggunaSaatIni */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->penggunaSaatIni = $penggunaSaatIni
            ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    /** @return array<string,mixed> */
    private function siswa(): array
    {
        $akun = ($this->penggunaSaatIni)();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'siswa'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya siswa aktif yang dapat mengakses pembelajaran.');
        }
        $siswa = $this->db->table('siswa s')
            ->select('s.id,s.angkatan_id,s.semester_mulai,a.kode AS gen_kode,' .
                'a.nama AS gen_nama,a.semester_mulai AS gen_semester_mulai')
            ->join('ref_angkatan a', 'a.id=s.angkatan_id', 'left')
            ->where('s.pengguna_id', (int) $akun['id'])->get()->getRowArray();
        if ($siswa === null) {
            throw new DomainException('Data siswa belum terdaftar. Hubungi Admin.');
        }
        $start = $siswa['semester_mulai'] ?? $siswa['gen_semester_mulai'];
        $semesterMulai = (int) $start;
        if ($siswa['angkatan_id'] === null || $semesterMulai < 1 || $semesterMulai > 6) {
            throw new DomainException('Gen atau semester mulai belum ditetapkan. Hubungi Admin.');
        }
        $siswa['mulai_efektif'] = $semesterMulai;
        return $siswa;
    }

    /**
     * Tidak menggunakan ref_mapel.guru_id; Gen siswa terikat penugasan_paket.
     * Hanya paket berstatus aktif dan atribut Gen/Mapel/semester/tahun yang konsisten.
     *
     * @return list<array<string,mixed>>
     */
    private function paket(int $genId, ?int $mapelId = null): array
    {
        $q = $this->db->table('penugasan_paket pp')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.tahun_ajaran_id,p.nomor_versi,' .
                'm.kode AS kode_mapel,m.nama AS nama_mapel')
            ->join('paket_semester p', 'p.id=pp.paket_id'
                . ' AND p.angkatan_id=pp.angkatan_id'
                . ' AND p.mapel_id=pp.mapel_id'
                . ' AND p.semester=pp.semester'
                . ' AND p.tahun_ajaran_id=pp.tahun_ajaran_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->where('pp.angkatan_id', $genId)
            ->where('p.angkatan_id', $genId)
            ->where('p.status', 'aktif');
        if ($mapelId !== null) {
            $q->where('p.mapel_id', $mapelId);
        }
        return $q->orderBy('m.nama', 'ASC')->orderBy('p.semester', 'ASC')
            ->orderBy('p.id', 'ASC')->get()->getResultArray();
    }

    /**
     * Status ini tidak menganggap TP sudah tuntas karena progres/diagnostik
     * belum dipasang. Hanya TP pertama pada semester mulai yang dapat ditinjau.
     *
     * @return array{kode:string,label:string,buka:bool}
     */
    public static function statusTp(int $semester, int $semesterMulai, int $urutan,
        string $statusDb, bool $punyaVersi, bool $pertama,
        string $jenis = 'non_esensial', bool $semesterTerbuka = false): array
    {
        if ($semester < $semesterMulai) {
            return ['kode' => 'dilewati', 'label' => 'Dilewati (semester awal)', 'buka' => false];
        }
        if ($semester > $semesterMulai && ! $semesterTerbuka) {
            return ['kode' => 'terkunci', 'label' => 'Menunggu semester sebelumnya', 'buka' => false];
        }
        if ($jenis === 'esensial') {
            return ['kode' => 'esensial', 'label' => 'Esensial — tanpa aktivitas siswa', 'buka' => false];
        }
        if (!$pertama || $urutan < 1) {
            return ['kode' => 'terkunci', 'label' => 'Selesaikan TP sebelumnya', 'buka' => false];
        }
        if ($statusDb !== 'siap' || !$punyaVersi) {
            return ['kode' => 'menunggu', 'label' => 'Menunggu TP disiapkan Guru', 'buka' => false];
        }
        return ['kode' => 'pratinjau', 'label' => 'Siap — lihat rincian TP', 'buka' => true];
    }

    /** @return list<array<string,mixed>> */
    private function tpUntukPaket(int $paketId, int $semester, int $mulai,
        int $siswaId): array
    {
        $baris = $this->db->table('tp t')
            ->select('t.id,t.kode_tp,t.judul,t.urutan_atp,t.status,t.jenis_tp,' .
                '(SELECT COUNT(*) FROM versi_konten_tp v WHERE v.tp_id=t.id) AS jumlah_versi', false)
            ->where('t.paket_semester_id', $paketId)
            ->where('t.status !=', 'arsip')
            ->orderBy('t.urutan_atp', 'ASC')->orderBy('t.id', 'ASC')->get()->getResultArray();
        $ids = array_map(static fn (array $tp): int => (int) $tp['id'], $baris);
        $tuntas = [];
        if ($ids !== []) {
            $progres = $this->db->table('progres_tp')
                ->select('tp_id,status')->where('siswa_id', $siswaId)
                ->whereIn('tp_id', $ids)->get()->getResultArray();
            foreach ($progres as $p) {
                if ($p['status'] === 'tuntas') {
                    $tuntas[(int) $p['tp_id']] = true;
                }
            }
        }
        // TP Esensial tidak menghalangi atau membuka langkah NonEsensial.
        // Hanya progres tuntas milik siswa yang membuka TP berikutnya.
        $sebelumnyaTuntas = true;
        foreach ($baris as &$tp) {
            $jenis = (string) $tp['jenis_tp'];
            $tp['akses'] = self::statusTp($semester, $mulai, (int) $tp['urutan_atp'],
                (string) $tp['status'], (int) $tp['jumlah_versi'] > 0,
                $sebelumnyaTuntas, $jenis, true);
            if ($jenis === 'non_esensial' && ! isset($tuntas[(int) $tp['id']])) {
                $sebelumnyaTuntas = false;
            }
        }
        unset($tp);
        return $baris;
    }

    /** Semester selanjutnya hanya boleh dibuka setelah seluruh TP wajib sebelumnya tuntas. */
    private function semesterTuntas(int $paketId, int $siswaId): bool
    {
        $tp = $this->db->table('tp')
            ->select('id')->where('paket_semester_id', $paketId)
            ->where('jenis_tp', 'non_esensial')->where('status !=', 'arsip')
            ->get()->getResultArray();
        if ($tp === []) {
            return false;
        }
        $ids = array_map(static fn (array $baris): int => (int) $baris['id'], $tp);
        $selesai = $this->db->table('progres_tp')
            ->distinct()->select('tp_id')->where('siswa_id', $siswaId)
            ->where('status', 'tuntas')->whereIn('tp_id', $ids)
            ->get()->getResultArray();
        return count($selesai) === count($ids);
    }

    /**
     * Otorisasi server-side untuk halaman semester dan URL paket langsung.
     * Paket harus aktif, diberikan ke Gen siswa, dan seluruh semester wajib
     * sebelumnya sudah tuntas. Semester sebelum mulai selalu dilewati.
     *
     * @param list<array<string,mixed>> $paketMapel
     */
    private function semesterBoleh(int $target, int $mulai, int $siswaId, array $paketMapel): bool
    {
        if ($target < $mulai || $target > 6) {
            return false;
        }
        $idPerSemester = [];
        foreach ($paketMapel as $paket) {
            $n = (int) $paket['semester'];
            if (isset($idPerSemester[$n])) {
                return false; // Penugasan ganda ambigu, jangan buka akses.
            }
            $idPerSemester[$n] = (int) $paket['id'];
        }
        if (! isset($idPerSemester[$target])) {
            return false;
        }
        for ($semester = $mulai; $semester < $target; ++$semester) {
            if (! isset($idPerSemester[$semester])
                || ! $this->semesterTuntas($idPerSemester[$semester], $siswaId)) {
                return false;
            }
        }
        return true;
    }

    /** @return array{siswa:array<string,mixed>,mapel:list<array<string,mixed>>} */
    public function daftarMapel(): array
    {
        $siswa = $this->siswa();
        $semua = $this->paket((int) $siswa['angkatan_id']);
        $mapel = [];
        foreach ($semua as $item) {
            $id = (int) $item['mapel_id'];
            if (!isset($mapel[$id])) {
                $mapel[$id] = ['id' => $id, 'kode' => $item['kode_mapel'],
                    'nama' => $item['nama_mapel'], 'semester_tersedia' => []];
            }
            $mapel[$id]['semester_tersedia'][] = (int) $item['semester'];
        }
        return ['siswa' => $siswa, 'mapel' => array_values($mapel)];
    }

    /** @return array{siswa:array<string,mixed>,mapel:array<string,mixed>,semester:list<array<string,mixed>>} */
    public function daftarSemester(int $mapelId): array
    {
        if ($mapelId < 1) {
            throw new DomainException('Mata pelajaran tidak tersedia.');
        }
        $siswa = $this->siswa();
        $semua = $this->paket((int) $siswa['angkatan_id'], $mapelId);
        if ($semua === []) {
            throw new DomainException('Mata pelajaran ini tidak ditugaskan pada Gen Anda.');
        }
        $semesters = [];
        foreach (range(1, 6) as $nomor) {
            $paket = null;
            foreach ($semua as $p) {
                if ((int) $p['semester'] === $nomor) {
                    $paket = $p;
                    break;
                }
            }
            $boleh = $paket !== null && $this->semesterBoleh(
                $nomor, (int) $siswa['mulai_efektif'], (int) $siswa['id'], $semua
            );
            $state = $nomor < (int) $siswa['mulai_efektif'] ? 'Dilewati'
                : ($boleh ? 'Lihat daftar TP'
                    : ($paket === null ? 'Belum ada paket aktif' : 'Menunggu semester sebelumnya'));
            $semesters[] = ['nomor' => $nomor, 'paket_id' => $paket === null ? null : (int) $paket['id'],
                'label_status' => $state, 'buka' => $boleh];
        }
        return ['siswa' => $siswa, 'mapel' => $semua[0], 'semester' => $semesters];
    }

    /** @return array{siswa:array<string,mixed>,paket:array<string,mixed>,tp:list<array<string,mixed>>} */
    public function daftarTp(int $paketId): array
    {
        $siswa = $this->siswa();
        $semua = $this->paket((int) $siswa['angkatan_id']);
        foreach ($semua as $paket) {
            if ((int) $paket['id'] === $paketId) {
                $paketMapel = array_values(array_filter($semua,
                    static fn (array $p): bool =>
                        (int) $p['mapel_id'] === (int) $paket['mapel_id']));
                if (! $this->semesterBoleh(
                    (int) $paket['semester'], (int) $siswa['mulai_efektif'],
                    (int) $siswa['id'], $paketMapel
                )) {
                    throw new DomainException('Semester ini belum tersedia untuk pembelajaran Anda.');
                }
                return ['siswa' => $siswa, 'paket' => $paket,
                    'tp' => $this->tpUntukPaket($paketId,
                        (int) $paket['semester'], (int) $siswa['mulai_efektif'],
                        (int) $siswa['id'])];
            }
        }
        throw new DomainException('Paket semester tidak tersedia untuk Gen Anda.');
    }

    /** @return array{siswa:array,paket:array,tp:array,materi:list<array<string,mixed>>} */
    public function rincianTp(int $tpId): array
    {
        if ($tpId < 1) {
            throw new DomainException('TP tidak ditemukan.');
        }
        $siswa = $this->siswa();
        $target = $this->db->table('tp')->select('id,paket_semester_id')
            ->where('id', $tpId)->get()->getRowArray();
        if ($target === null) {
            throw new DomainException('TP tidak ditemukan.');
        }
        $daftar = $this->daftarTp((int) $target['paket_semester_id']);
        $tp = null;
        foreach ($daftar['tp'] as $row) {
            if ((int) $row['id'] === $tpId) {
                $tp = $row;
                break;
            }
        }
        if ($tp === null || !$tp['akses']['buka']) {
            throw new DomainException('TP belum dapat dibuka. Periksa urutan TP pada semester Anda.');
        }
        $detail = $this->db->table('tp')->select('rumusan,indikator')
            ->where('id', $tpId)->get()->getRowArray();
        $tp['rumusan'] = (string) ($detail['rumusan'] ?? '');
        $tp['indikator'] = (string) ($detail['indikator'] ?? '');
        // Data yang aman dibaca sebelum diagnostik: judul Materi saja, bukan
        // URL berkas, jawaban, kunci atau teks petunjuk tugas.
        $materi = $this->db->table('materi')
            ->select('id,urutan,judul,jenis')->where('tp_id', $tpId)
            ->where('aktif', 1)->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
        return ['siswa' => $siswa, 'paket' => $daftar['paket'], 'tp' => $tp, 'materi' => $materi];
    }
}
