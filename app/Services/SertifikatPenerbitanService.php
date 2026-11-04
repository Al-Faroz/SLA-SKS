<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use RuntimeException;
use Throwable;

/**
 * A11-2: penerbitan sertifikat idempoten, snapshot immutable dan histori versi.
 * Persiapan PDF di luar transaksi; dalam transaksi ulangi pembuktian nilai/versi.
 */
final class SertifikatPenerbitanService
{
    private BaseConnection $db;
    private $akun;

    public function __construct(?BaseConnection $db=null,?callable $akun=null)
    {
        $this->db=$db ?? db_connect();
        $this->akun=$akun ?? static fn (): ?array =>
            (new AuthService())->penggunaSaatIni();
    }

    private function role(): array
    {
        $a=($this->akun)();
        if (!$a || !in_array($a['peran_aktif']??null,['admin','siswa'],true)
            || ($a['status']??null)!=='aktif'
            || (int)($a['wajib_ganti_sandi']??1)!==0
            || (int)($a['id']??0)<1) {
            throw new DomainException('Akses sertifikat tidak diizinkan.');
        }
        return $a;
    }

    private function admin(): int
    {
        $a=$this->role();
        if ($a['peran_aktif']!=='admin') {
            throw new DomainException('Hanya Admin dapat menerbitkan sertifikat.');
        }
        return (int)$a['id'];
    }

    private function identitas(): array
    {
        $wajib=array_diff(array_keys(PengaturanService::IDENTITAS),['website']);
        $rows=$this->db->table('pengaturan')->select('kunci,nilai')
            ->whereIn('kunci',array_keys(PengaturanService::IDENTITAS))
            ->get()->getResultArray();
        $id=[];
        foreach ($rows as $r) $id[$r['kunci']]=trim((string)($r['nilai']??''));
        foreach ($wajib as $key) {
            if (($id[$key]??'')==='') {
                throw new DomainException('Lengkapi identitas madrasah: '
                    .PengaturanService::IDENTITAS[$key].'.');
            }
        }
        return $id;
    }

