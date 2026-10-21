<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** Gerbang Siap: setiap syarat diuji ulang dalam transaksi yang mengunci BAB. */
final class BabKesiapanService
{
    public function __construct(private readonly AkademikGuruContext $ctx = new AkademikGuruContext())
    {
    }

    /** @return array{pesan:list<string>,siap:bool,ringkasan:list<array<string,mixed>>} */
    public function checklist(int $babId): array
    {
        $bab = $this->ctx->bab($babId);
        return $this->periksa($bab);
    }

    /** @return array{pesan:list<string>,siap:bool,ringkasan:list<array<string,mixed>>} */
    private function periksa(array $bab): array
    {
        $pesan=[];$ringkasan=[];
        $tp=$this->ctx->db->table('tp')->where('bab_id',(int)$bab['id'])
            ->orderBy('urutan_atp')->get()->getResultArray();
        if($tp===[])$pesan[]='Struktur BAB: buat minimal satu TP.';
        foreach($tp as $i=>$t){
            $label='TP '.$t['kode_tp'];
            $masalah=[];
            if((int)$t['urutan_atp']!==$i+1)$masalah[]='Urutan ATP tidak berurutan.';
            if(mb_strlen(trim($t['rumusan']))<20||mb_strlen(trim($t['indikator']))<15)
                $masalah[]='Rumusan/indikator belum lengkap.';
            try{PaketBabService::parameterTp($t);}catch(DomainException $e){$masalah[]=$e->getMessage();}
            $links=$this->ctx->db->table('tp_cp c')
                ->select('c.utama,c.cp_versi_id,v.status,r.mapel_id,r.kurikulum_id,r.fase_id')
                ->join('ref_cp_versi v','v.id=c.cp_versi_id')
                ->join('ref_cp r','r.id=v.cp_id')
                ->where('c.tp_id',(int)$t['id'])->get()->getResultArray();
            if($links===[]||count(array_filter($links,static fn($l)=>(int)$l['utama']===1))!==1)
                $masalah[]='Harus tepat satu CP utama.';
            foreach($links as $l){
                if($l['status']!=='aktif' || (int)$l['mapel_id']!==(int)$bab['mapel_id']
                    ||(int)$l['kurikulum_id']!==(int)$bab['kurikulum_id']||(int)$l['fase_id']!==1)
                    $masalah[]='Tautan CP harus versi resmi aktif dan cocok kurikulum/mapel/Fase D.';
            }
            $materi=$this->ctx->db->table('materi')->where('tp_id',$t['id'])
                ->where('aktif',1)->orderBy('urutan')->get()->getResultArray();
            if(!$materi)$masalah[]='Minimal satu materi aktif belum tersedia.';
            foreach($materi as $j=>$m){
                if((int)$m['urutan']!==$j+1)$masalah[]='Urutan materi tidak berurutan.';
                if(mb_strlen(trim($m['petunjuk_resume']))<15)$masalah[]='Petunjuk resume materi belum lengkap.';
                if($m['jenis']==='youtube' && !BabKontenService::urlYouTubeValid((string)$m['url_youtube']))
                    $masalah[]='URL YouTube materi belum sah.';
                if($m['jenis']!=='youtube'){
                    if($m['berkas_id']===null){
                        $masalah[]='Berkas materi '.$m['judul'].' belum diunggah.';
                    }else{
                        $f=$this->ctx->db->table('berkas')->where('id',$m['berkas_id'])->get()->getRowArray();
                        if(!$f)$masalah[]='Metadata berkas materi tidak tersedia.';
                        else{
                            $path=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.'berkas'.DIRECTORY_SEPARATOR.$f['nama_acak'];
                            if(!is_file($path)||!hash_equals($f['checksum'],hash_file('sha256',$path)))
                                $masalah[]='Berkas materi hilang/berubah dari checksum.';
                            if($m['jenis']==='pdf'&&((int)$f['halaman']<1||(int)$f['halaman']>40))
                                $masalah[]='Jumlah halaman PDF di luar ketentuan.';
                            if($m['jenis']==='audio'&&($f['durasi_detik']===null||(int)$f['durasi_detik']<1))
                                $masalah[]='Durasi audio harus berhasil diverifikasi server sebelum Siap.';
                        }
                    }
                }
            }
            $hots=$this->ctx->db->table('tugas_hots')->where('tp_id',$t['id'])->get()->getRowArray();
            if(!$hots||mb_strlen(trim($hots['pertanyaan']))<15||mb_strlen(trim($hots['petunjuk']))<12)
                $masalah[]='Tugas HOTS dan petunjuk belum lengkap.';
            foreach(['diagnostik'=>(int)$t['diag_tayang'],'sumatif'=>(int)$t['sum_tayang']] as $jenis=>$min){
                $soal=$this->ctx->db->table('soal')->where('tp_id',$t['id'])
                    ->where('jenis',$jenis)->where('aktif',1)->orderBy('urutan')
                    ->get()->getResultArray();
                if(count($soal)<$min||($jenis==='diagnostik'&&count($soal)<10))
                    $masalah[]='Bank '.$jenis.' perlu setidaknya '.$min.' soal aktif.';
                foreach($soal as $n=>$q){
                    if((int)$q['urutan']!==$n+1)$masalah[]='Urutan soal '.$jenis.' tidak berurutan.';
                    if(!BabKontenService::barisSoalValid($q))
                        $masalah[]='Soal '.$jenis.' #'.($n+1).' memiliki opsi/kunci tidak valid.';
                }
            }
            $ringkasan[]=['tp'=>$label,'materi'=>count($materi),'masalah'=>$masalah];
            foreach(array_unique($masalah) as $v)$pesan[]=$label.': '.$v;
        }
        return ['pesan'=>$pesan,'siap'=>$pesan===[],'ringkasan'=>$ringkasan];
    }

