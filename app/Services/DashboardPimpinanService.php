<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A12-1: dashboard Pimpinan read-only dari sumber akademik yang sah.
 * Agregasi TP berdasarkan pasangan siswa×TP, tidak mengulang nilai dan
 * tidak memasukkan Pra-Semester sebagai progres atau sertifikat.
 */
final class DashboardPimpinanService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->db = $db ?? db_connect();
        $this->akun = $akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function izinkan(): void
    {
        $a = ($this->akun)();
        if (!$a || ($a['peran_aktif'] ?? '') !== 'pimpinan'
            || ($a['status'] ?? '') !== 'aktif'
            || (int)($a['wajib_ganti_sandi'] ?? 1) !== 0
            || (int)($a['id'] ?? 0) < 1) {
            throw new DomainException('Ringkasan hanya untuk Pimpinan aktif.');
        }
    }

    /** @param array<string,int|null> $filter */
    public function ringkasan(array $filter = [], int $halaman = 1, int $perHalaman = 12): array
    {
        $this->izinkan();
        if ($halaman < 1 || $halaman > 100000
            || $perHalaman < 1 || $perHalaman > 50) {
            throw new DomainException('Halaman dashboard tidak sah.');
        }
        foreach (['gen','mapel','semester'] as $key) {
            $v=$filter[$key] ?? null;
            if ($v !== null && (!is_int($v) || $v < 1
                || $v > ($key === 'semester' ? 6 : 1000000))) {
                throw new DomainException('Filter dashboard tidak sah.');
            }
        }

        // Paket dipilih melalui service monitoring resmi, bukan dari ID URL bebas.
        $pilihan=(new LaporanAkademikService($this->db,$this->akun))
            ->paketPimpinan();
        $paket=array_values(array_filter($pilihan,static function (array $p) use ($filter): bool {
            return (($filter['gen'] ?? null) === null
                || (int)$p['angkatan_id'] === $filter['gen'])
                && (($filter['mapel'] ?? null) === null
                    || (int)$p['mapel_id'] === $filter['mapel'])
                && (($filter['semester'] ?? null) === null
                    || (int)$p['semester'] === $filter['semester']);
        }));
        $nol=[
            'siswa'=>0,'paket'=>0,'tp_wajib'=>0,'pasangan_wajib'=>0,
            'tuntas_sah'=>0,'nilai_final'=>0,'sertifikat_berlaku'=>0,
            'persen'=>null,'paket_kosong'=>0,'guru_ditugaskan'=>0,
            'pasangan_gen_mapel'=>0,'antrean_penilaian'=>0,
        ];
        if ($paket === []) {
            return ['pilihan'=>$pilihan,'filter'=>$filter,'ringkasan'=>$nol,
                'baris'=>[],'jumlah'=>0,'halaman'=>1,'jumlah_halaman'=>1];
        }

        $ids=array_map(static fn (array $p): int => (int)$p['id'],$paket);
        $genIds=array_values(array_unique(array_map(
            static fn (array $p): int => (int)$p['angkatan_id'],$paket
        )));
        $mulaiGen=[];
        foreach ($this->db->table('ref_angkatan')
            ->select('id,semester_mulai')->whereIn('id',$genIds)
            ->get()->getResultArray() as $g) {
            $mulaiGen[(int)$g['id']]=(int)$g['semester_mulai'];
        }

        // Cukup ID dan semester; tidak membaca nama/NISN atau biodata.
        $siswaGen=[];
        $seluruhSiswa=[];
        foreach ($this->db->table('siswa')
            ->select('id,angkatan_id,semester_mulai')
            ->whereIn('angkatan_id',$genIds)->get()->getResultArray() as $s) {
            $gen=(int)$s['angkatan_id'];
            $id=(int)$s['id'];
            $siswaGen[$gen][$id]=($s['semester_mulai']===null)
                ? ($mulaiGen[$gen] ?? 7) : (int)$s['semester_mulai'];
        }

        $pasanganGenMapel=[];
        $mapelIds=[];
        foreach ($paket as $p) {
            $key=(int)$p['angkatan_id'].':'.(int)$p['mapel_id'];
            $pasanganGenMapel[$key]=true;
            $mapelIds[(int)$p['mapel_id']]=true;
        }
        $guruTerisi=[];
        foreach ($this->db->table('penugasan_guru_gen')
            ->select('angkatan_id,mapel_id,guru_id')
            ->whereIn('angkatan_id',$genIds)
            ->whereIn('mapel_id',array_keys($mapelIds))
            ->get()->getResultArray() as $g) {
            $key=(int)$g['angkatan_id'].':'.(int)$g['mapel_id'];
            if (isset($pasanganGenMapel[$key]) && $g['guru_id']!==null) {
                $guruTerisi[$key]=true;
            }
        }

        $tpPerPaket=[];
        foreach ($this->db->table('tp')
            ->select('paket_semester_id,COUNT(*) AS jumlah',false)
            ->whereIn('paket_semester_id',$ids)
            ->where('jenis_tp','non_esensial')->where('status !=','arsip')
            ->groupBy('paket_semester_id')->get()->getResultArray() as $r) {
            $tpPerPaket[(int)$r['paket_semester_id']]=(int)$r['jumlah'];
        }

        // EXISTS Sumatif sah diekspresikan melalui join + DISTINCT; remedial
        // yang juga selesai sah tidak boleh menggandakan satu siswa×TP.
        $selesai=[];
        $final=[];
        $valid=$this->db->table('progres_tp pr')
            ->distinct()
            ->select('pr.paket_semester_id,pr.siswa_id,pr.tp_id,n.status_nilai,n.nilai_tp')
            ->join('tp t','t.id=pr.tp_id AND t.paket_semester_id=pr.paket_semester_id')
            ->join('percobaan_ujian u','u.progres_tp_id=pr.id AND u.jenis=\'sumatif\' AND u.status=\'selesai_sah\'')
            ->join('nilai_tp n','n.progres_tp_id=pr.id','left')
            ->whereIn('pr.paket_semester_id',$ids)
            ->where('pr.status','tuntas')
            ->where('t.jenis_tp','non_esensial')->where('t.status !=','arsip')
            ->get()->getResultArray();
        foreach ($valid as $v) {
            $pid=(int)$v['paket_semester_id'];
            $key=(int)$v['siswa_id'].':'.(int)$v['tp_id'];
            $selesai[$pid][$key]=true;
            if (($v['status_nilai'] ?? null)==='final' && $v['nilai_tp']!==null) {
                $final[$pid][$key]=true;  // skor 0 sah; NULL bukan 0
            }
        }

        $cert=[];
        foreach ($this->db->table('sertifikat_aktif a')
            ->select('a.paket_semester_id,COUNT(*) AS jumlah',false)
            ->join('sertifikat c','c.id=a.sertifikat_id AND c.status=\'berlaku\'')
            ->whereIn('a.paket_semester_id',$ids)
            ->groupBy('a.paket_semester_id')->get()->getResultArray() as $r) {
            $cert[(int)$r['paket_semester_id']]=(int)$r['jumlah'];
        }
        $pra=[];
        foreach ($this->db->table('ujian_pra_semester')
            ->select('paket_semester_id,status')
            ->whereIn('paket_semester_id',$ids)->get()->getResultArray() as $r) {
            $pra[(int)$r['paket_semester_id']]=(string)$r['status'];
        }

        $total=$nol;
        $unikSiswa=[];
        $eligibleByPaket=[];
        foreach ($paket as &$p) {
            $pid=(int)$p['id'];
            $tp=$tpPerPaket[$pid] ?? 0;
            $sem=(int)$p['semester'];
            $eligible=[];
            foreach ($siswaGen[(int)$p['angkatan_id']] ?? [] as $sid=>$mulai) {
                if ($sem >= $mulai) $eligible[(int)$sid]=true;
                $unikSiswa[(int)$sid]=true;
            }
            $sah=0; $nilai=0; $perSiswa=[];
            foreach ($selesai[$pid] ?? [] as $key=>$_) {
                [$sid]=explode(':',$key,2);
                if (!isset($eligible[(int)$sid])) continue;
                $sah++;
                $perSiswa[(int)$sid]=($perSiswa[(int)$sid] ?? 0)+1;
                if (isset($final[$pid][$key])) $nilai++;
            }
            $eligibleByPaket[$pid]=$eligible;
            $pasangan=count($eligible)*$tp;
            $p['siswa']=count($eligible);
            $p['tp_wajib']=$tp;
            $p['pasangan_wajib']=$pasangan;
            $p['tuntas_sah']=min($sah,$pasangan);
            $p['nilai_final']=min($nilai,$pasangan);
            $p['siswa_tp_selesai']=$tp===0?0:count(array_filter(
                $perSiswa,static fn (int $n): bool => $n >= $tp
            ));
            $p['persen']=$pasangan===0?null:
                round(100*$p['tuntas_sah']/$pasangan,1);
            $p['sertifikat_berlaku']=$cert[$pid]??0;
            $p['pra_status']=$pra[$pid]??null;
            $total['tp_wajib'] += $tp;
            $total['pasangan_wajib'] += $pasangan;
            $total['tuntas_sah'] += $p['tuntas_sah'];
            $total['nilai_final'] += $p['nilai_final'];
            $total['sertifikat_berlaku'] += $p['sertifikat_berlaku'];
            if ($tp===0) $total['paket_kosong']++;
        }
        unset($p);

        // Antrean Guru adalah jawaban FINAL yang nilainya belum tersimpan.
        // Keduanya dijumlahkan terpisah (Resume dan HOTS), bukan per siswa.
        $antreanPerPaket=[];
        foreach (['resume_materi','jawaban_hots_materi'] as $tabel) {
            $rows=$this->db->table($tabel.' j')
                ->select('j.id AS jawaban_id,pr.paket_semester_id,pr.siswa_id')
                ->join('progres_tp pr','pr.id=j.progres_tp_id')
                ->join('tp t','t.id=pr.tp_id AND t.paket_semester_id=pr.paket_semester_id')
                ->whereIn('pr.paket_semester_id',$ids)
                ->where('j.status','final')->where('j.nilai',null)
                ->where('t.jenis_tp','non_esensial')
                ->where('t.status !=','arsip')->get()->getResultArray();
            foreach ($rows as $j) {
                $pid=(int)$j['paket_semester_id'];
                if (!isset($eligibleByPaket[$pid][(int)$j['siswa_id']])) continue;
                $key=$tabel.':'.(int)$j['jawaban_id'];
                $antreanPerPaket[$pid][$key]=true;
            }
        }
        foreach ($paket as &$p) {
            $p['antrean_penilaian']=count($antreanPerPaket[(int)$p['id']]??[]);
            $total['antrean_penilaian']+=$p['antrean_penilaian'];
        }
        unset($p);
        $total['pasangan_gen_mapel']=count($pasanganGenMapel);
        $total['guru_ditugaskan']=count($guruTerisi);
        $total['siswa']=count($unikSiswa);
        $total['paket']=count($paket);
        $total['persen']=$total['pasangan_wajib']===0?null:
            round(100*$total['tuntas_sah']/$total['pasangan_wajib'],1);
        $jumlah=count($paket);
        $halaman=min($halaman,max(1,(int)ceil($jumlah/$perHalaman)));
        return [
            'pilihan'=>$pilihan,'filter'=>$filter,'ringkasan'=>$total,
            'baris'=>array_slice($paket,($halaman-1)*$perHalaman,$perHalaman),
            'jumlah'=>$jumlah,'halaman'=>$halaman,
            'jumlah_halaman'=>max(1,(int)ceil($jumlah/$perHalaman)),
        ];
    }
}
