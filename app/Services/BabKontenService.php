<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use RuntimeException;

/** K4B — konten draf; setiap endpoint memeriksa kepemilikan BAB dan status. */
final class BabKontenService
{
    public function __construct(private readonly AkademikGuruContext $ctx = new AkademikGuruContext())
    {
    }

    /** @return array<string,mixed> */
    private function konteksTp(int $tpId): array
    {
        $row=$this->ctx->db->table('tp t')
            ->select('t.id,t.bab_id,b.status,b.urutan')
            ->join('bab b','b.id=t.bab_id')->where('t.id',$tpId)->get()->getRowArray();
        if (!$row)throw new DomainException('TP tidak tersedia.');
        $this->ctx->bab((int)$row['bab_id'],true);
        return $row;
    }

    public static function barisSoalValid(array $data): bool
    {
        $required=['pertanyaan','opsi_a','opsi_b'];
        foreach($required as $key){
            if(mb_strlen(trim((string)($data[$key]??'')))<3)return false;
        }
        $seenGap=false;
        $options=['a','b','c','d','e'];
        $available=[];
        foreach($options as $o){
            $v=trim((string)($data['opsi_'.$o]??''));
            if($v==='')$seenGap=true;
            else{
                if($seenGap)return false;
                $available[]=$o;
            }
        }
        return in_array(strtolower(trim((string)($data['kunci']??''))),$available,true);
    }

    private function drafTerkunci(int $babId): void
    {
        $locked=$this->ctx->db->query('SELECT status FROM bab WHERE id=? FOR UPDATE',[$babId])->getRowArray();
        if(!$locked || $locked['status']!=='draf')throw new DomainException('BAB Siap terkunci; perubahan biasa dilarang.');
    }

    private function versiNaik(int $babId): void
    {
        $this->ctx->db->table('bab')->where('id',$babId)
            ->set('struktur_versi','struktur_versi+1',false)->update();
    }

    public function simpanMateri(int $tpId, array $data): void
    {
        $tp=$this->konteksTp($tpId);
        $judul=trim((string)($data['judul']??''));
        $jenis=trim((string)($data['jenis']??''));
        $petunjuk=trim((string)($data['petunjuk_resume']??''));
        $url=trim((string)($data['url_youtube']??''));
        $waktu=(int)($data['min_waktu_detik']??0);
        if(!in_array($jenis,['pdf','audio','gambar','youtube'],true)
            || mb_strlen($judul)<4 || mb_strlen($judul)>180
            || mb_strlen($petunjuk)<15 || mb_strlen($petunjuk)>4000
            || $waktu<10 || $waktu>36000) {
            throw new DomainException('Isi jenis, judul, petunjuk resume ≥15 karakter dan waktu 10–36000 detik.');
        }
        if ($jenis==='youtube' && ! self::urlYouTubeValid($url))
            throw new DomainException('Tautan YouTube harus HTTPS pada youtube.com atau youtu.be.');
        if($jenis!=='youtube' && $url!=='')
            throw new DomainException('URL YouTube hanya untuk jenis video YouTube.');
        $guru=$this->ctx->guru();
        $this->ctx->transaksi(function()use($tp,$tpId,$judul,$jenis,$petunjuk,$waktu,$url,$guru):void{
            $this->drafTerkunci((int)$tp['bab_id']);
            $last=$this->ctx->db->table('materi')->selectMax('urutan','maks')
                ->where('tp_id',$tpId)->get()->getRowArray();
            $n=(int)($last['maks']??0)+1;
            if($n>100)throw new DomainException('Maksimal 100 materi per TP.');
            $this->ctx->db->table('materi')->insert([
                'tp_id'=>$tpId,'row_uuid'=>bin2hex(random_bytes(16)),'urutan'=>$n,
                'judul'=>$judul,'jenis'=>$jenis,'petunjuk_resume'=>$petunjuk,
                'min_waktu_detik'=>$waktu,'url_youtube'=>$jenis==='youtube'?$url:null,
            ]);
            $this->versiNaik((int)$tp['bab_id']);
            $this->ctx->catat((int)$guru['id'],'guru','materi_draf_dibuat','materi',(int)$this->ctx->db->insertID());
        });
    }

