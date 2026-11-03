<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * A12-3: beranda akademik Siswa, seluruh SELECT dan tanpa pembuatan progres.
 * Paket dan tombol TP hanya berasal dari KatalogPembelajaranSiswaService
 * yang memeriksa Gen, semester mulai, penugasan, dan urutan ATP.
 */
final class DashboardSiswaService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db=null, ?callable $akun=null)
    {
        $this->db=$db ?? db_connect();
        $this->akun=$akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    /** @return array<string,mixed> */
    public function ringkasan(): array
    {
        $aktor=($this->akun)();
        if (!$aktor || ($aktor['peran_aktif']??'')!=='siswa'
            || ($aktor['status']??'')!=='aktif'
            || (int)($aktor['wajib_ganti_sandi']??1)!==0
            || (int)($aktor['id']??0)<1) {
            throw new DomainException('Dashboard hanya tersedia untuk Siswa aktif.');
        }

        $katalog=new KatalogPembelajaranSiswaService($this->db,$this->akun);
        $daftar=$katalog->daftarMapel();
        $siswa=$daftar['siswa'];
        $siswaId=(int)$siswa['id'];
        $paketDiizinkan=[];
        foreach ($daftar['mapel'] as $mapel) {
            $semester=$katalog->daftarSemester((int)$mapel['id']);
            foreach ($semester['semester'] as $s) {
                if (empty($s['buka']) || (int)($s['paket_id']??0)<1) {
                    continue;
                }
                $id=(int)$s['paket_id'];
                // Tidak menerima ID paket dari browser atau dari tabel lain
                // sebelum gerbang resmi katalog memastikan boleh dibuka.
                $paketDiizinkan[$id]=[
                    'paket_id'=>$id,
                    'mapel_id'=>(int)$mapel['id'],
                    'nama_mapel'=>(string)$mapel['nama'],
                    'semester'=>(int)$s['nomor'],
                    'tp_wajib'=>0,'tp_tuntas'=>0,'nilai_final'=>0,
                    'perlu_bantuan'=>0,'persen'=>null,
                    'tautan'=>'siswa/semester/'.$id,
                    'label_aksi'=>'Buka Semester',
                    'status'=>'Belum ada TP NonEsensial',
                ];
            }
        }

        $sertifikat=(int)$this->db->table('sertifikat_aktif a')
            ->join('sertifikat c','c.id=a.sertifikat_id')
            ->where('a.siswa_id',$siswaId)
            ->where('c.status','berlaku')->countAllResults();

        $kosong=[
            'siswa'=>[
                'gen'=>(string)($siswa['gen_nama']??''),
                'semester_mulai'=>(int)$siswa['mulai_efektif'],
            ],
            'mapel'=>count($daftar['mapel']),
            'paket_terbuka'=>count($paketDiizinkan),
            'tp_wajib'=>0,'tp_tuntas'=>0,'nilai_final'=>0,
            'perlu_bantuan'=>0,'sertifikat_berlaku'=>$sertifikat,
            'persen'=>null,
            'paket'=>[],
            'lanjut'=>[
                'tautan'=>'siswa/pembelajaran',
                'label'=>'Lihat Pembelajaran',
                'keterangan'=>'Pilih mata pelajaran untuk melihat semester yang tersedia.',
            ],
        ];
        if ($paketDiizinkan===[]) return $kosong;

        $paketIds=array_keys($paketDiizinkan);
        $tpByPaket=[];
        $tpOwner=[];
        foreach ($this->db->table('tp')
            ->select('id,paket_semester_id,jenis_tp,status')
            ->whereIn('paket_semester_id',$paketIds)
            ->where('status !=','arsip')->get()->getResultArray() as $tp) {
            if ($tp['jenis_tp']!=='non_esensial') continue;
            $pid=(int)$tp['paket_semester_id'];
            $tid=(int)$tp['id'];
            $tpByPaket[$pid][$tid]=true;
            $tpOwner[$tid]=$pid;
            $paketDiizinkan[$pid]['tp_wajib']++;
        }

        $progres=$this->db->table('progres_tp')
            ->select('id,tp_id,paket_semester_id,status')
            ->where('siswa_id',$siswaId)
            ->whereIn('paket_semester_id',$paketIds)
            ->get()->getResultArray();
        $progresById=[];
        foreach ($progres as $p) {
            $tid=(int)$p['tp_id'];
            $pid=(int)$p['paket_semester_id'];
            if (($tpOwner[$tid]??null)!==$pid) continue;
            $progresById[(int)$p['id']]=$p;
        }

        $valid=[];
        $final=[];
        if ($progresById!==[]) {
            $progressIds=array_keys($progresById);
            foreach ($this->db->table('percobaan_ujian')
                ->select('progres_tp_id')
                ->whereIn('progres_tp_id',$progressIds)
                ->where('jenis','sumatif')->where('status','selesai_sah')
                ->get()->getResultArray() as $u) {
                $valid[(int)$u['progres_tp_id']]=true;
            }
            foreach ($this->db->table('nilai_tp')
                ->select('progres_tp_id,status_nilai,nilai_tp')
                ->whereIn('progres_tp_id',$progressIds)
                ->get()->getResultArray() as $n) {
                if ($n['status_nilai']==='final' && $n['nilai_tp']!==null) {
                    // Nilai 0,00 adalah nilai Final yang sah.
                    $final[(int)$n['progres_tp_id']]=true;
                }
            }
        }

        $selesaiByPaket=[]; $nilaiByPaket=[]; $bantuanByPaket=[];
        foreach ($progresById as $id=>$p) {
            $pid=(int)$p['paket_semester_id'];
            $tid=(int)$p['tp_id'];
            if ($p['status']==='perlu_bantuan') {
                $bantuanByPaket[$pid][$tid]=true;
            }
            if ($p['status']==='tuntas' && isset($valid[$id])) {
                $selesaiByPaket[$pid][$tid]=true;
                if (isset($final[$id])) $nilaiByPaket[$pid][$tid]=true;
            }
        }

        $hasil=$kosong;
        $lanjut=null;
        foreach ($paketDiizinkan as &$p) {
            $pid=$p['paket_id'];
            $p['tp_tuntas']=count($selesaiByPaket[$pid]??[]);
            $p['nilai_final']=count($nilaiByPaket[$pid]??[]);
            $p['perlu_bantuan']=count($bantuanByPaket[$pid]??[]);
            $target=$p['tp_wajib'];
            if ($target===0) {
                $p['status']='Menunggu TP NonEsensial disiapkan Guru';
            } elseif ($p['tp_tuntas']===$target) {
                $p['persen']=100.0;
                $p['status']='Semua TP wajib selesai sah';
                $p['label_aksi']='Lihat Hasil';
                $p['tautan']='siswa/laporan/'.$pid;
            } else {
                $p['persen']=round(100*$p['tp_tuntas']/$target,1);
                $p['status']=$p['perlu_bantuan']>0
                    ? 'Ada TP yang memerlukan bantuan Guru'
                    : 'Pembelajaran belum selesai';

                // Link TP dibentuk hanya dari katalog resmi pada paket terbuka.
                // Jika TP belum siap, jangan membuat tautan TP/ujian palsu.
                $daftarTp=$katalog->daftarTp($pid);
                foreach ($daftarTp['tp'] as $item) {
                    $tid=(int)$item['id'];
                    if ($item['jenis_tp']!=='non_esensial'
                        || isset($selesaiByPaket[$pid][$tid])
                        || empty($item['akses']['buka'])) continue;
                    $p['tautan']='siswa/tp/'.$tid;
                    $p['label_aksi']='Lanjutkan TP';
                    break;
                }
                if ($lanjut===null) {
                    $lanjut=[
                        'tautan'=>$p['tautan'],
                        'label'=>$p['label_aksi'],
                        'keterangan'=>$p['nama_mapel'].' · Semester '.$p['semester']
                            .' · '.$p['status'],
                    ];
                }
            }
            $hasil['tp_wajib']+=$target;
            $hasil['tp_tuntas']+=$p['tp_tuntas'];
            $hasil['nilai_final']+=$p['nilai_final'];
            $hasil['perlu_bantuan']+=$p['perlu_bantuan'];
        }
        unset($p);
        $hasil['paket']=array_values($paketDiizinkan);
        $hasil['persen']=$hasil['tp_wajib']===0?null:
            round(100*$hasil['tp_tuntas']/$hasil['tp_wajib'],1);
        if ($lanjut!==null) {
            $hasil['lanjut']=$lanjut;
        } elseif ($hasil['tp_wajib']>0
            && $hasil['tp_tuntas']===$hasil['tp_wajib']) {
            $hasil['lanjut']=[
                'tautan'=>'siswa/laporan',
                'label'=>'Lihat Hasil Pembelajaran',
                'keterangan'=>'Seluruh TP NonEsensial pada paket yang terbuka sudah selesai sah.',
            ];
        }
        return $hasil;
    }
}
