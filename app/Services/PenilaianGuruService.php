<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/** Penilaian awal G10/G11; koreksi nilai historis bukan bagian dari layanan ini. */
final class PenilaianGuruService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function guruId(): int
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Guru aktif yang boleh melakukan penilaian.');
        }
        return (int) $akun['id'];
    }

    private static function tabel(string $jenis): string
    {
        return KegiatanMateriSiswaService::tabel($jenis);
    }

    public static function skor(int $nilai): int
    {
        if ($nilai < 0 || $nilai > 100) {
            throw new DomainException('Nilai harus bilangan bulat 0–100.');
        }
        return $nilai;
    }

    public static function komentar(string $teks): string
    {
        $teks = trim(str_replace(["\r\n", "\r"], "\n", $teks));
        if (mb_strlen($teks, 'UTF-8') > 1000) {
            throw new DomainException('Umpan balik maksimal 1.000 karakter.');
        }
        return $teks;
    }

    /** Indikator 3-gram kata: bukan keputusan kecurangan/penilaian. */
    public static function kemiripan(string $pertama, string $kedua): float
    {
        $tokens = static function (string $teks): array {
            $teks = mb_strtolower($teks, 'UTF-8');
            $teks = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $teks);
            $kata = preg_split('/\s+/u', trim((string) $teks), -1, PREG_SPLIT_NO_EMPTY);
            $kata = is_array($kata) ? $kata : [];
            if (count($kata) < 3) return array_fill_keys($kata, true);
            $set = [];
            for ($i = 0; $i <= count($kata) - 3; $i++) {
                $set[implode(' ', array_slice($kata, $i, 3))] = true;
            }
            return $set;
        };
        $a = $tokens($pertama);
        $b = $tokens($kedua);
        if (!$a || !$b) return 0.0;
        $irisan = count(array_intersect_key($a, $b));
        $gabungan = count($a) + count($b) - $irisan;
        return $gabungan > 0 ? round(100 * $irisan / $gabungan, 1) : 0.0;
    }

    private function skorKemiripan(string $jenis, array $data): float
    {
        $tabel = self::tabel($jenis);
        $row = $this->db->table($tabel . ' x')
            ->select('x.isi')
            ->join('progres_tp pr','pr.id=x.progres_tp_id')
            ->join('siswa s','s.id=pr.siswa_id')
            ->where('x.materi_id',(int)$data['materi_id'])
            ->where('x.versi_konten_tp_id',(int)$data['versi_konten_tp_id'])
            ->where('s.angkatan_id',(int)$data['angkatan_id'])
            ->where('x.status','final')->where('x.id !=',(int)$data['id'])
            ->limit(500)->get()->getResultArray();
        $max = 0.0;
        foreach ($row as $other) {
            $max = max($max, self::kemiripan(
                (string)$data['isi'], (string)$other['isi']));
        }
        return $max;
    }

    public function penugasan(): array
    {
        $this->guruId();
        return (new PetaTpService($this->db, $this->akun))->genMapelDiampu();
    }

    /** BAB pilihan Guru G1 pada Gen–Mapel; semester 0 berarti seluruh semester. */
    public function daftarBab(?array $pilihan, int $semester = 0): array
    {
        $guru = $this->guruId();
        if ($pilihan === null) return [];
        if ($semester < 0 || $semester > 6) {
            throw new DomainException('Semester penilaian tidak sah.');
        }
        $q = $this->db->table('bab b')
            ->select('b.id,b.nomor_bab,b.nama_bab,p.semester')
            ->join('paket_semester p','p.id=b.paket_semester_id')
            ->join('penugasan_guru_gen g',
                'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->where('g.guru_id',$guru)
            ->where('p.angkatan_id',(int)$pilihan['angkatan_id'])
            ->where('p.mapel_id',(int)$pilihan['mapel_id']);
        if ($semester > 0) $q->where('p.semester',$semester);
        return $q->orderBy('p.semester','ASC')->orderBy('b.nomor_bab','ASC')
            ->orderBy('b.id','ASC')->get()->getResultArray();
    }

    private static function sumber(string $jenis): string
    {
        $t = self::tabel($jenis);
        return ' FROM ' . $t . ' r'
            . ' JOIN progres_tp pr ON pr.id=r.progres_tp_id'
            . ' JOIN siswa s ON s.id=pr.siswa_id'
            . ' JOIN pengguna u ON u.id=s.pengguna_id'
            . ' JOIN materi m ON m.id=r.materi_id AND m.tp_id=pr.tp_id'
            . ' JOIN tp t ON t.id=pr.tp_id'
            . ' JOIN paket_semester p ON p.id=t.paket_semester_id'
            . ' JOIN versi_konten_tp v ON v.id=r.versi_konten_tp_id AND v.tp_id=t.id'
            . ' JOIN penugasan_guru_gen g ON g.angkatan_id=p.angkatan_id'
            . ' AND g.mapel_id=p.mapel_id'
            . ' JOIN ref_angkatan a ON a.id=p.angkatan_id'
            . ' JOIN ref_mapel rm ON rm.id=p.mapel_id'
            . ' WHERE g.guru_id=? AND s.angkatan_id=p.angkatan_id'
            . ' AND r.versi_konten_tp_id=pr.versi_konten_tp_id'
            . " AND r.status='final'";
    }

    private static function kolom(string $jenis): string
    {
        self::tabel($jenis);
        return "SELECT r.id,r.progres_tp_id,r.materi_id,r.versi_konten_tp_id,"
            . "r.isi,r.status,r.nilai,r.dinilai_oleh,r.dinilai_pada,"
            . "r.umpan_balik,r.dikirim_pada,t.id AS tp_id,t.kode_tp,"
            . "t.judul AS tp_judul,m.urutan AS urutan_materi,"
            . "m.judul AS materi_judul,u.nama AS nama_siswa,"
            . "a.nama AS nama_gen,rm.nama AS nama_mapel,"
            . "p.angkatan_id,p.mapel_id,"
            . "'" . $jenis . "' AS jenis";
    }

    /** Periksa tabel jawaban siswa; jangan memakai bank tugas HOTS milik Guru. */
    private function pastikanSkema(): void
    {
        if (!$this->db->tableExists('jawaban_hots_materi')) {
            throw new RuntimeException('Tabel jawaban HOTS siswa belum terpasang. Periksa SQL 0018.');
        }
    }

    /** @return array{baris:array,total:int,halaman:int,jumlah_halaman:int} */
    public function antrean(?array $filter = null, int $halaman = 1): array
    {
        $guruId = $this->guruId();
        $this->pastikanSkema();
        if ($halaman < 1 || $halaman > 100000) {
            throw new DomainException('Halaman antrean tidak valid.');
        }
        $where = ' AND r.nilai IS NULL AND r.dinilai_oleh IS NULL';
        $args = [];
        $jenisFilter = (string)($filter['jenis'] ?? 'semua');
        $semester = (int)($filter['semester'] ?? 0);
        $babId = (int)($filter['bab'] ?? 0);
        if (!in_array($jenisFilter,['semua','resume','hots'],true)
            || $semester < 0 || $semester > 6 || $babId < 0) {
            throw new DomainException('Filter jenis tugas, semester atau BAB tidak sah.');
        }
        if ($filter !== null) {
            $where .= ' AND p.angkatan_id=? AND p.mapel_id=?';
            $args = [(int) $filter['angkatan_id'], (int) $filter['mapel_id']];
            if ($semester > 0) {
                $where .= ' AND p.semester=?';
                $args[] = $semester;
            }
            if ($babId > 0) {
                $allowed = array_column($this->daftarBab($filter,$semester),'id');
                if (!in_array($babId,array_map('intval',$allowed),true)) {
                    throw new DomainException('BAB bukan milik Gen–Mapel dan semester penugasan Guru.');
                }
                $where .= ' AND t.bab_id=?';
                $args[] = $babId;
            }
        } elseif ($babId > 0) {
            throw new DomainException('Pilih penugasan Guru sebelum memfilter BAB.');
        }
        $parts = []; $bindings = [];
        $jenisDipakai = $jenisFilter === 'semua' ? ['resume','hots'] : [$jenisFilter];
        foreach ($jenisDipakai as $jenis) {
            $parts[] = self::kolom($jenis) . self::sumber($jenis) . $where;
            $bindings[] = $guruId;
            array_push($bindings, ...$args);
        }
        $subquery = implode(' UNION ALL ', $parts);
        $count = $this->db->query(
            'SELECT COUNT(*) AS jumlah FROM (' . $subquery . ') q',
            $bindings)->getRowArray();
        $total = (int) ($count['jumlah'] ?? 0);
        $perPage = 25;
        $pages = max(1, (int) ceil($total / $perPage));
        $halaman = min($pages, $halaman);
        $rows = $this->db->query('SELECT * FROM (' . $subquery . ') q'
            . ' ORDER BY dikirim_pada ASC,id ASC,jenis ASC'
            . ' LIMIT ' . $perPage . ' OFFSET ' . (($halaman - 1) * $perPage),
            $bindings)->getResultArray();
        // Data indikator tetap disediakan untuk kompatibilitas dan QA;
        // kolom Jenis pada View tidak lagi menampilkan persentasenya.
        foreach ($rows as &$r) {
            $r['kemiripan'] = $this->skorKemiripan((string)$r['jenis'], $r);
        }
        unset($r);
        return ['baris'=>$rows,'total'=>$total,'halaman'=>$halaman,
            'jumlah_halaman'=>$pages];
    }

    public function lihat(string $jenis, int $id): array
    {
        $guru = $this->guruId();
        $this->pastikanSkema();
        if ($id < 1) throw new DomainException('Tugas tidak valid.');
        $row = $this->db->query(self::kolom($jenis) . self::sumber($jenis)
            . ' AND r.id=?', [$guru, $id])->getRowArray();
        if ($row === null) {
            throw new DomainException('Tugas di luar Gen–Mapel yang Anda ampu.');
        }
        $snapshot = $this->db->table('versi_konten_tp')
            ->select('snapshot_json,checksum')->where('id',
                (int) $row['versi_konten_tp_id'])->get()->getRowArray();
        if (!$snapshot || !hash_equals((string) $snapshot['checksum'],
                hash('sha256',(string) $snapshot['snapshot_json']))) {
            throw new DomainException('Snapshot TP tidak sah.');
        }
        $data = json_decode((string) $snapshot['snapshot_json'], true);
        if (!is_array($data) || !is_array($data['materi'] ?? null)) {
            throw new DomainException('Snapshot materi tidak dapat dibaca.');
        }
        foreach ($data['materi'] as $materi) {
            if ((int) ($materi['id'] ?? 0) === (int) $row['materi_id']) {
                $row['materi_judul_snapshot'] = (string) ($materi['judul'] ?? '');
                $row['petunjuk'] = (string) ($materi[$jenis === 'resume'
                    ? 'petunjuk_resume' : 'petunjuk_hots'] ?? '');
                $row['kemiripan'] = $this->skorKemiripan($jenis, $row);
                return $row;
            }
        }
        throw new DomainException('Materi tidak ada dalam snapshot TP siswa.');
    }

    public function simpan(string $jenis, int $id, int $nilai, string $umpan): void
    {
        $nilai = self::skor($nilai);
        $umpan = self::komentar($umpan);
        $tabel = self::tabel($jenis);
        $guruId = $this->guruId();
        $objek = $this->lihat($jenis, $id);
        $this->db->transBegin();
        try {
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $parent = $this->db->query(
                'SELECT versi_konten_tp_id FROM progres_tp WHERE id=?' . $lock,
                [(int) $objek['progres_tp_id']])->getRowArray();
            if (!$parent || (int) $parent['versi_konten_tp_id']
                !== (int) $objek['versi_konten_tp_id']) {
                throw new DomainException('Versi TP sudah berubah.');
            }
            $g1 = $this->db->query('SELECT guru_id FROM penugasan_guru_gen'
                . ' WHERE angkatan_id=? AND mapel_id=?' . $lock,
                [(int) $objek['angkatan_id'],(int) $objek['mapel_id']])
                ->getRowArray();
            if (!$g1 || (int) $g1['guru_id'] !== $guruId) {
                throw new DomainException('Penugasan Guru sudah berubah.');
            }
            $row = $this->db->query('SELECT * FROM ' . $tabel
                . ' WHERE id=?' . $lock, [$id])->getRowArray();
            if (!$row || $row['status'] !== 'final'
                || (int) $row['progres_tp_id'] !== (int) $objek['progres_tp_id']
                || (int) $row['materi_id'] !== (int) $objek['materi_id']
                || (int) $row['versi_konten_tp_id'] !== (int) $objek['versi_konten_tp_id']) {
                throw new DomainException('Jawaban final tidak lagi sesuai.');
            }
            if ($row['nilai'] !== null || $row['dinilai_oleh'] !== null) {
                if ((int) $row['nilai'] === $nilai
                    && (int) $row['dinilai_oleh'] === $guruId
                    && (string) ($row['umpan_balik'] ?? '') === $umpan) {
                    $this->db->transCommit(); // retry idempoten
                    return;
                }
                throw new DomainException('Sudah dinilai. Gunakan jalur koreksi nilai.');
            }
            $now = gmdate('Y-m-d H:i:s');
            $this->db->table($tabel)->where('id',$id)
                ->where('status','final')->where('nilai',null)
                ->where('dinilai_oleh',null)->update([
                    'nilai'=>$nilai,'umpan_balik'=>$umpan,
                    'dinilai_oleh'=>$guruId,'dinilai_pada'=>$now,
                ]);
            if ($this->db->affectedRows() !== 1) {
                throw new DomainException('Penilaian bersamaan. Muat ulang.');
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$guruId,'peran_aktif'=>'guru',
                'peristiwa'=>'nilai_' . $jenis . '_materi',
                'catatan'=>strtoupper($jenis) . ' #' . $id
                    . ', TP #' . $objek['tp_id'] . ', skor ' . $nilai,
                'waktu'=>$now,
            ]);
            // Bila Sumatif dan seluruh aktivitas sah sudah selesai,
            // penilaian Guru langsung memperbarui nilai_tp secara atomik.
            // Jika komponen lain belum dinilai, tetap status sementara.
            NilaiTpService::sinkronkan($this->db, (int) $objek['progres_tp_id']);
            $siswa=$this->db->table('progres_tp pr')
                ->select('s.pengguna_id')->join('siswa s','s.id=pr.siswa_id')
                ->where('pr.id',(int)$objek['progres_tp_id'])->get(1)->getRowArray();
            if (!$siswa) throw new RuntimeException('Penerima penilaian tidak ditemukan.');
            NotifikasiService::catat($this->db,
                $jenis==='resume'?'nilai_resume_final':'nilai_hots_final',
                (int)$siswa['pengguna_id'],$id);
            if (!$this->db->transStatus()) {
                throw new RuntimeException('Nilai gagal disimpan.');
            }
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
