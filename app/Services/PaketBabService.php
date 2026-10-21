<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** Paket per Gen–Mapel; seluruh mutasi divalidasi di Service. */
final class PaketBabService
{
    public function __construct(private readonly AkademikGuruContext $ctx = new AkademikGuruContext())
    {
    }

    /** @return array{gen:list<array<string,mixed>>,tahun:list<array<string,mixed>>,kurikulum:list<array<string,mixed>>,paket:list<array<string,mixed>>} */
    public function beranda(): array
    {
        $guru = $this->ctx->guru();
        $gen = $this->ctx->daftarGenMapel();
        $tahun = $this->ctx->db->table('ref_tahun_ajaran')->orderBy('id', 'DESC')
            ->get()->getResultArray();
        $kurikulum = $this->ctx->db->table('ref_kurikulum')
            ->orderBy('id', 'DESC')->get()->getResultArray();
        $paket = $this->ctx->db->table('paket_semester p')
            ->select('p.*,a.nama AS gen_nama,m.nama AS mapel_nama,t.nama AS tahun_nama,k.nama AS kurikulum_nama')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->join('ref_tahun_ajaran t','t.id=p.tahun_ajaran_id')
            ->join('ref_kurikulum k','k.id=p.kurikulum_id')
            ->join('penugasan_guru_gen g',
                "g.angkatan_id=p.angkatan_id AND g.mapel_id=p.mapel_id")
            ->where('g.guru_id', (int) $guru['id'])
            ->orderBy('p.id','DESC')->get()->getResultArray();
        return compact('gen','tahun','kurikulum','paket');
    }

    public function buatPaket(int $gen, int $mapel, int $semester, int $tahun, int $kurikulum): int
    {
        $guru = $this->ctx->guru();
        if ($semester < 1 || $semester > 6 || $tahun < 1 || $kurikulum < 1) {
            throw new DomainException('Semester harus 1–6; pilih tahun ajaran dan kurikulum yang sah.');
        }
        $this->ctx->periksaGenMapel($gen, $mapel);
        return $this->ctx->transaksi(function () use ($gen,$mapel,$semester,$tahun,$kurikulum,$guru): int {
            // Kunci penugasan, agar tidak membuat paket setelah Guru dipindah.
            $g1 = $this->ctx->db->query(
                'SELECT guru_id FROM penugasan_guru_gen WHERE angkatan_id=? AND mapel_id=? FOR UPDATE',
                [$gen,$mapel]
            )->getRowArray();
            if ($g1 === null || (int) $g1['guru_id'] !== (int) $guru['id']) {
                throw new DomainException('Penugasan Gen–Mapel telah berubah.');
            }
            $genRow=$this->ctx->db->table('ref_angkatan')->select('semester_mulai')
                ->where('id',$gen)->get()->getRowArray();
            if($genRow===null || $semester<(int)$genRow['semester_mulai']){
                throw new DomainException('Semester ini sebelum semester mulai Gen dan berstatus Dilewati.');
            }
            if ($this->ctx->db->table('ref_tahun_ajaran')->where('id',$tahun)->countAllResults() !== 1
                || $this->ctx->db->table('ref_kurikulum')->where('id',$kurikulum)->countAllResults() !== 1) {
                throw new DomainException('Referensi tahun ajaran/kurikulum tidak tersedia.');
            }
            if ($this->ctx->db->table('penugasan_paket')->where('angkatan_id',$gen)
                ->where('mapel_id',$mapel)->where('semester',$semester)
                ->where('tahun_ajaran_id',$tahun)->countAllResults() !== 0) {
                throw new DomainException('Paket aktif untuk Gen–Mapel–Semester–Tahun Ajaran tersebut sudah tersedia.');
            }
            $now = gmdate('Y-m-d H:i:s');
            $this->ctx->db->table('paket_semester')->insert([
                'angkatan_id'=>$gen, 'mapel_id'=>$mapel,'semester'=>$semester,
                'tahun_ajaran_id'=>$tahun,'kurikulum_id'=>$kurikulum,
                'nomor_versi'=>1,'status'=>'draf','dibuat_oleh'=>$guru['id'],'dibuat_pada'=>$now,
            ]);
            $id=(int)$this->ctx->db->insertID();
            $this->ctx->db->table('penugasan_paket')->insert([
                'angkatan_id'=>$gen,'mapel_id'=>$mapel,'semester'=>$semester,
                'tahun_ajaran_id'=>$tahun,'paket_id'=>$id,
                'diperbarui_oleh'=>$guru['id'],'diperbarui_pada'=>$now,
            ]);
            $this->ctx->catat((int)$guru['id'],'guru','paket_dibuat','paket_semester',$id);
            return $id;
        });
    }

    /** @return array{paket:array<string,mixed>,bab:list<array<string,mixed>>} */
    public function detailPaket(int $id): array
    {
        $paket = $this->ctx->paket($id);
        $bab = $this->ctx->db->table('bab')->where('paket_id',$id)
            ->orderBy('urutan')->get()->getResultArray();
        return compact('paket','bab');
    }