    public static function urlYouTubeValid(string $url): bool
    {
        if (!filter_var($url,FILTER_VALIDATE_URL)
            || strtolower((string)parse_url($url,PHP_URL_SCHEME))!=='https')return false;
        $host=strtolower((string)parse_url($url,PHP_URL_HOST));
        if(!in_array($host,['youtube.com','www.youtube.com','m.youtube.com','youtu.be'],true))return false;
        $path=(string)parse_url($url,PHP_URL_PATH);
        if($host==='youtu.be')return preg_match('/^\/[A-Za-z0-9_-]{11}$/D',$path)===1;
        if($path==='/watch'){
            parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
            return isset($query['v'])&&is_string($query['v'])
                &&preg_match('/^[A-Za-z0-9_-]{11}$/D',$query['v'])===1;
        }
        return preg_match('/^\/(embed|shorts)\/[A-Za-z0-9_-]{11}$/D',$path)===1;
    }

    public function simpanHots(int $tpId,string $pertanyaan,string $petunjuk): void
    {
        $tp=$this->konteksTp($tpId);
        $pertanyaan=trim($pertanyaan);$petunjuk=trim($petunjuk);
        if(mb_strlen($pertanyaan)<15 || mb_strlen($petunjuk)<12 ||
            mb_strlen($pertanyaan)>8000 || mb_strlen($petunjuk)>4000)
            throw new DomainException('Pertanyaan HOTS minimal 15 dan petunjuk minimal 12 karakter.');
        $guru=$this->ctx->guru();
        $this->ctx->transaksi(function()use($tp,$tpId,$pertanyaan,$petunjuk,$guru):void{
            $this->drafTerkunci((int)$tp['bab_id']);
            $old=$this->ctx->db->table('tugas_hots')->where('tp_id',$tpId)->get()->getRowArray();
            if($old){
                $this->ctx->db->table('tugas_hots')->where('id',$old['id'])->update([
                    'pertanyaan'=>$pertanyaan,'petunjuk'=>$petunjuk,
                ]);
                $id=(int)$old['id'];
            }else{
                $this->ctx->db->table('tugas_hots')->insert([
                    'tp_id'=>$tpId,'row_uuid'=>bin2hex(random_bytes(16)),
                    'pertanyaan'=>$pertanyaan,'petunjuk'=>$petunjuk,
                ]);
                $id=(int)$this->ctx->db->insertID();
            }
            $this->versiNaik((int)$tp['bab_id']);
            $this->ctx->catat((int)$guru['id'],'guru','hots_draf_disimpan','tugas_hots',$id);
        });
    }

    public function simpanSoal(int $tpId,string $jenis,array $form): void
    {
        $tp=$this->konteksTp($tpId);
        if (!in_array($jenis,['diagnostik','sumatif'],true)||!self::barisSoalValid($form))
            throw new DomainException('Soal memerlukan teks pertanyaan, opsi A/B berurutan tanpa celah, dan kunci pilihan yang terisi.');
        $data=[];
        foreach(['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','kunci'] as $key)
            $data[$key]=trim((string)($form[$key]??''));
        if(mb_strlen($data['pertanyaan'])>6000 || max(array_map('mb_strlen',[
            $data['opsi_a'],$data['opsi_b'],$data['opsi_c'],$data['opsi_d'],$data['opsi_e']
        ]))>2000)throw new DomainException('Teks pertanyaan/opsi melebihi batas.');
        $data['kunci']=strtoupper($data['kunci']);
        $guru=$this->ctx->guru();
        $this->ctx->transaksi(function()use($tp,$tpId,$jenis,$data,$guru):void{
            $this->drafTerkunci((int)$tp['bab_id']);
            $last=$this->ctx->db->table('soal')->selectMax('urutan','n')
                ->where('tp_id',$tpId)->where('jenis',$jenis)->get()->getRowArray();
            $n=(int)($last['n']??0)+1;
            if($n>2000)throw new DomainException('Maksimum soal per jenis 2000.');
            $this->ctx->db->table('soal')->insert($data+[
                'tp_id'=>$tpId,'jenis'=>$jenis,'urutan'=>$n,'row_uuid'=>bin2hex(random_bytes(16))
            ]);
            $this->versiNaik((int)$tp['bab_id']);
            $this->ctx->catat((int)$guru['id'],'guru','soal_draf_dibuat','soal',(int)$this->ctx->db->insertID());
        });
    }