    public function terbitkan(int $babId): void
    {
        $guru=$this->ctx->guru();
        $bab=$this->ctx->bab($babId,true);
        $this->ctx->transaksi(function () use ($guru,$babId,$bab):void {
            $locked=$this->ctx->db->query('SELECT status,struktur_versi FROM bab WHERE id=? FOR UPDATE',[$babId])->getRowArray();
            if(!$locked||$locked['status']!=='draf')throw new DomainException('BAB sudah Siap/terkunci.');
            $this->ctx->periksaGenMapel((int)$bab['angkatan_id'],(int)$bab['mapel_id']);
            $periksa=$this->periksa($bab);
            if(!$periksa['siap'])throw new DomainException('BAB belum memenuhi checklist: '.implode(' | ',array_slice($periksa['pesan'],0,3)));
            $detail=(new PaketBabService($this->ctx))->detailBab($babId);
            $tautan=[];
            foreach($detail['tp'] as $t){
                $tautan[(int)$t['id']]=$this->ctx->db->table('tp_cp')
                    ->where('tp_id',$t['id'])->get()->getResultArray();
            }
            $snapshot=json_encode([
                'bab'=>$bab,'tp'=>$detail['tp'],'tp_cp'=>$tautan,
                'materi'=>$detail['materi'],'hots'=>$detail['hots'],'soal'=>$detail['soal'],
                'struktur_versi'=>$locked['struktur_versi']
            ],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            $this->ctx->db->table('versi_konten_bab')->insert([
                'bab_id'=>$babId,'nomor_versi'=>1,'snapshot_json'=>$snapshot,
                'checksum'=>hash('sha256',$snapshot),
                'disiapkan_oleh'=>$guru['id'],'disiapkan_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
            $this->ctx->db->table('bab')->where('id',$babId)->where('status','draf')
                ->update(['status'=>'siap','disiapkan_oleh'=>$guru['id'],
                    'disiapkan_pada'=>gmdate('Y-m-d H:i:s')]);
            if($this->ctx->db->affectedRows()!==1)
                throw new DomainException('BAB sudah berubah. Muat ulang halaman.');
            $this->ctx->db->table('paket_semester')->where('id',$bab['paket_id'])
                ->where('status','draf')->update(['status'=>'aktif']);
            $this->ctx->catat((int)$guru['id'],'guru','bab_siap_diterbitkan','bab',$babId);
        });
    }
}