    public function buatBab(int $paketId, string $judul): int
    {
        $paket = $this->ctx->paket($paketId);
        $guru = $this->ctx->guru();
        $judul=trim($judul);
        if (mb_strlen($judul)<4 || mb_strlen($judul)>180) {
            throw new DomainException('Judul BAB harus 4–180 karakter.');
        }
        return $this->ctx->transaksi(function () use ($paket,$guru,$judul): int {
            $this->ctx->db->query('SELECT id FROM paket_semester WHERE id=? FOR UPDATE',[$paket['id']]);
            $this->ctx->periksaGenMapel((int)$paket['angkatan_id'],(int)$paket['mapel_id']);
            $last=$this->ctx->db->table('bab')->selectMax('urutan','n')
                ->where('paket_id',$paket['id'])->get()->getRowArray();
            $next=(int)($last['n']??0)+1;
            if ($next>100)throw new DomainException('Batas BAB per paket adalah 100.');
            $this->ctx->db->table('bab')->insert([
                'paket_id'=>$paket['id'],'urutan'=>$next,'judul'=>$judul,
                'status'=>'draf','struktur_versi'=>1,'dibuat_oleh'=>$guru['id'],
                'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
            $id=(int)$this->ctx->db->insertID();
            $this->ctx->catat((int)$guru['id'],'guru','bab_draf_dibuat','bab',$id);
            return $id;
        });
    }

    /** @return list<array<string,mixed>> */
    public function cpAktif(int $babId): array
    {
        $bab=$this->ctx->bab($babId);
        return $this->ctx->db->table('ref_cp_versi v')
            ->select('v.id AS id,c.kode,v.nomor_versi,v.elemen,v.rumusan')
            ->join('ref_cp c','c.id=v.cp_id')
            ->where('v.status','aktif')->where('c.mapel_id',(int)$bab['mapel_id'])
            ->where('c.kurikulum_id',(int)$bab['kurikulum_id'])
            ->where('c.fase_id',1)->orderBy('c.kode')->get()->getResultArray();
    }

    public static function parameterTp(array $input): array
    {
        $defaults=[
            'kkm'=>75,'bobot_resume'=>20,'bobot_tugas'=>30,'bobot_sumatif'=>50,
            'resume_min'=>50,'tugas_min'=>200,'diag_tayang'=>10,'sum_tayang'=>20,
            'diag_durasi'=>30,'sum_durasi'=>60,'sum_maks_percobaan'=>3,
        ];
        foreach ($defaults as $k=>$v) {
            if (isset($input[$k]) && $input[$k] !== '') {
                if (! is_scalar($input[$k]) || preg_match('/^[0-9]{1,4}$/D',(string)$input[$k]) !== 1) {
                    throw new DomainException('Parameter ' . $k . ' harus angka bulat.');
                }
                $defaults[$k]=(int)$input[$k];
            }
        }
        if ($defaults['kkm']<1 || $defaults['kkm']>100
            || $defaults['bobot_resume']+$defaults['bobot_tugas']+$defaults['bobot_sumatif']!==100
            || $defaults['bobot_sumatif']<1
            || $defaults['resume_min']<10 || $defaults['resume_min']>1000
            || $defaults['tugas_min']<50 || $defaults['tugas_min']>5000
            || $defaults['diag_tayang']<10 || $defaults['diag_tayang']>100
            || $defaults['sum_tayang']<5 || $defaults['sum_tayang']>100
            || $defaults['diag_durasi']<5 || $defaults['diag_durasi']>180
            || $defaults['sum_durasi']<5 || $defaults['sum_durasi']>180
            || $defaults['sum_maks_percobaan']<1 || $defaults['sum_maks_percobaan']>10) {
            throw new DomainException('Parameter TP tidak memenuhi KKM, bobot 100%, batas karakter/soal/durasi dan percobaan.');
        }
        return $defaults;
    }

    public function tambahTp(int $babId, string $kode, string $rumusan, string $indikator,
        array $cpVersiIds, array $parameters = []): int
    {
        $bab=$this->ctx->bab($babId,true);
        $guru=$this->ctx->guru();
        $kode=strtoupper(trim($kode));$rumusan=trim($rumusan);$indikator=trim($indikator);
        $ids=array_values(array_unique(array_map('intval',$cpVersiIds)));
        if (! preg_match('/^[A-Z0-9._-]{2,40}$/D',$kode)
            || mb_strlen($rumusan)<20 || mb_strlen($indikator)<15
            || mb_strlen($rumusan)>5000 || mb_strlen($indikator)>5000 || !$ids) {
            throw new DomainException('TP perlu kode sah, rumusan ≥20 karakter, indikator ≥15 karakter, dan minimal satu CP.');
        }
        if (count($ids)>10)throw new DomainException('Maksimal sepuluh tautan CP per TP.');
        $parameter=self::parameterTp($parameters);
        return $this->ctx->transaksi(function () use ($babId,$bab,$kode,$rumusan,$indikator,$ids,$guru,$parameter): int {
            $locked=$this->ctx->db->query('SELECT status,struktur_versi FROM bab WHERE id=? FOR UPDATE',[$babId])->getRowArray();
            if (!$locked || $locked['status']!=='draf')throw new DomainException('BAB sudah terkunci.');
            $this->ctx->periksaGenMapel((int)$bab['angkatan_id'],(int)$bab['mapel_id']);
            if ($this->ctx->db->table('tp')->where('bab_id',$babId)->where('kode_tp',$kode)->countAllResults()!==0)
                throw new DomainException('Kode TP sudah ada di BAB ini.');
            foreach($ids as $id) {
                if ($this->ctx->db->table('ref_cp_versi v')
                    ->join('ref_cp c','c.id=v.cp_id')
                    ->where('v.id',$id)->where('v.status','aktif')
                    ->where('c.mapel_id',(int)$bab['mapel_id'])
                    ->where('c.kurikulum_id',(int)$bab['kurikulum_id'])
                    ->where('c.fase_id',1)->countAllResults()!==1)
                    throw new DomainException('CP harus versi aktif dan cocok mapel, fase, serta kurikulum paket.');
            }
            $last=$this->ctx->db->table('tp')->selectMax('urutan_atp','maks')
                ->where('bab_id',$babId)->get()->getRowArray();
            $urut=(int)($last['maks']??0)+1;
            if($urut>100)throw new DomainException('Maksimal 100 TP per BAB.');
            $this->ctx->db->table('tp')->insert([
                'bab_id'=>$babId,'kode_tp'=>$kode,'rumusan'=>$rumusan,
                'indikator'=>$indikator,'urutan_atp'=>$urut,
            ] + $parameter);
            $tpId=(int)$this->ctx->db->insertID();
            foreach($ids as $i=>$cp) {
                $this->ctx->db->table('tp_cp')->insert([
                    'tp_id'=>$tpId,'cp_versi_id'=>$cp,'utama'=>$i===0?1:0,
                ]);
            }
            $this->ctx->db->table('bab')->where('id',$babId)
                ->set('struktur_versi','struktur_versi + 1',false)->update();
            $this->ctx->catat((int)$guru['id'],'guru','tp_draf_dibuat','tp',$tpId);
            return $tpId;
        });
    }

    /** @return array{bab:array<string,mixed>,tp:list<array<string,mixed>>,materi:list<array<string,mixed>>,hots:list<array<string,mixed>>,soal:list<array<string,mixed>>} */
    public function detailBab(int $babId): array
    {
        $bab=$this->ctx->bab($babId);
        $tp=$this->ctx->db->table('tp t')->select('t.*,c.cp_versi_id,v.nomor_versi,rc.kode AS kode_cp')
            ->join('tp_cp c','c.tp_id=t.id AND c.utama=1','left')
            ->join('ref_cp_versi v','v.id=c.cp_versi_id','left')
            ->join('ref_cp rc','rc.id=v.cp_id','left')
            ->where('t.bab_id',$babId)->orderBy('t.urutan_atp')->get()->getResultArray();
        $ids=array_column($tp,'id');
        $materi=$ids ? $this->ctx->db->table('materi')->whereIn('tp_id',$ids)
            ->orderBy('tp_id')->orderBy('urutan')->get()->getResultArray() : [];
        $hots=$ids ? $this->ctx->db->table('tugas_hots')->whereIn('tp_id',$ids)
            ->get()->getResultArray() : [];
        $soal=$ids ? $this->ctx->db->table('soal')->whereIn('tp_id',$ids)
            ->orderBy('tp_id')->orderBy('jenis')->orderBy('urutan')->get()->getResultArray() : [];
        return compact('bab','tp','materi','hots','soal');
    }

    public function ubahParameter(int $tpId,array $input): void
    {
        $parameter=self::parameterTp($input);
        $record=$this->ctx->db->table('tp t')->select('t.bab_id')
            ->where('t.id',$tpId)->get()->getRowArray();
        if(!$record)throw new DomainException('TP tidak tersedia.');
        $this->ctx->bab((int)$record['bab_id'],true);
        $this->ctx->transaksi(function()use($tpId,$parameter,$record):void{
            $bab=$this->ctx->db->query('SELECT status FROM bab WHERE id=? FOR UPDATE',[$record['bab_id']])->getRowArray();
            if (!$bab||$bab['status']!=='draf')throw new DomainException('BAB Siap terkunci.');
            $this->ctx->db->table('tp')->where('id',$tpId)->update($parameter);
            $this->ctx->db->table('bab')->where('id',$record['bab_id'])
                ->set('struktur_versi','struktur_versi + 1',false)->update();
            $guru=$this->ctx->guru();
            $this->ctx->catat((int)$guru['id'],'guru','parameter_tp_diubah','tp',$tpId);
        });
    }
}