    /**
     * Durasi audio diverifikasi dari metadata kontainer (M4A) atau header frame (MP3).
     * MP3 VBR tanpa metadata terverifikasi ditolak alih-alih mengarang durasi.
     */
    public static function durasiAudio(string $bytes,string $ext):?int
    {
        if($ext==='m4a'){
            $pos=strpos($bytes,'mvhd');
            if($pos===false)return null;
            $version=ord($bytes[$pos+4]??"\0");
            if($version===0){
                if(strlen($bytes)<$pos+24)return null;
                $scale=unpack('N',substr($bytes,$pos+16,4))[1];
                $duration=unpack('N',substr($bytes,$pos+20,4))[1];
            }elseif($version===1){
                if(strlen($bytes)<$pos+36)return null;
                $scale=unpack('N',substr($bytes,$pos+24,4))[1];
                $parts=unpack('Nhigh/Nlow',substr($bytes,$pos+28,8));
                $duration=$parts['high']*4294967296+$parts['low'];
            }else return null;
            $seconds=$scale>0 ? (int)ceil($duration/$scale) : 0;
            return $seconds>0&&$seconds<=14400?$seconds:null;
        }
        if($ext!=='mp3')return null;
        $size=strlen($bytes);
        $start=0;
        if(substr($bytes,0,3)==='ID3' && $size>=10){
            $start=10+((ord($bytes[6])&127)<<21)+((ord($bytes[7])&127)<<14)
                +((ord($bytes[8])&127)<<7)+(ord($bytes[9])&127);
        }
        for($i=$start;$i<min($size-200,$start+32768);$i++){
            if(ord($bytes[$i])!==255 || (ord($bytes[$i+1])&224)!==224)continue;
            $h=unpack('N',substr($bytes,$i,4))[1];
            $version=($h>>19)&3;
            $layer=($h>>17)&3;
            $bitIndex=($h>>12)&15;
            $sampleIndex=($h>>10)&3;
            if($version===1||$layer!==1||$bitIndex===0||$bitIndex===15||$sampleIndex===3)
                continue;
            $rates=$version===3
                ? [0,32,40,48,56,64,80,96,112,128,160,192,224,256,320,0]
                : [0,8,16,24,32,40,48,56,64,80,96,112,128,144,160,0];
            $rate=$rates[$bitIndex];
            $sample=([44100,48000,32000][$sampleIndex]);
            if($version===2)$sample=(int)($sample/2);
            if($version===0)$sample=(int)($sample/4);
            $frameSamples=$version===3?1152:576;
            $frameLength=(int)floor(($version===3?144:72)*$rate*1000/$sample);
            if($frameLength<10||$i+$frameLength+4>$size)continue;
            if(ord($bytes[$i+$frameLength])!==255
                ||(ord($bytes[$i+$frameLength+1])&224)!==224)continue;

            // VBR Xing/Info header memiliki frame count untuk penghitungan akurat.
            $segment=substr($bytes,$i,min(240,$size-$i));
            $xing=strpos($segment,'Xing');
            if($xing===false)$xing=strpos($segment,'Info');
            if($xing!==false && strlen($segment)>=$xing+12){
                $flags=unpack('N',substr($segment,$xing+4,4))[1];
                if(($flags&1)===1){
                    $frames=unpack('N',substr($segment,$xing+8,4))[1];
                    $duration=(int)ceil($frames*$frameSamples/$sample);
                    return $duration>=1&&$duration<=14400?$duration:null;
                }
            }
            // MP3 CBR dapat dihitung dari semua frame berurutan (bukan hanya perkiraan bitrate).
            $cursor=$i;
            $frameCount=0;
            while($cursor+4 <= $size && $frameCount < 200000){
                $next=unpack('N',substr($bytes,$cursor,4))[1];
                if(($next & 0xFFE00000)!==0xFFE00000)break;
                if((($next>>19)&3)!==$version || (($next>>17)&3)!==1
                    ||(($next>>12)&15)!==$bitIndex || (($next>>10)&3)!==$sampleIndex)
                    return null; // Variasi bitrate berarti VBR; perlu Xing/Info.
                if($cursor+$frameLength>$size)return null;
                $cursor+=$frameLength;
                $frameCount++;
            }
            $trailing=$size-$cursor;
            if($frameCount<2 || $trailing>128
                ||($trailing>0 && substr($bytes,$cursor,3)!=='TAG'
                    && trim(substr($bytes,$cursor),"\\0")!==''))return null;
            $duration=(int)ceil($frameCount*$frameSamples/$sample);
            return $duration>=1&&$duration<=14400?$duration:null;
        }
        return null;
    }

