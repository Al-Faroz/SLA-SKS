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

    public function penugasan(): array
    {
        $this->guruId();
        return (new PetaTpService($this->db, $this->akun))->genMapelDiampu();
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

    /** @return array{baris:array,total:int,halaman:int,jumlah_halaman:int} */
    public function antrean(?array $filter = null, int $halaman = 1): array
    {
        $guruId = $this->guruId();
        if ($halaman < 1 || $halaman > 100000) {
            throw new DomainException('Halaman antrean tidak valid.');
        }
        $where = ' AND r.nilai IS NULL AND r.dinilai_oleh IS NULL';
        $args = [];
        if ($filter !== null) {
            $where .= ' AND p.angkatan_id=? AND p.mapel_id=?';
            $args = [(int) $filter['angkatan_id'], (int) $filter['mapel_id']];
        }
        $parts = []; $bindings = [];
        foreach (['resume', 'hots'] as $jenis) {
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
        return ['baris'=>$rows,'total'=>$total,'halaman'=>$halaman,
            'jumlah_halaman'=>$pages];
    }

    public function lihat(string $jenis, int $id): array
    {
        $guru = $this->guruId();
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
