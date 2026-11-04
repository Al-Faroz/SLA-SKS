<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * Kompatibilitas A5: izin akses ulang tes mandiri oleh Guru G1, bukan pembatalan akademik.
 * Tidak mengubah skor/jawaban/riwayat percobaan; izin pengganti sekali pakai.
 */
final class KeputusanUjianBatalService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    public static function pilihan(string $jenis): array
    {
        return match ($jenis) {
            'diagnostik' => ['ulang_diagnostik'],
            'sumatif' => ['ulang_sumatif'],
            default => [],
        };
    }

    private function guruId(): int
    {
        $akun = ($this->akun)();
        if (!$akun || ($akun['peran_aktif'] ?? '') !== 'guru'
            || ($akun['status'] ?? '') !== 'aktif'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Pemberian izin akses ulang hanya untuk Guru aktif.');
        }
        return (int) $akun['id'];
    }

    private function pastikanSkema(): void
    {
        if (!$this->db->tableExists('keputusan_ujian_batal')
            || !$this->db->tableExists('riwayat_keputusan_ujian_batal')) {
            throw new RuntimeException('SQL V4-0008 keputusan ujian belum terpasang.');
        }
    }

    private function queryOtorisasi(int $guruId)
    {
        return $this->db->table('percobaan_ujian pu')
            ->select('pu.id,pu.progres_tp_id,pu.versi_konten_tp_id,pu.jenis,pu.tahap,'
                . 'pu.nomor_percobaan,pu.status,pu.alasan_batal,pu.waktu_selesai,pu.skor,'
                . 'pr.tp_id,pr.status AS status_tp,pr.versi_konten_tp_id AS versi_progres,'
                . 'u.nama AS nama_siswa,t.kode_tp,t.judul AS nama_tp,'
                . 'p.angkatan_id,p.mapel_id,a.nama AS nama_gen,m.nama AS nama_mapel')
            ->join('progres_tp pr', 'pr.id=pu.progres_tp_id')
            ->join('siswa s', 's.id=pr.siswa_id')
            ->join('pengguna u', 'u.id=s.pengguna_id')
            ->join('tp t', 't.id=pr.tp_id')
            ->join('paket_semester p', 'p.id=t.paket_semester_id'
                . ' AND p.id=pr.paket_semester_id')
            ->join('ref_angkatan a', 'a.id=p.angkatan_id')
            ->join('ref_mapel m', 'm.id=p.mapel_id')
            ->join('penugasan_guru_gen g', 'g.angkatan_id=p.angkatan_id'
                . ' AND g.mapel_id=p.mapel_id')
            ->where('g.guru_id', $guruId)
            ->where('s.angkatan_id=p.angkatan_id')
            ->whereIn('pu.jenis', ['diagnostik', 'sumatif'])
            ->where('pu.status', 'dibatalkan_menunggu_guru') // kode status enum DB lama
            ->where('EXISTS (SELECT 1 FROM riwayat_status_ujian rs WHERE rs.percobaan_ujian_id=pu.id AND rs.alasan=' . $this->db->escape(PermintaanAksesUlangSiswaService::ALASAN_PERMINTAAN) . ')', null, false);
    }

    public function antrean(): array
    {
        $guru = $this->guruId();
        $this->pastikanSkema();
        $rows = $this->queryOtorisasi($guru)
            ->orderBy('pu.id', 'DESC')->limit(100)->get()->getResultArray();
        $hasil = [];
        foreach ($rows as $row) {
            $last = $this->db->table('percobaan_ujian')
                ->select('id')->where('progres_tp_id', (int) $row['progres_tp_id'])
                ->where('jenis', $row['jenis'])
                ->where('tahap', $row['tahap'])
                ->orderBy('id', 'DESC')->get(1)->getRowArray();
            if ((int) ($last['id'] ?? 0) !== (int) $row['id']) continue;
            $keputusan = $this->db->table('keputusan_ujian_batal')
                ->where('percobaan_ujian_id', (int) $row['id'])->get()->getRowArray();
            if ($keputusan !== null && $keputusan['keputusan'] !== 'tunda_tinjauan') {
                continue;
            }
            $row['keputusan'] = $keputusan['keputusan'] ?? null;
            $hasil[] = $row;
        }
        return $hasil;
    }

    public function lihat(int $attemptId): array
    {
        if ($attemptId < 1) throw new DomainException('Percobaan ujian tidak sah.');
        $guru = $this->guruId();
        $this->pastikanSkema();
        $row = $this->queryOtorisasi($guru)->where('pu.id', $attemptId)
            ->get()->getRowArray();
        if (!$row) throw new DomainException('Permintaan akses ulang tidak ditemukan atau di luar Gen–Mapel Anda.');
        $row['keputusan'] = $this->db->table('keputusan_ujian_batal')
            ->where('percobaan_ujian_id', $attemptId)->get()->getRowArray();
        return $row;
    }

    /**
     * Izin akses ulang tahap tes mandiri diberikan oleh Guru, sekali pakai.
     * Izin baru tidak mengaktifkan remedial atau mengubah skor sah.
     */
    public function putuskan(int $attemptId, string $aksi): void
    {
        $guruId = $this->guruId();
        $this->pastikanSkema();
        $this->db->transBegin();
        try {
            $row = $this->lihat($attemptId);
            if (!in_array($aksi, self::pilihan((string) $row['jenis']), true)) {
                throw new DomainException('Izin tidak sesuai jenis tes mandiri.');
            }
            $lock = $this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $p = $this->db->query(
                'SELECT id,status,versi_konten_tp_id FROM progres_tp WHERE id=?' . $lock,
                [(int) $row['progres_tp_id']])->getRowArray();
            $ujian = $this->db->query(
                'SELECT * FROM percobaan_ujian WHERE id=?' . $lock,
                [$attemptId])->getRowArray();
            if (!$p || !$ujian || $ujian['status'] !== 'dibatalkan_menunggu_guru'
                || (int) $p['versi_konten_tp_id'] !== (int) $ujian['versi_konten_tp_id']) {
                throw new DomainException('Tes yang memerlukan akses ulang sudah berubah atau snapshot tidak sesuai.');
            }
            // Otorisasi G1 diperiksa ulang dan dikunci menjelang commit,
            // sehingga pergantian pengampu tidak menghasilkan keputusan lama.
            $g1 = $this->db->query(
                'SELECT guru_id FROM penugasan_guru_gen WHERE angkatan_id=?'
                . ' AND mapel_id=?' . $lock,
                [(int) $row['angkatan_id'], (int) $row['mapel_id']]
            )->getRowArray();
            if (!$g1 || (int) $g1['guru_id'] !== $guruId) {
                throw new DomainException('Penugasan Guru sudah berubah. Muat ulang halaman.');
            }
            $latest = $this->db->table('percobaan_ujian')
                ->select('id')->where('progres_tp_id', (int) $p['id'])
                ->where('jenis', $ujian['jenis'])->where('tahap', $ujian['tahap'])
                ->orderBy('id', 'DESC')->get(1)->getRowArray();
            if ((int) ($latest['id'] ?? 0) !== $attemptId) {
                throw new DomainException('Percobaan pengganti sudah dibuat.');
            }
            $lama = $this->db->table('keputusan_ujian_batal')
                ->where('percobaan_ujian_id', $attemptId)->get()->getRowArray();
            if ($lama !== null && $lama['keputusan'] === $aksi) {
                $this->db->transCommit(); // double click idempoten
                return;
            }
            if ($lama !== null && $lama['keputusan'] !== 'tunda_tinjauan') {
                throw new DomainException('Keputusan final sudah tersimpan.');
            }
            if ($lama !== null && $aksi === 'tunda_tinjauan') {
                $this->db->transCommit();
                return;
            }
            if ($aksi !== 'tunda_tinjauan' && $p['status'] !== 'perlu_bantuan') {
                throw new DomainException('TP tidak sedang menunggu izin akses ulang.');
            }
            // Alasan audit dibangkitkan sistem; sebab teknis tersimpan pada percobaan.
            $alasan = 'Izin akses ulang ' . ucfirst((string) $ujian['jenis'])
                . ' (' . (string) $ujian['tahap'] . ') diberikan Guru atas permintaan siswa.';
            $waktu = gmdate('Y-m-d H:i:s');
            $data = ['keputusan'=>$aksi,'guru_id'=>$guruId,'alasan'=>$alasan,
                'diputus_pada'=>$waktu,'digunakan_pada'=>null];
            if ($lama === null) {
                $this->db->table('keputusan_ujian_batal')
                    ->insert(['percobaan_ujian_id'=>$attemptId] + $data);
                $id = (int) $this->db->insertID();
            } else {
                $id = (int) $lama['id'];
                $this->db->table('keputusan_ujian_batal')
                    ->where('id',$id)->update($data);
            }
            $this->db->table('riwayat_keputusan_ujian_batal')->insert([
                'keputusan_ujian_batal_id'=>$id,'guru_id'=>$guruId,
                'keputusan_lama'=>$lama['keputusan'] ?? null,
                'keputusan_baru'=>$aksi,'alasan'=>$alasan,'terjadi_pada'=>$waktu,
            ]);
            if ($aksi !== 'tunda_tinjauan') {
                $status = match ($aksi) {
                    'ulang_diagnostik' => 'diagnostik',
                    'langsung_belajar' => 'belajar',
                    'ulang_sumatif' => $this->jalurSumatif((int) $p['id']),
                };
                $this->db->table('progres_tp')->where('id', (int) $p['id'])
                    ->where('status','perlu_bantuan')
                    ->update(['status'=>$status,'diubah_pada'=>$waktu]);
                if ($this->db->affectedRows() !== 1) {
                    throw new DomainException('Progres berubah bersamaan dengan keputusan Guru.');
                }
                if ($aksi === 'langsung_belajar') {
                    $this->db->table('keputusan_ujian_batal')
                        ->where('id',$id)->update(['digunakan_pada'=>$waktu]);
                }
            }
            $penerima=$this->db->table('progres_tp pr')
                ->select('s.pengguna_id')->join('siswa s','s.id=pr.siswa_id')
                ->where('pr.id',(int)$p['id'])->get(1)->getRowArray();
            if (!$penerima) throw new RuntimeException('Penerima izin akses ulang tidak ditemukan.');
            NotifikasiService::catat($this->db,'izin_akses_ulang',
                (int)$penerima['pengguna_id'],$id);
            if (!$this->db->transStatus()) throw new RuntimeException('Keputusan gagal disimpan.');
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Memulihkan jalur sah sebelum Sumatif tidak selesai; tanpa mengulang materi. */
    private function jalurSumatif(int $progresId): string
    {
        $diag = $this->db->table('percobaan_ujian')
            ->select('skor,kkm')->where('progres_tp_id',$progresId)
            ->where('jenis','diagnostik')->where('status','selesai_sah')
            ->orderBy('id','DESC')->get(1)->getRowArray();
        if ($diag !== null) {
            return (float) $diag['skor'] >= (int) $diag['kkm']
                ? 'sumatif' : 'belajar';
        }
        $langsung = $this->db->table('keputusan_ujian_batal k')
            ->select('k.id')->join('percobaan_ujian p','p.id=k.percobaan_ujian_id')
            ->where('p.progres_tp_id',$progresId)
            ->where('p.jenis','diagnostik')->where('k.keputusan','langsung_belajar')
            ->get(1)->getRowArray();
        if ($langsung) return 'belajar';
        throw new DomainException('Jalur hasil Diagnostik tidak dapat dipastikan.');
    }

    /** Verifikasi grant di transaksi siswa; tidak pernah mempercayai data browser. */
    public static function izin(BaseConnection $db, int $attemptId, string $aksi): ?array
    {
        return $db->table('keputusan_ujian_batal')
            ->where('percobaan_ujian_id',$attemptId)
            ->where('keputusan',$aksi)->where('digunakan_pada',null)
            ->get()->getRowArray();
    }

    /** Digunakan satu kali setelah percobaan pengganti berhasil dibuat. */
    public static function gunakan(BaseConnection $db, int $id): void
    {
        $db->table('keputusan_ujian_batal')->where('id',$id)
            ->where('digunakan_pada',null)
            ->update(['digunakan_pada'=>gmdate('Y-m-d H:i:s')]);
        if ($db->affectedRows() !== 1) {
            throw new DomainException('Izin ulang ujian sudah digunakan.');
        }
    }
}