    /** Snapshot tercetak saja: tidak pernah mengambil hasil Pra-Semester. */
    private function konten(int $siswaId,int $paketId): array
    {
        $s=$this->db->table('siswa s')
            ->select('s.id,s.nisn,s.angkatan_id,s.semester_mulai,u.nama')
            ->join('pengguna u','u.id=s.pengguna_id')
            ->where('s.id',$siswaId)->get()->getRowArray();
        $p=$this->db->table('paket_semester p')
            ->select('p.id,p.angkatan_id,p.mapel_id,p.semester,p.status,'
                .'a.nama AS nama_gen,a.semester_mulai AS gen_semester_mulai,'
                .'m.nama AS nama_mapel')
            ->join('ref_angkatan a','a.id=p.angkatan_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->where('p.id',$paketId)->get()->getRowArray();
        if (!$s || !$p || $p['status']!=='aktif'
            || (int)$s['angkatan_id']!==(int)$p['angkatan_id']) {
            throw new DomainException('Siswa/paket sertifikat tidak sah.');
        }
        $mulai=(int)($s['semester_mulai']??$p['gen_semester_mulai']);
        if ((int)$p['semester']<$mulai) {
            throw new DomainException('Semester siswa dilewati, sertifikat tidak boleh diterbitkan.');
        }
        if (!preg_match('/^[0-9]{10}$/D',(string)$s['nisn'])) {
            throw new DomainException('NISN siswa belum valid.');
        }
        $tp=$this->db->table('tp t')
            ->select('t.id,t.kode_tp,t.nama_tp,t.judul,t.jenis_tp,'
                .'b.nomor_bab,b.nama_bab,n.nilai_tp,n.status_nilai')
            ->join('bab b','b.id=t.bab_id AND b.paket_semester_id=t.paket_semester_id','left')
            ->join('progres_tp pr','pr.tp_id=t.id AND pr.siswa_id='.(int)$siswaId
                .' AND pr.paket_semester_id=t.paket_semester_id','left')
            ->join('nilai_tp n','n.progres_tp_id=pr.id','left')
            ->where('t.paket_semester_id',$paketId)
            ->where('t.status !=','arsip')
            ->orderBy('t.urutan_atp','ASC')->orderBy('t.id','ASC')
            ->get()->getResultArray();
        $list=[];
        foreach ($tp as $item) {
            if (!in_array($item['jenis_tp'],['esensial','non_esensial'],true)) {
                throw new DomainException('Jenis TP sertifikat tidak valid.');
            }
            if ($item['jenis_tp']==='non_esensial'
                && ($item['status_nilai']!=='final' || $item['nilai_tp']===null)) {
                throw new DomainException('Masih ada Nilai TP yang belum Final.');
            }
            $list[]=[
                'nomor_bab'=>$item['nomor_bab'],
                'nama_bab'=>(string)($item['nama_bab']??''),
                'kode_tp'=>(string)$item['kode_tp'],
                'nama_tp'=>(string)($item['nama_tp']?:$item['judul']),
                'jenis_tp'=>(string)$item['jenis_tp'],
                'nilai_tp'=>$item['jenis_tp']==='esensial'
                    ? null : number_format((float)$item['nilai_tp'],2,'.',''),
            ];
        }
        return [
            'siswa'=>['nama'=>(string)$s['nama'],'nisn'=>(string)$s['nisn']],
            'paket'=>[
                'nama_gen'=>(string)$p['nama_gen'],
                'nama_mapel'=>(string)$p['nama_mapel'],
                'semester'=>(int)$p['semester'],
            ],
            'madrasah'=>$this->identitas(),
            'tp'=>$list,
        ];
    }

    /** Sertifikat aktif yang terkunci dari pilihan siswa+paket. */
    private function aktif(int $siswaId,int $paketId): ?array
    {
        return $this->db->table('sertifikat_aktif a')
            ->select('c.*')->join('sertifikat c','c.id=a.sertifikat_id')
            ->where('a.siswa_id',$siswaId)
            ->where('a.paket_semester_id',$paketId)
            ->get()->getRowArray();
    }

    /** Baca internal aset setelah role dan kepemilikan siswa diverifikasi.
     * Aksi upload/aktifkan tetap eksklusif Admin pada SertifikatAsetService.
     */
    private function asetAktif(): array
    {
        $rows=$this->db->table('sertifikat_aset_aktif aa')
            ->select('aa.jenis,a.id,a.nama_acak,a.mime,a.sha256,a.lebar,a.tinggi')
            ->join('sertifikat_aset a','a.id=aa.aset_id AND a.jenis=aa.jenis')
            ->get()->getResultArray();
        $out=[];
        foreach($rows as $row)$out[$row['jenis']]=$row;
        return $out;
    }

    private function asetAmbil(int $id,string $jenis): array
    {
        $r=$this->db->table('sertifikat_aset')
            ->where('id',$id)->where('jenis',$jenis)->get(1)->getRowArray();
        if (!$r)throw new DomainException('Aset sertifikat tidak ditemukan.');
        return $r;
    }

    /** @return array{background:?array,ttd:array} */
    private function aset(?array $lama,bool $revisi): array
    {
        $aktif=$this->asetAktif();
        if (!isset($aktif['ttd'])) {
            throw new DomainException('Unggah dan aktifkan tanda tangan PNG terlebih dahulu.');
        }
        $ttd=$this->asetAmbil((int)$aktif['ttd']['id'],'ttd');
        SertifikatAsetService::path($ttd);
        $background=null;
        if ($revisi && $lama!==null && $lama['background_aset_id']!==null) {
            $background=$this->asetAmbil((int)$lama['background_aset_id'],'background');
        } elseif (!$revisi && isset($aktif['background'])) {
            $background=$this->asetAmbil((int)$aktif['background']['id'],'background');
        }
        // Revisi template fallback lama tetap fallback, meski template baru aktif.
        SertifikatAsetService::path($ttd);
        if ($background!==null) SertifikatAsetService::path($background);
        return ['background'=>$background,'ttd'=>$ttd];
    }

    private function persiapkan(int $sid,int $pid,bool $revisi,string $alasan,bool $otomatis=false): array
    {
        $svc=new KelayakanSertifikatService($this->db,$this->akun);
        $cek=$svc->periksaSiswa($sid,$pid);
        if (!$cek['layak_akademik']) {
            throw new DomainException('Siswa belum layak akademik: '
                .implode(' ',$cek['alasan']));
        }
        $lama=$this->aktif($sid,$pid);
        if ($revisi && $lama===null) {
            throw new DomainException('Sertifikat awal belum diterbitkan.');
        }
        $aset=$this->aset($lama,$revisi);
        $konten=$this->konten($sid,$pid);
        $konten['ttd_aset_id']=(int)$aset['ttd']['id'];
        $konten['background_aset_id']=$aset['background']['id']??null;
        $json=json_encode($konten,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $hash=hash('sha256',$json);
        if ($lama!==null) {
            if (hash_equals((string)$lama['konten_sha256'],$hash)) {
                if ($revisi && !$otomatis) throw new DomainException('Isi cetak belum berubah. Tidak perlu revisi.');
                return ['sudah_ada'=>$lama];
            }
            if (!$revisi) {
                throw new DomainException(
                    'Sertifikat sudah terbit tetapi isi cetak berubah. Gunakan revisi satu per satu.');
            }
        }
        if ($revisi && (mb_strlen($alasan)<5 || mb_strlen($alasan)>225)) {
            throw new DomainException('Alasan revisi wajib 5–225 karakter.');
        }
        $versi=$lama===null?1:(int)$lama['versi']+1;
        if ($versi>65535) throw new DomainException('Batas versi sertifikat tercapai.');
        $tanggal=(new \DateTimeImmutable('now',new \DateTimeZone('Asia/Jakarta')))->format('d-m-Y');
        $tahun=substr($tanggal,-4);
        $nomor=sprintf('SLA-SKS/%s/P%06d/S%06d/V%03d',$tahun,$pid,$sid,$versi);
        $token=bin2hex(random_bytes(24));
        $snapshot=$konten+[
            'nomor'=>$nomor,'tanggal'=>$tanggal,
            'kode_verifikasi'=>$token,'versi'=>$versi,
        ];
        $snapshotJson=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $url=site_url('verifikasi/'.$token);
        $pdf=(new SertifikatPdfService())->render(
            $snapshot,$aset['background'],$aset['ttd'],$url
        );
        $dir=SertifikatAsetService::direktori('pdf');
        if (!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) {
            throw new RuntimeException('Folder privat PDF tidak dapat dibuat.');
        }
        $nama=bin2hex(random_bytes(24));
        $path=$dir.DIRECTORY_SEPARATOR.$nama.'.pdf';
        if (@file_put_contents($path,$pdf,LOCK_EX)!==strlen($pdf)) {
            @unlink($path);
            throw new RuntimeException('PDF tidak berhasil disimpan secara privat.');
        }
        @chmod($path,0600);
        return [
            'sudah_ada'=>null,'lama'=>$lama,'konten_sha256'=>$hash,
            'snapshot'=>$snapshotJson,'snapshot_sha256'=>hash('sha256',$snapshotJson),
            'nomor'=>$nomor,'token'=>$token,'versi'=>$versi,
            'nama'=>$nama,'path'=>$path,'pdf_sha256'=>hash('sha256',$pdf),
            'background_id'=>$aset['background']['id']??null,
            'ttd_id'=>(int)$aset['ttd']['id'],'alasan'=>$revisi?$alasan:null,
        ];
    }

    public function terbit(int $siswaId,int $paketId): array
    {
        return $this->simpan($siswaId,$paketId,false,'');
    }

    public function revisi(int $siswaId,int $paketId,string $alasan): array
    {
        return $this->simpan($siswaId,$paketId,true,trim($alasan));
    }

    private function simpan(int $sid,int $pid,bool $revisi,string $alasan,bool $otomatis=false): array
    {
        $a=$this->role();
        if ($sid<1 || $pid<1) throw new DomainException('Siswa/paket tidak valid.');
        if ($a['peran_aktif']==='siswa') {
            if (!$otomatis) throw new DomainException('Siswa tidak dapat menerbitkan secara manual.');
            $pemilik=$this->db->table('siswa')->where('id',$sid)
                ->where('pengguna_id',(int)$a['id'])->countAllResults();
            if ($pemilik!==1) throw new DomainException('Sertifikat hanya untuk siswa pemilik.');
        } elseif ($a['peran_aktif']!=='admin' || $otomatis) {
            throw new DomainException('Aktor penerbitan tidak sah.');
        }
        $pelaku=(int)$a['id'];
        $peran=(string)$a['peran_aktif'];
        $r=$this->persiapkan($sid,$pid,$revisi,$alasan,$otomatis);
        if ($r['sudah_ada']!==null) return $r['sudah_ada'];
        $this->db->transBegin();
        try {
            // Kunci siswa, serialkan semua penerbitan bersamaan untuk pasangan ini.
            $lockSql='SELECT id FROM siswa WHERE id=?';
            if ($this->db->DBDriver !== 'SQLite3') $lockSql.=' FOR UPDATE';
            $this->db->query($lockSql,[$sid]);
            $terkini=$this->aktif($sid,$pid);
            if (($terkini['id']??null)!==($r['lama']['id']??null)
                || ($terkini['konten_sha256']??null)!==($r['lama']['konten_sha256']??null)) {
                // Permintaan identik dari tab ganda tetap idempoten;
                // tidak menerbitkan versi baru, tidak menyimpan PDF duplikat.
                if ((!$revisi || $otomatis) && $terkini!==null
                    && hash_equals((string)$terkini['konten_sha256'],$r['konten_sha256'])) {
                    $this->db->transRollback();
                    @unlink($r['path']);
                    return $terkini;
                }
                throw new DomainException('Versi sertifikat berubah. Muat ulang halaman.');
            }
            $asetAktif=$this->asetAktif();
            if ((int)($asetAktif['ttd']['id']??0)!==$r['ttd_id']
                || (!$revisi && ($asetAktif['background']['id']??null)!==$r['background_id'])) {
                throw new DomainException('Template atau TTD aktif berubah; ulangi penerbitan.');
            }
            $cek=(new KelayakanSertifikatService($this->db,$this->akun))
                ->periksaSiswa($sid,$pid);
            if (!$cek['layak_akademik']) {
                throw new DomainException('Syarat TP berubah, penerbitan dibatalkan.');
            }
            $konten=$this->konten($sid,$pid);
            $konten['ttd_aset_id']=$r['ttd_id'];
            $konten['background_aset_id']=$r['background_id'];
            $hash=hash('sha256',json_encode($konten,
                JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            if (!hash_equals($r['konten_sha256'],$hash)) {
                throw new DomainException('Konten nilai atau identitas berubah; ulangi penerbitan.');
            }
            $now=gmdate('Y-m-d H:i:s');
            $this->db->table('sertifikat')->insert([
                'siswa_id'=>$sid,'paket_semester_id'=>$pid,
                'versi'=>$r['versi'],'nomor'=>$r['nomor'],
                'kode_verifikasi'=>$r['token'],'status'=>'berlaku',
                'menggantikan_id'=>$terkini['id']??null,
                'background_aset_id'=>$r['background_id'],
                'ttd_aset_id'=>$r['ttd_id'],
                'konten_sha256'=>$r['konten_sha256'],
                'snapshot_json'=>$r['snapshot'],
                'snapshot_sha256'=>$r['snapshot_sha256'],
                'pdf_nama_acak'=>$r['nama'],
                'pdf_sha256'=>$r['pdf_sha256'],
                'alasan_revisi'=>$r['alasan'],
                'diterbitkan_oleh'=>$pelaku,'diterbitkan_pada'=>$now,
            ]);
            $id=(int)$this->db->insertID();
            if ($terkini!==null) {
                $this->db->table('sertifikat')
                    ->where('id',(int)$terkini['id'])->where('status','berlaku')
                    ->update(['status'=>'digantikan','digantikan_pada'=>$now]);
                $this->db->table('sertifikat_aktif')
                    ->where('siswa_id',$sid)->where('paket_semester_id',$pid)
                    ->update(['sertifikat_id'=>$id,'diubah_pada'=>$now]);
            } else {
                $this->db->table('sertifikat_aktif')->insert([
                    'siswa_id'=>$sid,'paket_semester_id'=>$pid,
                    'sertifikat_id'=>$id,'diubah_pada'=>$now,
                ]);
            }
            $this->db->table('log_aktivitas')->insert([
                'pengguna_id'=>$pelaku,'peran_aktif'=>$peran,
                'peristiwa'=>$otomatis
                    ?($revisi?'sertifikat_otomatis_direvisi':'sertifikat_otomatis_diterbitkan')
                    :($revisi?'sertifikat_direvisi':'sertifikat_diterbitkan'),
                'catatan'=>'Sertifikat ID '.$id.'; paket '.$pid.'; versi '.$r['versi']
                    .($revisi?'; pengganti ID '.$terkini['id']:''),
                'waktu'=>$now,
            ]);
            $penerima=$this->db->table('siswa')->select('pengguna_id')
                ->where('id',$sid)->get(1)->getRowArray();
            if (!$penerima) throw new RuntimeException('Penerima sertifikat tidak ditemukan.');
            NotifikasiService::catat($this->db,
                $revisi?'sertifikat_pengganti_terbit':'sertifikat_awal_terbit',
                (int)$penerima['pengguna_id'],$id,(int)$r['versi']);
            if ($id<1 || $this->db->transStatus()===false) {
                throw new RuntimeException('Transaksi sertifikat tidak berhasil.');
            }
            $this->db->transCommit();
            return $this->db->table('sertifikat')->where('id',$id)->get()->getRowArray();
        } catch (Throwable $e) {
            $this->db->transRollback();
            @unlink($r['path']); // Hanya berkas baru; PDF historis tidak disentuh.
            throw $e;
        }
    }

    public function aktifUntukPaket(int $paketId,array $siswaIds): array
    {
        $this->admin();
        if ($paketId<1 || $siswaIds===[]) return [];
        $rows=$this->db->table('sertifikat_aktif a')
            ->select('a.siswa_id,c.id,c.nomor,c.versi,c.diterbitkan_pada')
            ->join('sertifikat c','c.id=a.sertifikat_id')
            ->where('a.paket_semester_id',$paketId)
            ->whereIn('a.siswa_id',array_map('intval',$siswaIds))
            ->get()->getResultArray();
        $map=[];
        foreach ($rows as $r) $map[(int)$r['siswa_id']]=$r;
        return $map;
    }

    /**
     * Terbitkan atau revisi sertifikat pribadi saat halaman dibuka.
     * Hanya paket aktif/ditugaskan milik Gen siswa; tidak memerlukan
     * tombol/konfirmasi Admin. Setiap paket diverifikasi akademik ulang.
     *
     * @return array{diperbarui:int,belum_layak:int,galat:int}
     */
    public function sinkronkanSaya(): array
    {
        $a=$this->role();
        if ($a['peran_aktif']!=='siswa') {
            throw new DomainException('Penerbitan otomatis hanya bagi pemilik siswa.');
        }
        $s=$this->db->table('siswa s')
            ->select('s.id,s.angkatan_id,s.semester_mulai,'
                .'g.semester_mulai AS gen_semester_mulai')
            ->join('ref_angkatan g','g.id=s.angkatan_id')
            ->where('s.pengguna_id',(int)$a['id'])->get(1)->getRowArray();
        if (!$s) throw new DomainException('Akun belum memiliki profil siswa.');
        $sid=(int)$s['id'];
        $mulai=(int)($s['semester_mulai']??$s['gen_semester_mulai']);
        $paket=$this->db->table('paket_semester p')
            ->distinct()->select('p.id,p.semester')
            ->join('penugasan_paket pp','pp.paket_id=p.id'
                .' AND pp.angkatan_id=p.angkatan_id'
                .' AND pp.mapel_id=p.mapel_id'
                .' AND pp.semester=p.semester'
                .' AND pp.tahun_ajaran_id=p.tahun_ajaran_id')
            ->where('p.angkatan_id',(int)$s['angkatan_id'])
            ->where('p.status','aktif')
            ->where('p.semester >=',$mulai)
            ->orderBy('p.semester','ASC')->orderBy('p.id','ASC')
            ->get()->getResultArray();
        $hasil=['diperbarui'=>0,'belum_layak'=>0,'galat'=>0];
        foreach ($paket as $p) {
            try {
                $pid=(int)$p['id'];
                $lama=$this->aktif($sid,$pid);
                $hasilTerbit=$this->simpan($sid,$pid,$lama!==null,
                    'Pembaruan otomatis isi sertifikat personal',true);
                if ($lama===null || (int)$lama['id']!==(int)$hasilTerbit['id']) {
                    $hasil['diperbarui']++;
                }
            } catch (DomainException $e) {
                // Paket belum tuntas/identitas/TTD belum siap: halaman lain
                // tetap tersedia; tidak menciptakan sertifikat palsu.
                $hasil['belum_layak']++;
            } catch (Throwable $e) {
                $hasil['galat']++;
                log_message('error','Sertifikat otomatis paket #{id}: {error}',[
                    'id'=>(int)$p['id'],'error'=>$e->getMessage(),
                ]);
            }
        }
        return $hasil;
    }

    public function daftarSaya(): array
    {
        $a=$this->role();
        if ($a['peran_aktif']!=='siswa') {
            throw new DomainException('Daftar sertifikat hanya untuk Siswa.');
        }
        return $this->db->table('sertifikat c')
            ->select('c.id,c.nomor,c.versi,c.status,c.diterbitkan_pada,'
                .'p.semester,m.nama AS nama_mapel')
            ->join('siswa s','s.id=c.siswa_id')
            ->join('paket_semester p','p.id=c.paket_semester_id')
            ->join('ref_mapel m','m.id=p.mapel_id')
            ->where('s.pengguna_id',(int)$a['id'])
            ->orderBy('c.diterbitkan_pada','DESC')
            ->orderBy('c.id','DESC')
            ->get()->getResultArray();
    }

    public function pdfBerizin(int $id): array
    {
        $a=$this->role();
        $row=$this->db->table('sertifikat c')
            ->select('c.id,c.siswa_id,c.pdf_nama_acak,c.pdf_sha256,c.nomor,'
                .'s.pengguna_id')
            ->join('siswa s','s.id=c.siswa_id')->where('c.id',$id)
            ->get()->getRowArray();
        if (!$row || ($a['peran_aktif']==='siswa'
            && (int)$row['pengguna_id']!==(int)$a['id'])) {
            throw new DomainException('Sertifikat tidak ditemukan.');
        }
        $nama=(string)$row['pdf_nama_acak'];
        if (!preg_match('/^[a-f0-9]{48}$/D',$nama)) {
            throw new DomainException('Nama PDF historis tidak valid.');
        }
        $path=SertifikatAsetService::direktori('pdf')
            .DIRECTORY_SEPARATOR.$nama.'.pdf';
        if (!is_file($path) || !is_readable($path)
            || hash_file('sha256',$path)!==$row['pdf_sha256']) {
            throw new DomainException('PDF sertifikat tidak dapat diverifikasi.');
        }
        return ['path'=>$path,'nomor'=>$row['nomor']];
    }

    /** Data PUBLIK terbatas, tanpa NISN, TTD, alasan, kontak atau berkas PDF. */
    public function verifikasi(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{48}$/D',$token)) return null;
        $r=$this->db->table('sertifikat c')
            ->select('c.id,c.nomor,c.versi,c.status,c.diterbitkan_pada,'
                .'c.menggantikan_id,c.snapshot_json,c.snapshot_sha256,'
                .'c.siswa_id,c.paket_semester_id')
            ->where('c.kode_verifikasi',$token)->get()->getRowArray();
        if (!$r) return null;
        if (!hash_equals((string)$r['snapshot_sha256'],
            hash('sha256',(string)$r['snapshot_json']))) {
            throw new RuntimeException('Checksum snapshot sertifikat tidak sesuai.');
        }
        $konten=json_decode((string)$r['snapshot_json'],true,32,JSON_THROW_ON_ERROR);
        $aktif=$this->db->table('sertifikat_aktif a')
            ->select('c.nomor,c.kode_verifikasi,c.id')
            ->join('sertifikat c','c.id=a.sertifikat_id')
            ->where('a.siswa_id',(int)$r['siswa_id'])
            ->where('a.paket_semester_id',(int)$r['paket_semester_id'])
            ->get()->getRowArray();
        // Proyeksi whitelisted; jangan pernah mengirim keseluruhan snapshot.
        $tp=[];
        foreach ($konten['tp']??[] as $t) {
            $tp[]=[
                'kode_tp'=>$t['kode_tp']??'',
                'nama_tp'=>$t['nama_tp']??'',
                'jenis_tp'=>$t['jenis_tp']??'',
                'nilai_tp'=>($t['jenis_tp']??null)==='esensial'?null:($t['nilai_tp']??null),
            ];
        }
        return [
            'nomor'=>$r['nomor'],'versi'=>$r['versi'],
            'status'=>$r['status'],'tanggal'=>$r['diterbitkan_pada'],
            'nama_siswa'=>$konten['siswa']['nama']??'',
            'nama_mapel'=>$konten['paket']['nama_mapel']??'',
            'semester'=>$konten['paket']['semester']??'',
            'tp'=>$tp,
            'nomor_aktif'=>$aktif['nomor']??null,
            'kode_aktif'=>(int)($aktif['id']??0)===(int)$r['id']?null:($aktif['kode_verifikasi']??null),
        ];
    }
}
