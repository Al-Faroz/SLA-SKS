<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A12-2: ringkasan pekerjaan Guru G1, baca-saja dan terbatas pada Gen–Mapel
 * yang ditugaskan. Tidak melakukan sinkronisasi nilai atau progres.
 */
final class DashboardGuruService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db=null, ?callable $akun=null)
    {
        $this->db=$db ?? db_connect();
        $this->akun=$akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function guruId(): int
    {
        $a=($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '')!=='guru'
            || ($a['status'] ?? '')!=='aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1)!==0
            || (int)($a['id'] ?? 0)<1) {
            throw new DomainException('Dashboard hanya untuk Guru aktif.');
        }
        return (int)$a['id'];
    }

    /** @return array<string,mixed> */
    public function ringkasan(?int $index=0, int $semester=0): array
    {
        $guru=$this->guruId();
        if ($semester<0 || $semester>6) {
            throw new DomainException('Semester dashboard tidak sah.');
        }
        $pilihan=(new PetaTpService($this->db,$this->akun))->genMapelDiampu();
        $dipilih=PetaTpService::pilihPenugasan($pilihan,$index);
        $nol=[
            'paket'=>0,'bab'=>0,'tp_total'=>0,'tp_siap'=>0,
            'tp_draf'=>0,'tp_tanpa_materi'=>0,'antrean_resume'=>0,
            'antrean_hots'=>0,'perlu_bantuan'=>0,'ujian_berjalan'=>0,
        ];
        if ($dipilih===null) {
            return ['pilihan'=>$pilihan,'index'=>$index,'terpilih'=>null,
                'semester'=>$semester,'ringkasan'=>$nol,'baris'=>[]];
        }

        // Guard kedua: jangan percaya indeks pilihan POST/GET sebagai hak.
        $gen=(int)$dipilih['angkatan_id'];
        $mapel=(int)$dipilih['mapel_id'];
        $g1=$this->db->table('penugasan_guru_gen')
            ->where('guru_id',$guru)->where('angkatan_id',$gen)
            ->where('mapel_id',$mapel)->countAllResults();
        if ($g1!==1) throw new DomainException('Penugasan Guru telah berubah.');

        $q=$this->db->table('paket_semester p')
            ->select('p.id,p.semester')
            ->where('p.angkatan_id',$gen)
            ->where('p.mapel_id',$mapel)
            ->where('p.status','aktif');
        if ($semester>0) $q->where('p.semester',$semester);
        $paket=$q->orderBy('p.semester','ASC')->orderBy('p.id','ASC')
            ->get()->getResultArray();

        $hasil=$nol;
        $hasil['paket']=count($paket);
        if ($paket===[]) {
            return ['pilihan'=>$pilihan,'index'=>$index,'terpilih'=>$dipilih,
                'semester'=>$semester,'ringkasan'=>$hasil,'baris'=>[]];
        }

        $ids=array_map(static fn (array $p): int => (int)$p['id'],$paket);
        $map=[];
        foreach ($paket as $p) {
            $id=(int)$p['id'];
            $map[$id]=[
                'id'=>$id,'semester'=>(int)$p['semester'],
                'bab'=>0,'tp_total'=>0,'tp_siap'=>0,'tp_draf'=>0,
                'tp_tanpa_materi'=>0,'antrean_resume'=>0,
                'antrean_hots'=>0,'perlu_bantuan'=>0,'ujian_berjalan'=>0,
            ];
        }

        foreach ($this->db->table('bab')
            ->select('paket_semester_id,COUNT(*) AS jumlah',false)
            ->whereIn('paket_semester_id',$ids)
            ->groupBy('paket_semester_id')->get()->getResultArray() as $r) {
            $map[(int)$r['paket_semester_id']]['bab']=(int)$r['jumlah'];
        }

        $tp=$this->db->table('tp t')
            ->select('t.id,t.paket_semester_id,t.status')
            ->whereIn('t.paket_semester_id',$ids)
            ->where('t.status !=','arsip')->get()->getResultArray();
        $tpIds=[];
        foreach ($tp as $t) {
            $id=(int)$t['id'];
            $pid=(int)$t['paket_semester_id'];
            $tpIds[$id]=$pid;
            $map[$pid]['tp_total']++;
            if ($t['status']==='siap') $map[$pid]['tp_siap']++;
            if ($t['status']==='draf') $map[$pid]['tp_draf']++;
        }

        if ($tpIds!==[]) {
            $jumlahMateri=[];
            foreach ($this->db->table('materi')
                ->select('tp_id,COUNT(*) AS jumlah',false)
                ->whereIn('tp_id',array_keys($tpIds))
                ->where('aktif',1)->groupBy('tp_id')
                ->get()->getResultArray() as $r) {
                $jumlahMateri[(int)$r['tp_id']]=(int)$r['jumlah'];
            }
            foreach ($tpIds as $tid=>$pid) {
                // Tepat satu Materi aktif diperlukan untuk Esensial DAN NonEsensial.
                if (($jumlahMateri[$tid]??0)!==1) $map[$pid]['tp_tanpa_materi']++;
            }
        }

        // Jawaban final bernilai NULL, bukan skor 0: sama dengan gerbang
        // penilaian Guru. Snapshot versi dan Gen siswa harus sesuai.
        foreach (['resume_materi'=>'antrean_resume',
            'jawaban_hots_materi'=>'antrean_hots'] as $tabel=>$kolom) {
            $rows=$this->db->table($tabel.' r')
                ->select('pr.paket_semester_id,COUNT(DISTINCT r.id) AS jumlah',false)
                ->join('progres_tp pr','pr.id=r.progres_tp_id')
                ->join('siswa s','s.id=pr.siswa_id')
                ->join('tp t','t.id=pr.tp_id AND t.paket_semester_id=pr.paket_semester_id')
                ->join('materi m','m.id=r.materi_id AND m.tp_id=pr.tp_id')
                ->join('versi_konten_tp v','v.id=r.versi_konten_tp_id AND v.tp_id=t.id')
                ->whereIn('pr.paket_semester_id',$ids)
                ->where('s.angkatan_id',$gen)
                ->where('r.versi_konten_tp_id=pr.versi_konten_tp_id',null,false)
                ->where('r.status','final')
                ->where('r.nilai',null)->where('r.dinilai_oleh',null)
                ->where('t.jenis_tp','non_esensial')
                ->where('t.status !=','arsip')
                ->groupBy('pr.paket_semester_id')->get()->getResultArray();
            foreach ($rows as $r) {
                $map[(int)$r['paket_semester_id']][$kolom]=(int)$r['jumlah'];
            }
        }

        foreach ($this->db->table('progres_tp pr')
            ->select('pr.paket_semester_id,COUNT(DISTINCT pr.siswa_id) AS jumlah',false)
            ->join('siswa s','s.id=pr.siswa_id')
            ->join('tp t','t.id=pr.tp_id AND t.paket_semester_id=pr.paket_semester_id')
            ->whereIn('pr.paket_semester_id',$ids)
            ->where('s.angkatan_id',$gen)
            ->where('pr.status','perlu_bantuan')
            ->where('t.jenis_tp','non_esensial')->where('t.status !=','arsip')
            ->groupBy('pr.paket_semester_id')->get()->getResultArray() as $r) {
            $map[(int)$r['paket_semester_id']]['perlu_bantuan']=(int)$r['jumlah'];
        }

        foreach ($this->db->table('percobaan_ujian u')
            ->select('pr.paket_semester_id,COUNT(DISTINCT u.id) AS jumlah',false)
            ->join('progres_tp pr','pr.id=u.progres_tp_id')
            ->join('siswa s','s.id=pr.siswa_id')
            ->join('tp t','t.id=pr.tp_id AND t.paket_semester_id=pr.paket_semester_id')
            ->whereIn('pr.paket_semester_id',$ids)
            ->where('s.angkatan_id',$gen)
            ->whereIn('u.jenis',['diagnostik','sumatif'])
            ->where('u.status','berjalan')
            ->where('t.jenis_tp','non_esensial')->where('t.status !=','arsip')
            ->groupBy('pr.paket_semester_id')->get()->getResultArray() as $r) {
            $map[(int)$r['paket_semester_id']]['ujian_berjalan']=(int)$r['jumlah'];
        }

        $baris=array_values($map);
        foreach ($baris as $row) {
            foreach ($nol as $key=>$_) {
                if ($key==='paket') continue;
                $hasil[$key]+=$row[$key];
            }
        }
        return ['pilihan'=>$pilihan,'index'=>$index,'terpilih'=>$dipilih,
            'semester'=>$semester,'ringkasan'=>$hasil,'baris'=>$baris];
    }
}