    /**
     * Inspeksi konservatif: berkas ambigu ditolak.
     * Semua file disimpan privat dan tidak diunggah ke public/.
     */
    public function unggahBerkas(int $materiId,UploadedFile $file): void
    {
        $m=$this->ctx->db->table('materi m')->select('m.*,t.bab_id')
            ->join('tp t','t.id=m.tp_id')->where('m.id',$materiId)->get()->getRowArray();
        if(!$m)throw new DomainException('Materi tidak ditemukan.');
        $this->ctx->bab((int)$m['bab_id'],true);
        if($m['jenis']==='youtube')throw new DomainException('Video menggunakan tautan YouTube, bukan upload.');
        if(!$file->isValid())throw new DomainException('Berkas gagal diunggah.');
        $type=$m['jenis'];
        $limits=['pdf'=>15*1048576,'gambar'=>8*1048576,'audio'=>10*1048576];
        $size=$file->getSize();
        if(!$size || $size>$limits[$type])throw new DomainException('Ukuran berkas terlalu besar.');
        $source=$file->getTempName();
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($source);
        $ext=strtolower((string)$file->getClientExtension());
        $allow=[
            'pdf'=>['application/pdf'=>['pdf']],
            'gambar'=>['image/jpeg'=>['jpg','jpeg'],'image/png'=>['png'],'image/webp'=>['webp']],
            'audio'=>['audio/mpeg'=>['mp3'],'audio/mp3'=>['mp3'],'audio/mp4'=>['m4a'],'audio/x-m4a'=>['m4a'],'video/mp4'=>['m4a']],
        ];
        if(!isset($allow[$type][$mime])||!in_array($ext,$allow[$type][$mime],true))
            throw new DomainException('Ekstensi/MIME file tidak sesuai jenis materi.');
        $contents=file_get_contents($source);
        if(!is_string($contents))throw new DomainException('File tidak dapat dibaca.');
        $pages=null;
        $durasi=$type==='audio'?self::durasiAudio($contents,$ext):null;
        if($type==='audio'&&$durasi===null)
            throw new DomainException('Durasi audio tidak dapat diverifikasi dari metadata MP3/M4A. Gunakan audio dengan metadata durasi yang valid.');
        if($type==='pdf'){
            if(!str_starts_with($contents,'%PDF-') ||
                preg_match('/\/(JavaScript|JS|Launch|OpenAction|EmbeddedFile)\b/i',$contents))
                throw new DomainException('PDF mengandung struktur yang tidak diizinkan.');
            $pages=preg_match_all('/\/Type\s*\/Page\b/',$contents);
            if($pages<1 || $pages>40 || preg_match('/\/Encrypt\b/',$contents))
                throw new DomainException('PDF tidak terverifikasi 1–40 halaman atau terenkripsi.');
        }
        $dir=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.'berkas';
        if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))
            throw new RuntimeException('Folder privat tidak dapat dibuat.');
        $filename=bin2hex(random_bytes(20)).'.'.$ext;
        $path=$dir.DIRECTORY_SEPARATOR.$filename;
        if($type==='gambar'){
            $info=@getimagesize($source);
            if(!$info||$info['mime']!==$mime)throw new DomainException('Gambar tidak valid.');
            $img=@imagecreatefromstring($contents);
            if(!$img)throw new DomainException('Gambar gagal didekode.');
            try{
                $w=imagesx($img);$h=imagesy($img);
                $scale=min(1,1600/max($w,$h));
                $new=imagecreatetruecolor(max(1,(int)round($w*$scale)),max(1,(int)round($h*$scale)));
                imagealphablending($new,false);imagesavealpha($new,true);
                imagecopyresampled($new,$img,0,0,0,0,imagesx($new),imagesy($new),$w,$h);
                try{
                    $ok=match($mime){
                        'image/jpeg'=>imagejpeg($new,$path,85),
                        'image/png'=>imagepng($new,$path,6),
                        default=>imagewebp($new,$path,82),
                    };
                }finally{imagedestroy($new);}
            }finally{imagedestroy($img);}
        }else{
            $ok=file_put_contents($path,$contents,LOCK_EX)!==false;
        }
        unset($contents);
        if(!$ok||!is_file($path)){@unlink($path);throw new RuntimeException('Penyimpanan berkas gagal.');}
        @chmod($path,0600);
        $guru=$this->ctx->guru();
        try{
            $this->ctx->transaksi(function()use($m,$file,$mime,$pages,$durasi,$filename,$path,$materiId,$guru):void{
                $this->drafTerkunci((int)$m['bab_id']);
                $this->ctx->db->table('berkas')->insert([
                    'bab_id'=>$m['bab_id'],'nama_acak'=>$filename,'mime'=>$mime,
                    'ukuran_byte'=>filesize($path),'checksum'=>hash_file('sha256',$path),
                    'halaman'=>$pages,'durasi_detik'=>$durasi,'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
                ]);
                $id=(int)$this->ctx->db->insertID();
                $this->ctx->db->table('materi')->where('id',$materiId)->update(['berkas_id'=>$id]);
                $this->versiNaik((int)$m['bab_id']);
                $this->ctx->catat((int)$guru['id'],'guru','berkas_materi_diunggah','berkas',$id);
            });
        }catch(\Throwable $e){@unlink($path);throw $e;}
    }

    /** @return array{mime:string,path:string} */
    public function media(int $berkasId): array
    {
        $file=$this->ctx->db->table('berkas f')
            ->select('f.*,m.tp_id,t.bab_id')
            ->join('materi m','m.berkas_id=f.id')
            ->join('tp t','t.id=m.tp_id')
            ->where('f.id',$berkasId)->get()->getRowArray();
        if(!$file)throw new DomainException('Berkas tidak tersedia.');
        $this->ctx->bab((int)$file['bab_id']);
        if(!preg_match('/^[a-f0-9]{40}\.(pdf|jpg|jpeg|png|webp|mp3|m4a)$/D',$file['nama_acak']))
            throw new DomainException('Nama berkas tidak sah.');
        $path=rtrim(WRITEPATH,'/\\').DIRECTORY_SEPARATOR.'berkas'.DIRECTORY_SEPARATOR.$file['nama_acak'];
        if(!is_file($path)||!is_readable($path))throw new DomainException('Berkas tidak ditemukan pada storage.');
        return ['mime'=>$file['mime'],'path'=>$path];
    }
}
