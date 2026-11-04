<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A14-2: pengajuan Guru G1 dan keputusan Admin atas nilai Resume/HOTS siswa.
 *
 * Tidak pernah mengedit soal, kunci, percobaan ujian, KKM, TP atau PDF sertifikat.
 * Penilaian awal dan dokumen historis tetap utuh; perubahan sumber/nilai_tp
 * hanya melalui keputusan Admin lain dan dalam satu transaksi atomik.
 */
final class KoreksiNilaiService
{
    private BaseConnection $db;
    /** @var callable():?array */
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn(): ?array => (new AuthService())->penggunaSaatIni();
    }

    /** @return array<string,mixed> */
    private function pelaku(string $role): array
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== $role
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0
            || (int)($a['id'] ?? 0) < 1) {
            throw new DomainException('Akses koreksi nilai tidak diizinkan untuk peran aktif.');
        }
        $this->skema();
        return $a;
    }

    private function skema(): void
    {
        foreach (['permohonan_koreksi_nilai','riwayat_koreksi_nilai','notifikasi'] as $nama) {
            if (!$this->db->tableExists($nama)) {
                throw new RuntimeException(
                    'Modul koreksi memerlukan patch V4-0013 dan V4-0014 yang belum terpasang.'
                );
            }
        }
    }

    private static function tabel(string $jenis): string
    {
        return match($jenis) {
            'resume' => 'resume_materi',
            'hots' => 'jawaban_hots_materi',
            default => throw new DomainException('Jenis penilaian untuk koreksi tidak sah.'),
        };
    }

    /**
     * Memetakan sumber nilai dengan TP terpin, siswa, paket dan G1 yang kini
     * bertugas. Data siswa dibaca hanya pada konteks Admin/Guru yang sah.
     * @return array<string,mixed>
     */
    private function sumber(string $jenis, int $id): array
    {
        if ($id < 1) throw new DomainException('Referensi penilaian tidak sah.');
        $tabel = self::tabel($jenis);
        $row = $this->db->table($tabel . ' r')
            ->select('r.id,r.progres_tp_id,r.materi_id,r.versi_konten_tp_id,'
                .'r.status AS status_sumber,r.nilai AS nilai_sumber,'
                .'r.dinilai_oleh,r.dinilai_pada,'
                .'pr.tp_id,pr.siswa_id,pr.versi_konten_tp_id AS versi_progres,'
                .'pr.paket_semester_id,pr.status AS status_progres,'
                .'s.pengguna_id AS pengguna_siswa,s.angkatan_id AS angkatan_siswa,'
                .'u.nama AS nama_siswa,p.angkatan_id,p.mapel_id,p.semester,'
                .'t.kode_tp,t.jenis_tp,g.guru_id AS guru_g1,'
                .'n.id AS nilai_tp_id,n.jalur,n.nilai_resume,n.nilai_hots,'
                .'n.nilai_sumatif,n.nilai_tp,n.status_nilai')
            ->join('progres_tp pr','pr.id=r.progres_tp_id')
            ->join('tp t','t.id=pr.tp_id')
            ->join('paket_semester p',
                'p.id=pr.paket_semester_id AND p.id=t.paket_semester_id')
            ->join('siswa s','s.id=pr.siswa_id')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->join('penugasan_guru_gen g',
                'g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id')
            ->join('nilai_tp n','n.progres_tp_id=pr.id','left')
            ->where('r.id',$id)->get(1)->getRowArray();
        if (!$row
            || $row['jenis_tp'] !== 'non_esensial'
            || $row['status_sumber'] !== 'final'
            || $row['nilai_sumber'] === null
            || $row['dinilai_oleh'] === null
            || (int)$row['versi_konten_tp_id'] !== (int)$row['versi_progres']
            || (int)$row['angkatan_siswa'] !== (int)$row['angkatan_id']
            || !$row['nilai_tp_id'] || $row['nilai_sumatif'] === null
            || $row['status_progres'] !== 'tuntas') {
            throw new DomainException(
                'Nilai dan snapshot siswa belum memenuhi syarat koreksi. '
                .'Pastikan TP selesai serta skor Sumatif dan hasil Guru tersedia.'
            );
        }
        return $row;
    }

    /** @return array<string,mixed> */
    private function pratinjauSumber(array $r, string $jenis, int $usulan): array
    {
        return KoreksiNilaiDampakService::pratinjau(
            $jenis,(int)$r['nilai_sumber'],$usulan,(string)$r['jalur'],
            $r['nilai_resume']===null?null:(float)$r['nilai_resume'],
            $r['nilai_hots']===null?null:(float)$r['nilai_hots'],
            (float)$r['nilai_sumatif'],(string)$r['status_nilai'],
            $r['nilai_tp']===null?null:(float)$r['nilai_tp']
        );
    }

    /** @return array{tugas:array<string,mixed>,dampak:array<string,mixed>} */
    public function pratinjauGuru(string $jenis, int $sumberId, int $nilaiUsulan): array
    {
        $a=$this->pelaku('guru');
        PenilaianGuruService::skor($nilaiUsulan);
        // Termasuk pembuktian checksum snapshot dari layanan penilaian resmi.
        (new PenilaianGuruService($this->db,$this->akun))->lihat($jenis,$sumberId);
        $r=$this->sumber($jenis,$sumberId);
        if ((int)$r['guru_g1'] !== (int)$a['id']) {
            throw new DomainException('Penugasan Gen–Mapel Guru sudah berubah.');
        }
        return ['tugas'=>$r,'dampak'=>$this->pratinjauSumber($r,$jenis,$nilaiUsulan)];
    }

    /** @return array<string,mixed> */
    public function permohonan(int $id): array
    {
        $a=($this->akun)();
        if (!$a || !in_array($a['peran_aktif']??null,['guru','admin'],true)) {
            throw new DomainException('Akses permohonan ditolak.');
        }
        $this->pelaku((string)$a['peran_aktif']);
        $q=$this->db->table('permohonan_koreksi_nilai k')
            ->select('k.*,s.pengguna_id AS pengguna_siswa,u.nama AS nama_siswa,'
                .'t.kode_tp,p.semester,p.angkatan_id,p.mapel_id')
            ->join('progres_tp pr','pr.id=k.progres_tp_id')
            ->join('siswa s','s.id=pr.siswa_id')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->join('tp t','t.id=pr.tp_id')
            ->join('paket_semester p','p.id=pr.paket_semester_id')
            ->where('k.id',$id);
        if ($a['peran_aktif']==='guru') {
            $q->where('k.guru_id',(int)$a['id']);
        }
        $r=$q->get(1)->getRowArray();
        if (!$r) throw new DomainException('Permohonan tidak ditemukan untuk akun Anda.');
        return $r;
    }

    /** @return array{baris:array<int,array<string,mixed>>,halaman:int,total:int,jumlah_halaman:int} */
    public function daftar(string $role, int $halaman=1): array
    {
        $a=$this->pelaku($role);
        if (!in_array($role,['guru','admin'],true)) {
            throw new DomainException('Peran daftar tidak sah.');
        }
        $halaman=max(1,min(100000,$halaman));
        $base=static function(BaseConnection $db) {
            return $db->table('permohonan_koreksi_nilai k')
                ->select('k.id,k.jenis,k.sumber_id,k.progres_tp_id,k.nilai_awal,'
                    .'k.nilai_usulan,k.nilai_tp_awal,k.nilai_tp_usulan,'
                    .'k.status,k.versi_koreksi,k.guru_id,k.perlu_tinjau_sertifikat,'
                    .'k.dibuat_pada,k.diputus_pada,'
                    .'u.nama AS nama_siswa,t.kode_tp,p.semester')
                ->join('progres_tp pr','pr.id=k.progres_tp_id')
                ->join('siswa s','s.id=pr.siswa_id')
                ->join('pengguna u','u.id=s.pengguna_id')
                ->join('tp t','t.id=pr.tp_id')
                ->join('paket_semester p','p.id=pr.paket_semester_id');
        };
        $q=$base($this->db);
        if ($role==='guru')$q->where('k.guru_id',(int)$a['id']);
        $count=$q->countAllResults();
        $pages=max(1,(int)ceil($count/20));
        $halaman=min($pages,$halaman);
        $q=$base($this->db);
        if ($role==='guru')$q->where('k.guru_id',(int)$a['id']);
        return ['baris'=>$q->orderBy('k.id','DESC')
            ->get(20,($halaman-1)*20)->getResultArray(),
            'halaman'=>$halaman,'jumlah_halaman'=>$pages,'total'=>$count];
    }

    public function ajukan(string $jenis, int $sumberId, int $nilaiUsulan, string $alasan): int
    {
        $a=$this->pelaku('guru');
        PenilaianGuruService::skor($nilaiUsulan);
        $alasan=trim($alasan);
        if (mb_strlen($alasan,'UTF-8')<10 || mb_strlen($alasan,'UTF-8')>500) {
            throw new DomainException('Alasan koreksi wajib 10–500 karakter.');
        }
        // Pembuktian snapshot dan Gen–Mapel sebelum transaksi; diulang
        // setelah kunci progres disimpan.
        $awal=$this->pratinjauGuru($jenis,$sumberId,$nilaiUsulan);
        if (!$awal['dampak']['perubahan_sumber']) {
            throw new DomainException('Nilai usulan sama dengan nilai yang berlaku.');
        }
        $this->db->transBegin();
        try {
            $lock=$this->db->DBDriver==='SQLite3'?'':' FOR UPDATE';
            $this->db->query('SELECT id FROM progres_tp WHERE id=?'.$lock,
                [(int)$awal['tugas']['progres_tp_id']]);
            $baru=$this->pratinjauGuru($jenis,$sumberId,$nilaiUsulan);
            $r=$baru['tugas'];$d=$baru['dampak'];
            $sebelumnya=$this->db->table('permohonan_koreksi_nilai')
                ->where('jenis',$jenis)->where('sumber_id',$sumberId)
                ->orderBy('versi_koreksi','DESC')->get(1)->getRowArray();
            if ($sebelumnya && $sebelumnya['status']==='diajukan') {
                if ((int)$sebelumnya['guru_id']===(int)$a['id']
                    && (int)$sebelumnya['nilai_awal']===$d['nilai_sumber_lama']
                    && (int)$sebelumnya['nilai_usulan']===$nilaiUsulan
                    && (string)$sebelumnya['alasan']===$alasan) {
                    $this->db->transCommit();
                    return (int)$sebelumnya['id'];
                }
                throw new DomainException('Sudah ada pengajuan terbuka untuk nilai ini.');
            }
            $versi=(int)($sebelumnya['versi_koreksi']??0)+1;
            $now=gmdate('Y-m-d H:i:s');
            $this->db->table('permohonan_koreksi_nilai')->insert([
                'jenis'=>$jenis,'sumber_id'=>$sumberId,
                'progres_tp_id'=>(int)$r['progres_tp_id'],
                'versi_konten_tp_id'=>(int)$r['versi_konten_tp_id'],
                'versi_koreksi'=>$versi,
                'nilai_awal'=>$d['nilai_sumber_lama'],
                'nilai_usulan'=>$d['nilai_sumber_usulan'],
                'nilai_tp_awal'=>$d['nilai_tp_lama'],
                'nilai_tp_usulan'=>$d['nilai_tp_usulan'],
                'status_nilai_awal'=>$d['status_nilai_lama'],
                'alasan'=>$alasan,'guru_id'=>(int)$a['id'],
                'status'=>'diajukan','dibuat_pada'=>$now,
            ]);
            $id=(int)$this->db->insertID();
            if ($id<1)throw new RuntimeException('Pengajuan tidak tersimpan.');
            $this->riwayat($id,'diajukan',(int)$a['id'],'guru',$d,$versi,$now);
            $this->audit((int)$a['id'],'guru','koreksi_nilai_diajukan',
                'Permohonan #'.$id.'; sumber '.$jenis.' #'.$sumberId,$now);
            $admins=$this->db->table('pengguna u')
                ->select('u.id')->join('pengguna_peran pr','pr.pengguna_id=u.id')
                ->where('u.status','aktif')->where('pr.peran','admin')
                ->where('u.id !=',(int)$a['id'])->get()->getResultArray();
            foreach ($admins as $admin) {
                NotifikasiService::catat($this->db,'permohonan_koreksi_nilai',
                    (int)$admin['id'],$id,$versi);
            }
            if (!$this->db->transStatus())throw new RuntimeException('Pengajuan koreksi gagal.');
            $this->db->transCommit();
            return $id;
        }catch(Throwable $e){
            $this->db->transRollback();
            throw $e;
        }
    }

    /** @return array{status:string,perlu_tinjau_sertifikat:bool} */
    public function putuskan(int $id, string $keputusan, string $catatan): array
    {
        $a=$this->pelaku('admin');
        if (!in_array($keputusan,['disetujui','ditolak'],true)) {
            throw new DomainException('Keputusan Admin harus Setujui atau Tolak.');
        }
        $catatan=trim($catatan);
        if (mb_strlen($catatan,'UTF-8')<10 || mb_strlen($catatan,'UTF-8')>500) {
            throw new DomainException('Catatan keputusan wajib 10–500 karakter.');
        }
        $this->db->transBegin();
        try {
            $lock=$this->db->DBDriver==='SQLite3'?'':' FOR UPDATE';
            $k=$this->db->query(
                'SELECT * FROM permohonan_koreksi_nilai WHERE id=?'.$lock,[$id]
            )->getRowArray();
            if (!$k)throw new DomainException('Permohonan koreksi tidak ditemukan.');
            if ((int)$k['guru_id']===(int)$a['id']) {
                throw new DomainException('Admin tidak boleh memutus pengajuan dari akun sendiri.');
            }
            if ($k['status']!=='diajukan') {
                throw new DomainException('Permohonan sudah diputus dan tidak dapat diulang.');
            }
            $this->db->query('SELECT id FROM progres_tp WHERE id=?'.$lock,
                [(int)$k['progres_tp_id']]);
            $r=$this->sumber((string)$k['jenis'],(int)$k['sumber_id']);
            if ((int)$r['progres_tp_id']!==(int)$k['progres_tp_id']
                || (int)$r['versi_konten_tp_id']!==(int)$k['versi_konten_tp_id']) {
                throw new DomainException('Snapshot atau progres siswa berubah; keputusan ditahan.');
            }
            $g=$this->db->table('pengguna u')->select('u.id')
                ->join('pengguna_peran pr','pr.pengguna_id=u.id')
                ->where('u.id',(int)$k['guru_id'])->where('u.status','aktif')
                ->where('pr.peran','guru')->get(1)->getRowArray();
            if (!$g || (int)$r['guru_g1']!==(int)$k['guru_id']) {
                throw new DomainException('Guru pengusul tidak lagi aktif pada Gen–Mapel terkait.');
            }
            $d=$this->pratinjauSumber($r,(string)$k['jenis'],(int)$k['nilai_usulan']);
            if ((int)$k['nilai_awal']!==$d['nilai_sumber_lama']
                || (int)$k['nilai_usulan']!==$d['nilai_sumber_usulan']
                || (string)$k['status_nilai_awal']!==$d['status_nilai_lama']
                || !self::angkaSama($k['nilai_tp_awal'],$d['nilai_tp_lama'])
                || !self::angkaSama($k['nilai_tp_usulan'],$d['nilai_tp_usulan'])) {
                throw new DomainException('Snapshot penilaian berubah; pengajuan harus dibuat ulang.');
            }
            $now=gmdate('Y-m-d H:i:s');
            $perluSertifikat=false;
            if ($keputusan==='disetujui') {
                $tabel=self::tabel((string)$k['jenis']);
                $this->db->table($tabel)->where('id',(int)$r['id'])
                    ->where('status','final')->where('nilai',(int)$k['nilai_awal'])
                    ->update(['nilai'=>(int)$k['nilai_usulan']]);
                if ($this->db->affectedRows()!==1) {
                    throw new DomainException('Nilai sumber berubah bersamaan.');
                }
                $komponen=$k['jenis']==='resume'?'nilai_resume':'nilai_hots';
                $q=$this->db->table('nilai_tp')->where('id',(int)$r['nilai_tp_id'])
                    ->where('progres_tp_id',(int)$k['progres_tp_id']);
                if ($k['nilai_tp_awal']===null) {
                    $q->where('nilai_tp',null);
                }else{
                    $q->where('nilai_tp',(float)$k['nilai_tp_awal']);
                }
                $q->update([
                    $komponen=>(int)$k['nilai_usulan'],
                    'nilai_tp'=>$d['nilai_tp_usulan'],
                    'diubah_pada'=>$now,
                ]);
                if ($this->db->affectedRows()!==1) {
                    throw new DomainException('Nilai TP berubah bersamaan.');
                }
                $perluSertifikat=$d['perlu_tinjau_sertifikat']
                    && $this->db->table('sertifikat_aktif')
                        ->where('siswa_id',(int)$r['siswa_id'])
                        ->where('paket_semester_id',(int)$r['paket_semester_id'])
                        ->countAllResults()>0;
            }
            $this->db->table('permohonan_koreksi_nilai')->where('id',$id)
                ->where('status','diajukan')->update([
                    'status'=>$keputusan,'admin_id'=>(int)$a['id'],
                    'catatan_keputusan'=>$catatan,'diputus_pada'=>$now,
                    'perlu_tinjau_sertifikat'=>$perluSertifikat?1:0,
                ]);
            if ($this->db->affectedRows()!==1) {
                throw new DomainException('Permohonan sudah diputus bersamaan.');
            }
            $this->riwayat($id,$keputusan,(int)$a['id'],'admin',
                $d,(int)$k['versi_koreksi'],$now);
            $this->audit((int)$a['id'],'admin','koreksi_nilai_'.$keputusan,
                'Permohonan #'.$id.'; sumber '.$k['jenis'].' #'.$k['sumber_id']
                .($perluSertifikat?'; perlu reviu sertifikat':''),$now);
            // Pemberitahuan keputusan dan perubahan nilai dalam transaksi yang sama.
            NotifikasiService::catat($this->db,'keputusan_koreksi_nilai',
                (int)$k['guru_id'],$id,(int)$k['versi_koreksi']);
            if ($keputusan==='disetujui') {
                NotifikasiService::catat($this->db,'koreksi_nilai_diterapkan',
                    (int)$r['pengguna_siswa'],$id,(int)$k['versi_koreksi']);
            }
            if (!$this->db->transStatus())throw new RuntimeException('Keputusan koreksi gagal.');
            $this->db->transCommit();
            return ['status'=>$keputusan,'perlu_tinjau_sertifikat'=>$perluSertifikat];
        }catch(Throwable $e){
            $this->db->transRollback();
            throw $e;
        }
    }

    private static function angkaSama($a,?float $b): bool
    {
        if ($a===null || $b===null)return $a===null && $b===null;
        return abs((float)$a-$b)<0.005;
    }

    private function riwayat(
        int $id,string $aksi,int $pelaku,string $role,array $d,int $versi,string $now
    ): void {
        $this->db->table('riwayat_koreksi_nilai')->insert([
            'permohonan_id'=>$id,'aksi'=>$aksi,'pelaku_id'=>$pelaku,
            'peran_aktif'=>$role,'nilai_awal'=>$d['nilai_sumber_lama'],
            'nilai_usulan'=>$d['nilai_sumber_usulan'],
            'nilai_tp_awal'=>$d['nilai_tp_lama'],
            'nilai_tp_usulan'=>$d['nilai_tp_usulan'],
            'versi_koreksi'=>$versi,'waktu'=>$now,
        ]);
    }

    private function audit(int $pelaku,string $role,string $event,string $catatan,string $now): void
    {
        $this->db->table('log_aktivitas')->insert([
            'pengguna_id'=>$pelaku,'peran_aktif'=>$role,
            'peristiwa'=>$event,'catatan'=>$catatan,'waktu'=>$now,
        ]);
    }

    /** @return list<array<string,mixed>> */
    public function riwayatPermohonan(int $id): array
    {
        $this->permohonan($id); // hak akses role & kepemilikan sumber
        return $this->db->table('riwayat_koreksi_nilai')
            ->where('permohonan_id',$id)->orderBy('id','ASC')
            ->get()->getResultArray();
    }
}
