<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A8: jadwal remedial/susulan oleh Admin dan peserta susulan terpilih.
 * Memerlukan SQL V4-0009; sesi utama tetap di ujian_pra_semester.
 */
final class PraSemesterSesiService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array => (new AuthService())->penggunaSaatIni();
    }

    private function admin(): int
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'admin'
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Admin aktif yang mengelola susulan/remedial.');
        }
        return (int)$a['id'];
    }

    /** Sumber setiap penentuan kesempatan: riwayat satu ujian+siswa dari server. */
    public function riwayat(int $ujianId, int $siswaId): array
    {
        return $this->db->table('percobaan_ujian')
            ->select('id,tahap,status,skor,kkm,nomor_percobaan,token_publik,jadwal_pra_sesi_id')
            ->where('ujian_pra_semester_id',$ujianId)
            ->where('siswa_id',$siswaId)->where('jenis','pra_semester')
            ->orderBy('id','ASC')->get()->getResultArray();
    }

    /** Dipanggil setelah cek hak siswa+pembuktian kelayakan TP oleh engine. */
    public function sesiYangTerbuka(array $ujian, int $siswaId, string $fase, int $now): ?array
    {
        $ujianId=(int)$ujian['id'];
        if ($fase === 'belum_mulai') {
            $awal=AturanPraSemesterService::jadwal(
                $ujian['mulai_pada'],$ujian['selesai_pada'],$now
            );
            if ($awal['boleh_mulai'] && $ujian['status'] === 'siap') {
                return [
                    'id'=>null,'jenis'=>'utama','tahap'=>'normal',
                    'mulai'=>$ujian['mulai_pada'],'akhir'=>$ujian['selesai_pada'],
                    'durasi_menit'=>(int)$ujian['durasi_menit'],
                    'peserta_ditetapkan'=>true,
                ];
            }
        }
        $rows=$this->db->table('jadwal_pra_sesi j')
            ->select('j.id,j.jenis,j.tahap,j.mulai_pada,j.selesai_pada,j.durasi_menit,ps.id AS ditunjuk')
            ->join('peserta_pra_susulan ps',
                'ps.jadwal_pra_sesi_id=j.id AND ps.siswa_id=' . (int)$siswaId,'left')
            ->where('j.ujian_pra_semester_id',$ujianId)
            ->where('j.status','siap')->where('j.mulai_pada <=',
                (new \DateTimeImmutable('@'.$now))->setTimezone(new \DateTimeZone('Asia/Jakarta'))
                    ->format('Y-m-d H:i:s'))
            ->where('j.selesai_pada >',
                (new \DateTimeImmutable('@'.$now))->setTimezone(new \DateTimeZone('Asia/Jakarta'))
                    ->format('Y-m-d H:i:s'))
            ->orderBy('j.id','DESC')->get()->getResultArray();
        foreach ($rows as $r) {
            $sesuai = match ($fase) {
                'belum_mulai','butuh_susulan_normal' =>
                    $r['jenis'] === 'susulan' && $r['tahap'] === 'normal'
                    && $r['ditunjuk'] !== null,
                'menunggu_remedial' =>
                    $r['jenis'] === 'remedial' && $r['tahap'] === 'remedial',
                'butuh_susulan_remedial' =>
                    $r['jenis'] === 'susulan' && $r['tahap'] === 'remedial'
                    && $r['ditunjuk'] !== null,
                default => false,
            };
            if ($sesuai) {
                return [
                    'id'=>(int)$r['id'],'jenis'=>(string)$r['jenis'],
                    'tahap'=>(string)$r['tahap'],
                    'mulai'=>$r['mulai_pada'],'akhir'=>$r['selesai_pada'],
                    'durasi_menit'=>(int)$r['durasi_menit'],
                    'peserta_ditetapkan'=>$r['ditunjuk'] !== null,
                ];
            }
        }
        return null;
    }

    public function daftar(int $paketId): array
    {
        $this->admin();
        $this->ujianPaket($paketId);
        return $this->db->table('jadwal_pra_sesi j')
            ->select('j.*,COUNT(ps.id) AS jumlah_peserta')
            ->join('ujian_pra_semester u','u.id=j.ujian_pra_semester_id')
            ->join('peserta_pra_susulan ps','ps.jadwal_pra_sesi_id=j.id','left')
            ->where('u.paket_semester_id',$paketId)
            ->groupBy('j.id')
            ->orderBy('j.mulai_pada','DESC')->get()->getResultArray();
    }

    private function ujianPaket(int $paketId): array
    {
        $row=$this->db->table('ujian_pra_semester u')
            ->select('u.*,p.angkatan_id,p.mapel_id,p.semester,p.status AS status_paket')
            ->join('paket_semester p','p.id=u.paket_semester_id')
            ->where('u.paket_semester_id',$paketId)->get()->getRowArray();
        if (!$row || $row['status_paket'] !== 'aktif' || $row['status'] !== 'siap') {
            throw new DomainException('Jadwal hanya dapat dibuat setelah ujian utama siap.');
        }
        return $row;
    }

    /** Transaksi mencakup lock Gen untuk cegah balapan jadwal antar-mapel. */
    public function buat(int $paketId, string $jenis, string $tahap,
        mixed $awal, mixed $akhir, mixed $durasi): int
    {
        $admin=$this->admin();
        if (!in_array($jenis,['remedial','susulan'],true)
            || !in_array($tahap,['normal','remedial'],true)
            || ($jenis === 'remedial' && $tahap !== 'remedial')) {
            throw new DomainException('Jenis atau tahap sesi tidak valid.');
        }
        $form=PraSemesterJadwalService::validasiForm($awal,$akhir,$durasi);
        $jadwal=AturanPraSemesterService::jadwal($form['mulai'],$form['selesai'],time());
        if ($jadwal['status'] !== 'belum_mulai') {
            throw new DomainException('Jadwal remedial/susulan wajib dimulai di masa depan.');
        }
        $this->db->transBegin();
        try {
            $row=$this->ujianPaket($paketId);
            $lock=$this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $this->db->query('SELECT id FROM ref_angkatan WHERE id=?'.$lock,
                [(int)$row['angkatan_id']])->getRowArray();
            $this->db->query('SELECT id FROM ujian_pra_semester WHERE id=?'.$lock,
                [(int)$row['id']])->getRowArray();
            if (!$row['selesai_pada'] || $form['mulai'] < $row['selesai_pada']) {
                throw new DomainException('Jadwal tambahan wajib setelah akhir jendela ujian utama.');
            }
            // Cegah tabrakan semua sesi (utama + khusus) untuk Gen–Semester.
            $benturanUtama=$this->db->table('ujian_pra_semester u')
                ->join('paket_semester p','p.id=u.paket_semester_id')
                ->where('p.angkatan_id',(int)$row['angkatan_id'])
                ->where('p.semester',(int)$row['semester'])
                ->where('u.status','siap')
                ->where('u.mulai_pada <',$form['selesai'])
                ->where('u.selesai_pada >',$form['mulai'])->countAllResults();
            if ($benturanUtama > 0) throw new DomainException('Jadwal berbenturan dengan ujian utama Gen ini.');
            $benturanSesi=$this->db->table('jadwal_pra_sesi j')
                ->join('ujian_pra_semester u','u.id=j.ujian_pra_semester_id')
                ->join('paket_semester p','p.id=u.paket_semester_id')
                ->where('p.angkatan_id',(int)$row['angkatan_id'])
                ->where('p.semester',(int)$row['semester'])
                ->where('j.status','siap')
                ->where('j.mulai_pada <',$form['selesai'])
                ->where('j.selesai_pada >',$form['mulai'])->countAllResults();
            if ($benturanSesi > 0) throw new DomainException('Jadwal berbenturan dengan sesi lain pada Gen yang sama.');
            $this->db->table('jadwal_pra_sesi')->insert([
                'ujian_pra_semester_id'=>(int)$row['id'],
                'jenis'=>$jenis,'tahap'=>$tahap,
                'mulai_pada'=>$form['mulai'],'selesai_pada'=>$form['selesai'],
                'durasi_menit'=>$form['durasi'],'status'=>'siap',
                'dibuat_oleh'=>$admin,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
            $id=(int)$this->db->insertID();
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'pra_sesi_dijadwalkan',
                'catatan'=>'Paket #' . $paketId . ', sesi #' . $id . ', '
                    . $jenis . '/' . $tahap . ', ' . $form['mulai']
                    . '—' . $form['selesai'] . ', durasi ' . $form['durasi'] . ' menit.',
                'waktu'=>gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Jadwal tidak tersimpan.');
            $this->db->transCommit();
            return $id;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Hanya peserta yang benar-benar tidak hadir atau attempt-nya batal. */
    public function calonSusulan(int $paketId, int $sesiId): array
    {
        $this->admin();
        $ujian=$this->ujianPaket($paketId);
        $sesi=$this->db->table('jadwal_pra_sesi')
            ->where('id',$sesiId)->where('ujian_pra_semester_id',(int)$ujian['id'])
            ->where('jenis','susulan')->get()->getRowArray();
        if (!$sesi) throw new DomainException('Sesi susulan tidak tersedia.');
        $rows=$this->db->table('siswa s')->select('s.id,p.nama')
            ->join('pengguna p','p.id=s.pengguna_id')
            ->where('s.angkatan_id',(int)$ujian['angkatan_id'])
            ->where('p.status','aktif')
            ->orderBy('p.nama')->get()->getResultArray();
        $sudah=$this->db->table('peserta_pra_susulan')
            ->select('siswa_id')->where('jadwal_pra_sesi_id',$sesiId)->get()->getResultArray();
        $terpilih=array_fill_keys(array_map(
            static fn(array $x):int=>(int)$x['siswa_id'],$sudah
        ),true);
        $hasil=[];
        foreach ($rows as $s) {
            $id=(int)$s['id'];
            $histori=$this->riwayat((int)$ujian['id'],$id);
            $fase=PenilaianPraSemesterService::ringkas($histori)['fase'];
            $benar=$sesi['tahap'] === 'normal'
                ? in_array($fase,['belum_mulai','butuh_susulan_normal'],true)
                : $fase === 'butuh_susulan_remedial';
            if ($fase === 'belum_mulai' &&
                (string)$ujian['selesai_pada'] >
                (new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta')))
                    ->format('Y-m-d H:i:s')) {
                $benar=false; // belum dapat dianggap tidak hadir sebelum jendela utama selesai
            }
            if ($benar || isset($terpilih[$id])) {
                $hasil[]=['siswa_id'=>$id,'nama'=>$s['nama'],
                    'fase'=>$fase,'ditetapkan'=>isset($terpilih[$id]),
                    'boleh_ditambah'=>$benar && !isset($terpilih[$id])];
            }
        }
        return $hasil;
    }

    /**
     * Additive saja: tidak menghapus/menimpa daftar peserta lama.
     * Setiap calon diperiksa ulang DALAM transaksi oleh server.
     */
    public function tambahPeserta(int $paketId,int $sesiId,array $siswaIds): int
    {
        $admin=$this->admin();
        if ($siswaIds === [] || count($siswaIds)>500) {
            throw new DomainException('Pilih minimal satu siswa (maksimal 500).');
        }
        $this->db->transBegin();
        try {
            $ujian=$this->ujianPaket($paketId);
            $lock=$this->db->DBDriver === 'SQLite3' ? '' : ' FOR UPDATE';
            $sesi=$this->db->query('SELECT * FROM jadwal_pra_sesi WHERE id=?'
                . $lock,[$sesiId])->getRowArray();
            if (!$sesi || (int)$sesi['ujian_pra_semester_id'] !== (int)$ujian['id']
                || $sesi['jenis'] !== 'susulan' || $sesi['status'] !== 'siap') {
                throw new DomainException('Sesi susulan bukan milik paket aktif.');
            }
            $now=(new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta')))
                ->format('Y-m-d H:i:s');
            if ($sesi['mulai_pada'] <= $now) {
                throw new DomainException('Penetapan peserta harus sebelum jendela susulan dimulai.');
            }
            $ids=[];
            foreach ($siswaIds as $v) {
                if (!is_string($v) && !is_int($v)) throw new DomainException('ID siswa tidak valid.');
                if (!ctype_digit((string)$v) || (int)$v < 1) {
                    throw new DomainException('ID siswa tidak valid.');
                }
                $ids[(int)$v]=true;
            }
            // Audit kelayakan berdasarkan attempt sah dan status batal.
            $total=0;
            foreach (array_keys($ids) as $siswaId) {
                $siswa=$this->db->table('siswa')->select('id')
                    ->where('id',$siswaId)
                    ->where('angkatan_id',(int)$ujian['angkatan_id'])
                    ->get()->getRowArray();
                if (!$siswa) throw new DomainException('Siswa tidak berada di Gen ini.');
                $fase=PenilaianPraSemesterService::ringkas(
                    $this->riwayat((int)$ujian['id'],$siswaId)
                )['fase'];
                $sesuai=$sesi['tahap']==='normal'
                    ? in_array($fase,['belum_mulai','butuh_susulan_normal'],true)
                    : $fase==='butuh_susulan_remedial';
                if (!$sesuai || ($fase==='belum_mulai' && $ujian['selesai_pada']>$now)) {
                    throw new DomainException('Siswa tidak memenuhi syarat susulan tahap ini.');
                }
                $exists=$this->db->table('peserta_pra_susulan')
                    ->where('jadwal_pra_sesi_id',$sesiId)
                    ->where('siswa_id',$siswaId)->countAllResults();
                if ($exists) continue;
                // Siswa tidak boleh diberi dua sesi susulan masa depan untuk tahap sama.
                $duplikat=$this->db->table('peserta_pra_susulan ps')
                    ->join('jadwal_pra_sesi j','j.id=ps.jadwal_pra_sesi_id')
                    ->where('ps.siswa_id',$siswaId)
                    ->where('j.ujian_pra_semester_id',(int)$ujian['id'])
                    ->where('j.tahap',$sesi['tahap'])
                    ->where('j.status','siap')->where('j.selesai_pada >',$now)
                    ->countAllResults();
                if ($duplikat) {
                    throw new DomainException('Siswa sudah terdaftar pada sesi susulan lain yang belum berakhir.');
                }
                $this->db->table('peserta_pra_susulan')->insert([
                    'jadwal_pra_sesi_id'=>$sesiId,'siswa_id'=>$siswaId,
                    'ditetapkan_oleh'=>$admin,'ditetapkan_pada'=>gmdate('Y-m-d H:i:s'),
                ]);
                $total++;
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$admin,'peran_aktif'=>'admin',
                'peristiwa'=>'pra_susulan_peserta',
                'catatan'=>'Sesi susulan #' . $sesiId . ' paket #'.$paketId
                    . ', tambahan peserta: '.$total,
                'waktu'=>gmdate('Y-m-d H:i:s'),
            ]);
            if (!$this->db->transStatus()) throw new RuntimeException('Daftar peserta gagal disimpan.');
            $this->db->transCommit();
            return $total;
        } catch (Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
