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
            ->join('paket_semester p', 'p.id=pp.paket_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->where('pp.angkatan_id', $genId)
            ->where('p.angkatan_id', $genId)
            ->where('p.status', 'aktif')
            ->where('pp.mapel_id = p.mapel_id', null, false)
            ->where('pp.semester = p.semester', null, false)
            ->where('pp.tahun_ajaran_id = p.tahun_ajaran_id', null, false);
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
        string $statusDb, bool $punyaVersi, bool $pertama): array
    {
        if ($semester < $semesterMulai) {
            return ['kode' => 'dilewati', 'label' => 'Dilewati (semester awal)', 'buka' => false];
        }
        if ($semester > $semesterMulai) {
            return ['kode' => 'terkunci', 'label' => 'Menunggu semester sebelumnya', 'buka' => false];
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
    private function tpUntukPaket(int $paketId, int $semester, int $mulai): array
    {
        $baris = $this->db->table('tp t')
            ->select('t.id,t.kode_tp,t.judul,t.urutan_atp,t.status,' .
                '(SELECT COUNT(*) FROM versi_konten_tp v WHERE v.tp_id=t.id) AS jumlah_versi', false)
            ->where('t.paket_semester_id', $paketId)
            ->orderBy('t.urutan_atp', 'ASC')->orderBy('t.id', 'ASC')->get()->getResultArray();
        $pertamaId = $baris === [] ? 0 : (int) $baris[0]['id'];
        foreach ($baris as &$tp) {
            $tp['akses'] = self::statusTp($semester, $mulai, (int) $tp['urutan_atp'],
                (string) $tp['status'], (int) $tp['jumlah_versi'] > 0,
                (int) $tp['id'] === $pertamaId);
        }
        unset($tp);
        return $baris;
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
            $state = $nomor < (int) $siswa['mulai_efektif'] ? 'Dilewati'
                : ($nomor > (int) $siswa['mulai_efektif'] ? 'Menunggu semester sebelumnya'
                    : ($paket === null ? 'Belum ada paket aktif' : 'Lihat daftar TP'));
            $semesters[] = ['nomor' => $nomor, 'paket_id' => $paket === null ? null : (int) $paket['id'],
                'label_status' => $state, 'buka' => $paket !== null
                    && $nomor === (int) $siswa['mulai_efektif']];
        }
        return ['siswa' => $siswa, 'mapel' => $semua[0], 'semester' => $semesters];
    }

    /** @return array{siswa:array<string,mixed>,paket:array<string,mixed>,tp:list<array<string,mixed>>} */
    public function daftarTp(int $paketId): array
    {
        $siswa = $this->siswa();
        foreach ($this->paket((int) $siswa['angkatan_id']) as $paket) {
            if ((int) $paket['id'] === $paketId) {
                if ((int) $paket['semester'] !== (int) $siswa['mulai_efektif']) {
                    throw new DomainException('Semester ini belum tersedia untuk pembelajaran Anda.');
                }
                return ['siswa' => $siswa, 'paket' => $paket,
                    'tp' => $this->tpUntukPaket($paketId,
                        (int) $paket['semester'], (int) $siswa['mulai_efektif'])];
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
        // Data yang aman dibaca sebelum diagnostik: judul Materi saja, bukan
        // URL berkas, jawaban, kunci atau teks petunjuk tugas.
        $materi = $this->db->table('materi')
            ->select('id,urutan,judul,jenis')->where('tp_id', $tpId)
            ->where('aktif', 1)->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
        return ['siswa' => $siswa, 'paket' => $daftar['paket'], 'tp' => $tp, 'materi' => $materi];
    }
}
