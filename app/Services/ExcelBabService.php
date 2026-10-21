<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * 8 sheet resmi. HMAC melekat pada identitas BAB/struktur/UUID tiap baris.
 * Excel hanya mengisi konten/parameter, tidak menciptakan TP atau mengganti CP.
 */
final class ExcelBabService
{
    public const VERSI = 'BAB-V23-1';
    public const SHEETS = [
        'PETUNJUK','IDENTITAS_BAB','TP','MATERI','TUGAS_HOTS',
        'SOAL_DIAGNOSTIK','SOAL_SUMATIF','_META',
    ];
    public const KOLOM = [
        'TP' => ['tp_id','kode_tp','rumusan','indikator','urutan_atp','cp_utama_id',
            'kkm','bobot_resume','bobot_tugas','bobot_sumatif','resume_min','tugas_min',
            'diag_tayang','sum_tayang','diag_durasi','sum_durasi','sum_maks_percobaan'],
        'MATERI' => ['row_uuid','kode_tp','urutan','judul','jenis','petunjuk_resume','min_waktu_detik','url_youtube','berkas_id'],
        'TUGAS_HOTS' => ['row_uuid','kode_tp','pertanyaan','petunjuk'],
        'SOAL_DIAGNOSTIK' => ['row_uuid','kode_tp','urutan','pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','kunci'],
        'SOAL_SUMATIF' => ['row_uuid','kode_tp','urutan','pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','kunci'],
    ];

    public function __construct(private readonly AkademikGuruContext $ctx = new AkademikGuruContext())
    {
    }

    private function rahasia(): string
    {
        $row=$this->ctx->db->table('rahasia_template_bab')->where('id',1)->get()->getRowArray();
        if($row!==null)return hex2bin($row['rahasia']);
        return $this->ctx->transaksi(function():string {
            $r=$this->ctx->db->query('SELECT rahasia FROM rahasia_template_bab WHERE id=1 FOR UPDATE')->getRowArray();
            if($r)return hex2bin($r['rahasia']);
            $value=random_bytes(32);
            $this->ctx->db->table('rahasia_template_bab')->insert([
                'id'=>1,'rahasia'=>bin2hex($value),'dibuat_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
            return $value;
        });
    }

    private static function safe(string $value):string
    {
        if(preg_match('/^[\x00-\x20]*[=+\-@]/',$value))return "'".$value;
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',$value)??'';
    }

    private static function row(Worksheet $sheet,int $n,array $fields,array $data):void
    {
        foreach($fields as $i=>$key){
            $sheet->setCellValueExplicit([$i+1,$n],self::safe((string)($data[$key]??'')),DataType::TYPE_STRING);
        }
    }

    private function signature(int $bab,int $versi,string $sheet,string $code,string $uuid,string $secret):string
    {
        return hash_hmac('sha256',$bab.'|'.$versi.'|'.$sheet.'|'.$code.'|'.$uuid,$secret);
    }

    /** @return array{nama:string,isi:string} */
    public function unduh(int $babId):array
    {
        $guru=$this->ctx->guru();
        $detail=(new PaketBabService($this->ctx))->detailBab($babId);
        $bab=$detail['bab'];
        if($bab['status']!=='draf')throw new DomainException('Template hanya untuk BAB Draf.');
        if($detail['tp']===[])throw new DomainException('Buat TP dan kaitkan CP terlebih dahulu.');
        $secret=$this->rahasia();
        $b=new Spreadsheet();
        $b->removeSheetByIndex(0);
        foreach(self::SHEETS as $name){$b->addSheet(new Worksheet($b,$name));}
        $b->setActiveSheetIndex(0);
        $petunjuk=$b->getSheetByName('PETUNJUK');
        $notes=[
            'SLA-SKS '.self::VERSI.' — template BAB untuk satu Gen',
            'Isi konten MATERI, TUGAS_HOTS, SOAL_DIAGNOSTIK, dan SOAL_SUMATIF.',
            'Jangan ubah identitas BAB/TP/kode CP/urutan ATP/UUID atau sheet _META.',
            'TP: hanya parameter KKM, bobot, batas karakter/jumlah dan durasi boleh diubah.',
            'Jangan hapus baris lama. Baris kosong baru tidak menghapus data server.',
            'Materi PDF/gambar/audio perlu upload berkas secara terpisah melalui halaman BAB.',
            'Untuk materi YouTube, isi URL HTTPS dari youtube.com atau youtu.be.',
            'Bila ada masalah, impor akan menampilkan sheet, baris, kolom, dan alasan.',
            'Semua perubahan pratinjau harus dikonfirmasi; BAB Siap mengunci seluruh konten.',
        ];
        foreach($notes as $i=>$note)self::row($petunjuk,$i+1,['petunjuk'],['petunjuk'=>$note]);
        $ident=$b->getSheetByName('IDENTITAS_BAB');
        $kelas=$this->ctx->db->table('ref_semester s')
            ->select('s.kelas_id,k.fase_id')->join('ref_kelas k','k.id=s.kelas_id')
            ->where('s.id',$bab['semester'])->get()->getRowArray();
        if(!$kelas || (int)$kelas['fase_id']!==1)
            throw new DomainException('Semester BAB tidak berada pada Fase D yang sah.');
        $keys=['bab_id','paket_id','angkatan_id','mapel_id','semester','kelas_id','fase_id',
            'tahun_ajaran_id','kurikulum_id','judul_bab','urutan_bab','struktur_versi'];
        self::row($ident,1,$keys,array_combine($keys,$keys));
        self::row($ident,2,$keys,[
            'bab_id'=>$babId,'paket_id'=>$bab['paket_id'],'angkatan_id'=>$bab['angkatan_id'],
            'mapel_id'=>$bab['mapel_id'],'semester'=>$bab['semester'],
            'kelas_id'=>$kelas['kelas_id'],'fase_id'=>$kelas['fase_id'],
            'tahun_ajaran_id'=>$bab['tahun_ajaran_id'],'kurikulum_id'=>$bab['kurikulum_id'],
            'judul_bab'=>$bab['judul'],'urutan_bab'=>$bab['urutan'],
            'struktur_versi'=>$bab['struktur_versi'],
        ]);
        $meta=$b->getSheetByName('_META');
        self::row($meta,1,['versi','bab_id','struktur_versi','hmac_konteks'],[
            'versi'=>'versi','bab_id'=>'bab_id','struktur_versi'=>'struktur_versi','hmac_konteks'=>'hmac_konteks',
        ]);
        self::row($meta,2,['versi','bab_id','struktur_versi','hmac_konteks'],[
            'versi'=>self::VERSI,'bab_id'=>$babId,'struktur_versi'=>$bab['struktur_versi'],
            'hmac_konteks'=>$this->signature($babId,(int)$bab['struktur_versi'],'KONTEKS','','',$secret),
        ]);
        self::row($meta,4,['sheet','kode_tp','row_uuid','hmac'],[
            'sheet'=>'sheet','kode_tp'=>'kode_tp','row_uuid'=>'row_uuid','hmac'=>'hmac',
        ]);
        $metaIndex=5;
        $tpById=array_column($detail['tp'],null,'id');
        $kodeById=[];$settings=[];
        foreach($detail['tp'] as $t){
            $kodeById[(int)$t['id']]=$t['kode_tp'];
            $settings[]=$t;
        }
        $rows=['MATERI'=>[],'TUGAS_HOTS'=>[],'SOAL_DIAGNOSTIK'=>[],'SOAL_SUMATIF'=>[]];
        foreach($detail['materi'] as $m)$rows['MATERI'][]=['kode_tp'=>$kodeById[(int)$m['tp_id']]]+$m;
        foreach($detail['hots'] as $h)$rows['TUGAS_HOTS'][]=['kode_tp'=>$kodeById[(int)$h['tp_id']]]+$h;
        foreach($detail['soal'] as $q){
            $sheet=$q['jenis']==='diagnostik'?'SOAL_DIAGNOSTIK':'SOAL_SUMATIF';
            $rows[$sheet][]=['kode_tp'=>$kodeById[(int)$q['tp_id']]]+$q;
        }

        foreach(self::KOLOM as $name=>$headers){
            $sheet=$b->getSheetByName($name);
            self::row($sheet,1,$headers,array_combine($headers,$headers));
            $n=2;
            if($name==='TP'){
                foreach($settings as $t){
                    $cpMain=$this->ctx->db->table('tp_cp')->where('tp_id',$t['id'])
                        ->where('utama',1)->get()->getRowArray();
                    $d=$t;
                    $d['tp_id']=$t['id'];
                    $d['cp_utama_id']=$cpMain['cp_versi_id']??'';
                    self::row($sheet,$n,$headers,$d);
                    $n++;
                }
            }else{
                foreach($detail['tp'] as $t){
                    $existing=array_values(array_filter($rows[$name],
                        static fn($row):bool=>$row['kode_tp']===$t['kode_tp']));
                    $count=count($existing);
                    $prealloc=$name==='TUGAS_HOTS'?max(1,$count)
                        :($name==='MATERI'?max(10,$count)
                        :max(40,(int)$t[$name==='SOAL_DIAGNOSTIK'?'diag_tayang':'sum_tayang'],$count));
                    foreach(array_merge($existing,array_fill(0,$prealloc-$count,[])) as $item){
                        if($n>2001)throw new DomainException('Template melebihi 2000 baris per sheet. Bagi BAB ke beberapa BAB.');
                        $uuid=(string)($item['row_uuid']??bin2hex(random_bytes(16)));
                        $row=['row_uuid'=>$uuid,'kode_tp'=>$t['kode_tp']]+$item;
                        self::row($sheet,$n,$headers,$row);
                        self::row($meta,$metaIndex,['sheet','kode_tp','row_uuid','hmac'],[
                            'sheet'=>$name,'kode_tp'=>$t['kode_tp'],'row_uuid'=>$uuid,
                            'hmac'=>$this->signature($babId,(int)$bab['struktur_versi'],$name,$t['kode_tp'],$uuid,$secret)
                        ]);
                        $metaIndex++;$n++;
                    }
                }
            }
            $sheet->freezePane('A2');
            foreach(range(1,count($headers)) as $c)$sheet->getColumnDimensionByColumn($c)->setWidth(24);
            $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
            $sheet->getStyle('A1:'.$sheet->getHighestColumn().max(2,$n))->getNumberFormat()->setFormatCode('@');
        }
        $meta->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        $file=tempnam(WRITEPATH,'bab_tpl_');
        if($file===false)throw new DomainException('Tidak dapat membuat file sementara di writable.');
        try{
            (new XlsxWriter($b))->save($file);
            $content=file_get_contents($file);
            if(!is_string($content)||$content==='')throw new DomainException('Gagal menulis template XLSX.');
        }finally{$b->disconnectWorksheets();@unlink($file);}
        $this->ctx->catat((int)$guru['id'],'guru','template_bab_diunduh','bab',$babId);
        return ['nama'=>'BAB-'.$babId.'-'.self::VERSI.'.xlsx','isi'=>$content];
    }

    /** @return array<string,mixed> */
    public function pratinjau(int $babId,UploadedFile $file):array
    {
        $guru=$this->ctx->guru();
        $bab=$this->ctx->bab($babId,true);
        $ukuran=$file->getSize();
        if(!$file->isValid() || $ukuran<100 || $ukuran>5*1048576
            || strtolower((string)$file->getClientExtension())!=='xlsx')
            throw new DomainException('File harus XLSX asli dengan ukuran maksimum 5 MB.');
        $path=$file->getTempName();
        $checksum=hash_file('sha256',$path);
        if(!is_string($checksum))throw new DomainException('Checksum file gagal dihitung.');
        $diterapkan=$this->ctx->db->table('impor_bab')
            ->where('bab_id',$babId)->where('guru_id',$guru['id'])
            ->where('checksum',$checksum)->where('status','diterapkan')
            ->orderBy('dibuat_pada','DESC')->get()->getRowArray();
        if($diterapkan!==null){
            return ['id'=>$diterapkan['id'],
                'tambah'=>(int)$diterapkan['jumlah_tambah'],
                'ubah'=>(int)$diterapkan['jumlah_ubah'],'galat'=>0];
        }
        // Data staging lama tidak menyimpan naskah/soal tanpa batas waktu.
        $this->ctx->db->table('impor_bab')->where('status','pratinjau')
            ->where('kedaluwarsa_pada <',gmdate('Y-m-d H:i:s'))
            ->update(['status'=>'kedaluwarsa','data_json'=>'{}']);
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if(!in_array($mime,['application/zip','application/octet-stream',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],true))
            throw new DomainException('MIME unggahan bukan XLSX.');
        $zip=new \ZipArchive();
        if($zip->open($path)!==true)throw new DomainException('XLSX tidak dapat dibuka.');
        try{
            $total=0;
            if($zip->numFiles>250||$zip->locateName('xl/workbook.xml')===false)
                throw new DomainException('Struktur ZIP XLSX tidak dikenal.');
            for($i=0;$i<$zip->numFiles;$i++){
                $stat=$zip->statIndex($i);
                if(!$stat)throw new DomainException('Isi ZIP tidak dapat diperiksa.');
                $total+=(int)$stat['size'];
                if($total>32*1048576)throw new DomainException('XLSX melewati batas data ekstraksi 32 MB.');
                $name=strtolower($stat['name']);
                if(str_contains($name,'vbaproject')||str_ends_with($name,'.bin'))
                    throw new DomainException('Makro atau berkas binary Excel tidak diperbolehkan.');
            }
        }finally{$zip->close();}
        $reader=new XlsxReader();
        $reader->setReadDataOnly(true);
        try{$book=$reader->load($path);}
        catch(Throwable $e){throw new DomainException('XLSX tidak terbaca. Unduh template baru.');}
        try{$parsed=$this->baca($book,$bab);}
        finally{$book->disconnectWorksheets();}
        $id=bin2hex(random_bytes(16));
        $errors=$parsed['galat'];
        $now=time();
        $this->ctx->transaksi(function()use($id,$babId,$guru,$ukuran,$checksum,$parsed,$errors,$now,$bab):void{
            $this->ctx->db->table('impor_bab')->insert([
                'id'=>$id,'bab_id'=>$babId,'guru_id'=>$guru['id'],
                'checksum'=>$checksum,
                'struktur_versi'=>$bab['struktur_versi'],'status'=>'pratinjau',
                'data_json'=>json_encode($parsed['data'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
                'jumlah_tambah'=>$parsed['tambah'],'jumlah_ubah'=>$parsed['ubah'],
                'jumlah_galat'=>count($errors),
                'dibuat_pada'=>gmdate('Y-m-d H:i:s',$now),
                'kedaluwarsa_pada'=>gmdate('Y-m-d H:i:s',$now+1800),
            ]);
            foreach(array_slice($errors,0,2000) as $error){
                $this->ctx->db->table('impor_baris')->insert([
                    'impor_id'=>$id,'sheet_nama'=>$error['sheet'],
                    'nomor_baris'=>$error['baris'],'kolom'=>$error['kolom'],
                    'kode'=>$error['kode'],'pesan'=>mb_substr($error['pesan'],0,255),
                ]);
            }
            $this->ctx->catat((int)$guru['id'],'guru','impor_bab_pratinjau','bab',$babId);
        });
        return ['id'=>$id,'tambah'=>$parsed['tambah'],'ubah'=>$parsed['ubah'],
            'galat'=>count($errors)];
    }

    /** @return array{data:array<string,list<array<string,string>>>,galat:list<array<string,mixed>>,tambah:int,ubah:int} */
    private function baca(Spreadsheet $book,array $bab):array
    {
        if($book->getSheetNames()!==self::SHEETS)throw new DomainException('Urutan/nama delapan sheet tidak sesuai template resmi.');
        $secret=$this->rahasia();
        $ctx=$book->getSheetByName('_META');
        if($ctx->getSheetState()!==Worksheet::SHEETSTATE_VERYHIDDEN)
            throw new DomainException('Sheet _META harus sangat tersembunyi.');
        $a=fn($sheet,$col,$row)=>trim((string)$sheet->getCell([$col,$row])->getValue());
        if($a($ctx,1,2)!==self::VERSI || $a($ctx,2,2)!==(string)$bab['id']
           || $a($ctx,3,2)!==(string)$bab['struktur_versi']
           || !hash_equals($this->signature((int)$bab['id'],(int)$bab['struktur_versi'],'KONTEKS','','',$secret),$a($ctx,4,2)))
            throw new DomainException('HMAC, versi, atau identitas template tidak cocok dengan BAB terkini.');
        $identity=$book->getSheetByName('IDENTITAS_BAB');
        $kelas=$this->ctx->db->table('ref_semester s')
            ->select('s.kelas_id,k.fase_id')->join('ref_kelas k','k.id=s.kelas_id')
            ->where('s.id',$bab['semester'])->get()->getRowArray();
        if(!$kelas || (int)$kelas['fase_id']!==1)
            throw new DomainException('Identitas Fase D/Kelas BAB tidak sah.');
        $expected=[
            'bab_id'=>$bab['id'],'paket_id'=>$bab['paket_id'],
            'angkatan_id'=>$bab['angkatan_id'],'mapel_id'=>$bab['mapel_id'],
            'semester'=>$bab['semester'],'kelas_id'=>$kelas['kelas_id'],
            'fase_id'=>$kelas['fase_id'],'tahun_ajaran_id'=>$bab['tahun_ajaran_id'],
            'kurikulum_id'=>$bab['kurikulum_id'],'judul_bab'=>$bab['judul'],
            'urutan_bab'=>$bab['urutan'],'struktur_versi'=>$bab['struktur_versi'],
        ];
        foreach(array_values($expected) as $i=>$v){
            if($a($identity,$i+1,2)!==(string)$v)
                throw new DomainException('Sheet IDENTITAS_BAB tidak boleh diubah; periksa identitas Gen/Mapel/semester.');
        }
        $meta=[];
        for($r=5;$r<=$ctx->getHighestDataRow();$r++){
            $key=$a($ctx,1,$r).'|'.$a($ctx,2,$r).'|'.$a($ctx,3,$r);
            $meta[$key]=$a($ctx,4,$r);
        }
        $tpList=$this->ctx->db->table('tp')->where('bab_id',$bab['id'])->orderBy('urutan_atp')
            ->get()->getResultArray();
        $tpByCode=array_column($tpList,null,'kode_tp');
        $tpSheet=$book->getSheetByName('TP');
        if($tpSheet->getHighestDataRow()!==count($tpList)+1)
            throw new DomainException('Jumlah baris TP tidak boleh diubah melalui Excel.');
        $data=['TP'=>[],'MATERI'=>[],'TUGAS_HOTS'=>[],'SOAL_DIAGNOSTIK'=>[],'SOAL_SUMATIF'=>[]];
        $galat=[];$tambah=0;$ubah=0;
        $push=function(string $sheet,int $r,string $col,string $kode,string $pesan)use(&$galat):void{
            $galat[]=['sheet'=>$sheet,'baris'=>$r,'kolom'=>$col,'kode'=>$kode,'pesan'=>$pesan];
        };
        foreach(self::KOLOM as $sheet=>$headers){
            $ws=$book->getSheetByName($sheet);
            if(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ws->getHighestDataColumn())>count($headers))
                throw new DomainException('Ada kolom ekstra di sheet '.$sheet);
            foreach($headers as $i=>$h)if($a($ws,$i+1,1)!==$h)
                throw new DomainException('Header '.$sheet.' kolom '.($i+1).' harus '.$h);
            $max=$ws->getHighestDataRow();
            if($max>2001)throw new DomainException('Maksimal 2.000 baris tiap sheet.');
            $seen=[];
            for($r=2;$r<=$max;$r++){
                $row=[];$formula=false;
                foreach($headers as $i=>$h){
                    $cell=$ws->getCell([$i+1,$r]);
                    if($cell->getDataType()===DataType::TYPE_FORMULA)$formula=true;
                    $value=$cell->getValue();
                    $row[$h]=is_scalar($value)?trim((string)$value):'';
                }
                if($formula){$push($sheet,$r,'*','FORMULA','Sel formula tidak diperbolehkan.');continue;}
                if($sheet==='TP'){
                    $t=$tpList[$r-2];
                    foreach(['tp_id'=>'id','kode_tp'=>'kode_tp','rumusan'=>'rumusan',
                        'indikator'=>'indikator','urutan_atp'=>'urutan_atp'] as $col=>$dbcol)
                        if($row[$col] !== (string)$t[$dbcol]){
                            $push($sheet,$r,$col,'TERKUNCI','TP hanya dapat diubah pada layar BAB.');
                        }
                    $main=$this->ctx->db->table('tp_cp')->where('tp_id',$t['id'])
                        ->where('utama',1)->get()->getRowArray();
                    if($row['cp_utama_id']!==(string)($main['cp_versi_id']??''))
                        $push($sheet,$r,'cp_utama_id','CP_TERKUNCI','Tautan CP tidak boleh diubah lewat Excel.');
                    $param=[];
                    foreach(array_slice($headers,6) as $key)$param[$key]=$row[$key];
                    try{$params=PaketBabService::parameterTp($param);}
                    catch(DomainException $e){$push($sheet,$r,'kkm/bobot','PARAMETER',$e->getMessage());continue;}
                    $row['tp_id']=(string)$t['id'];
                    $data['TP'][]=$row;
                    if(array_filter($params,static fn($val,$k)=>$val!==(int)$t[$k],ARRAY_FILTER_USE_BOTH))$ubah++;
                    continue;
                }
                $uuid=$row['row_uuid'];$code=$row['kode_tp'];
                if($uuid==='' && implode('',array_values($row))==='')continue;
                $key=$sheet.'|'.$code.'|'.$uuid;
                if(!preg_match('/^[a-f0-9]{32}$/D',$uuid)||!isset($meta[$key])
                    ||!hash_equals($this->signature((int)$bab['id'],(int)$bab['struktur_versi'],$sheet,$code,$uuid,$secret),$meta[$key])){
                    $push($sheet,$r,'row_uuid','HMAC','UUID baris tidak cocok dengan struktur BAB dan _META.');
                    continue;
                }
                if(!isset($tpByCode[$code])){$push($sheet,$r,'kode_tp','TP','Kode TP tidak tersedia di BAB.');continue;}
                if(isset($seen[$key])){$push($sheet,$r,'row_uuid','DUPLIKAT','UUID baris ganda.');continue;}
                $seen[$key]=true;
                $fields=array_slice($headers,2);
                if(!array_filter($fields,static fn($f)=>!in_array($f,['berkas_id','urutan'],true)&&$row[$f]!==''))
                    continue;
                $tpId=(int)$tpByCode[$code]['id'];
                $table=match($sheet){
                    'MATERI'=>'materi','TUGAS_HOTS'=>'tugas_hots',default=>'soal'
                };
                $q=$this->ctx->db->table($table)->where('tp_id',$tpId)->where('row_uuid',$uuid);
                if($table==='soal')$q->where('jenis',$sheet==='SOAL_DIAGNOSTIK'?'diagnostik':'sumatif');
                $old=$q->get()->getRowArray();
                if(in_array($sheet,['MATERI','SOAL_DIAGNOSTIK','SOAL_SUMATIF'],true)
                    && ctype_digit($row['urutan']) && (int)$row['urutan']>0){
                    $position=$this->ctx->db->table($table)->where('tp_id',$tpId)
                        ->where('urutan',(int)$row['urutan']);
                    if($table==='soal')$position->where('jenis',$sheet==='SOAL_DIAGNOSTIK'?'diagnostik':'sumatif');
                    $occupied=$position->get()->getRowArray();
                    if($occupied!==null && $occupied['row_uuid']!==$uuid){
                        $push($sheet,$r,'urutan','URUTAN','Urutan sudah ditempati baris lain; jangan menghapus/mengganti identitas baris.');
                        continue;
                    }
                }
                if($sheet==='MATERI'){
                    if(!in_array($row['jenis'],['pdf','gambar','audio','youtube'],true)
                        || mb_strlen($row['judul'])<4 || mb_strlen($row['petunjuk_resume'])<15
                        || !ctype_digit($row['urutan']) || (int)$row['urutan']<1 || (int)$row['urutan']>2000
                        || !ctype_digit($row['min_waktu_detik'])||(int)$row['min_waktu_detik']<10
                        || ($row['jenis']==='youtube'&&!BabKontenService::urlYouTubeValid($row['url_youtube']))
                        || ($row['jenis']!=='youtube'&&$row['url_youtube']!=='')){
                        $push($sheet,$r,'jenis/judul/urutan','MATERI','Periksa jenis materi, judul, urutan, URL, waktu dan petunjuk resume.');
                        continue;
                    }
                    if($old && ((string)($old['berkas_id']??'')!==$row['berkas_id']
                        || $old['jenis']!==$row['jenis'])){
                        $push($sheet,$r,'berkas_id','MEDIA','Berkas atau jenis materi lama tidak dapat diganti lewat Excel.');
                        continue;
                    }
                    if(!$old&&$row['berkas_id']!==''){
                        $push($sheet,$r,'berkas_id','MEDIA','Berkas baru wajib diunggah lewat aplikasi.');
                        continue;
                    }
                }elseif($sheet==='TUGAS_HOTS'){
                    if(mb_strlen($row['pertanyaan'])<15||mb_strlen($row['petunjuk'])<12){
                        $push($sheet,$r,'pertanyaan/petunjuk','HOTS','Teks HOTS/petunjuk belum lengkap.');continue;
                    }
                }else{
                    if(!ctype_digit($row['urutan'])|| (int)$row['urutan']<1
                        || !BabKontenService::barisSoalValid($row)){
                        $push($sheet,$r,'opsi/kunci','SOAL','Periksa urutan, opsi A/B/C/D/E tanpa celah dan kunci yang sah.');continue;
                    }
                }
                if($old)$ubah++;else $tambah++;
                $row['tp_id']=(string)$tpId;
                $data[$sheet][]=$row;
            }
        }
        // Hindari pratinjau data parsial bila sheet memiliki baris tidak valid.
        return compact('data','galat','tambah','ubah');
    }

    /** @return array<string,mixed> */
    public function laporan(int $babId,string $id):array
    {
        $guru=$this->ctx->guru();
        $this->ctx->bab($babId);
        if(!preg_match('/^[a-f0-9]{32}$/D',$id))throw new DomainException('Kode impor tidak valid.');
        $batch=$this->ctx->db->table('impor_bab')->where('id',$id)
            ->where('bab_id',$babId)->where('guru_id',$guru['id'])->get()->getRowArray();
        if(!$batch)throw new DomainException('Pratinjau tidak ditemukan atau bukan milik Anda.');
        $errors=$this->ctx->db->table('impor_baris')->where('impor_id',$id)
            ->orderBy('sheet_nama')->orderBy('nomor_baris')->get()->getResultArray();
        $expired=strtotime($batch['kedaluwarsa_pada'].' UTC')<time();
        unset($batch['data_json']);
        return ['batch'=>$batch,'galat'=>$errors,'kedaluwarsa'=>$expired];
    }

    /** @return array{ulang:bool,tambah:int,ubah:int} */
    public function konfirmasi(int $babId,string $id):array
    {
        $guru=$this->ctx->guru();
        $this->ctx->bab($babId,true);
        if(!preg_match('/^[a-f0-9]{32}$/D',$id))throw new DomainException('ID impor salah.');
        return $this->ctx->transaksi(function()use($babId,$id,$guru):array{
            $bab=$this->ctx->db->query('SELECT * FROM bab WHERE id=? FOR UPDATE',[$babId])->getRowArray();
            $batch=$this->ctx->db->query('SELECT * FROM impor_bab WHERE id=? FOR UPDATE',[$id])->getRowArray();
            if(!$bab||$bab['status']!=='draf'||!$batch
                ||(int)$batch['bab_id']!==$babId||(int)$batch['guru_id']!==(int)$guru['id'])
                throw new DomainException('Impor tidak tersedia untuk Guru dan BAB ini.');
            if($batch['status']==='diterapkan')
                return ['ulang'=>true,'tambah'=>(int)$batch['jumlah_tambah'],'ubah'=>(int)$batch['jumlah_ubah']];
            if($batch['status']!=='pratinjau'
                ||strtotime($batch['kedaluwarsa_pada'].' UTC')<time()
                ||(int)$batch['jumlah_galat']>0
                ||(int)$bab['struktur_versi']!==(int)$batch['struktur_versi'])
                throw new DomainException('Pratinjau kedaluwarsa, mengandung galat atau struktur TP sudah berubah. Unduh template baru.');
            $this->ctx->periksaGenMapel(
                (int)$this->ctx->db->table('paket_semester')->where('id',$bab['paket_id'])
                    ->get()->getRowArray()['angkatan_id'],
                (int)$this->ctx->db->table('paket_semester')->where('id',$bab['paket_id'])
                    ->get()->getRowArray()['mapel_id']
            );
            $rows=json_decode($batch['data_json'],true,512,JSON_THROW_ON_ERROR);
            foreach($rows['TP'] as $t){
                $idTp=(int)$t['tp_id'];
                $record=$this->ctx->db->table('tp')->where('id',$idTp)
                    ->where('bab_id',$babId)->get()->getRowArray();
                if(!$record)throw new DomainException('TP berubah sejak pratinjau.');
                $keys=array_slice(self::KOLOM['TP'],6);
                $inputs=[];
                foreach($keys as $key)$inputs[$key]=$t[$key];
                $params=PaketBabService::parameterTp($inputs);
                $this->ctx->db->table('tp')->where('id',$idTp)->update($params);
            }
            foreach(['MATERI','TUGAS_HOTS','SOAL_DIAGNOSTIK','SOAL_SUMATIF'] as $sheet){
                foreach($rows[$sheet] as $row){
                    $tpId=(int)$row['tp_id'];
                    if($this->ctx->db->table('tp')->where('id',$tpId)->where('bab_id',$babId)->countAllResults()!==1)
                        throw new DomainException('TP dari sheet lain tidak boleh digunakan.');
                    $kind=match($sheet){'MATERI'=>'materi','TUGAS_HOTS'=>'tugas_hots',default=>'soal'};
                    $old=$this->ctx->db->table($kind)->where('tp_id',$tpId)
                        ->where('row_uuid',$row['row_uuid'])->get()->getRowArray();
                    $payload=['tp_id'=>$tpId,'row_uuid'=>$row['row_uuid']];
                    if($kind==='materi'){
                        foreach(['urutan','judul','jenis','petunjuk_resume','min_waktu_detik','url_youtube'] as $k)
                            $payload[$k]=$row[$k];
                        if($payload['jenis']!=='youtube')$payload['url_youtube']=null;
                    }elseif($kind==='tugas_hots'){
                        $payload['pertanyaan']=$row['pertanyaan'];$payload['petunjuk']=$row['petunjuk'];
                    }else{
                        $payload['jenis']=$sheet==='SOAL_DIAGNOSTIK'?'diagnostik':'sumatif';
                        foreach(['urutan','pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','kunci'] as $k)
                            $payload[$k]=$row[$k];
                        if(!BabKontenService::barisSoalValid($payload))
                            throw new DomainException('Pilihan/kunci soal sudah tidak valid.');
                    }
                    if($old){
                        if($kind==='materi' && $old['jenis']!==$payload['jenis'])
                            throw new DomainException('Jenis materi berubah; gunakan editor BAB.');
                        $this->ctx->db->table($kind)->where('id',$old['id'])->update($payload);
                    }else{
                        if($kind==='tugas_hots'&&$this->ctx->db->table($kind)
                            ->where('tp_id',$tpId)->countAllResults()>0)
                            throw new DomainException('HOTS TP sudah ada. Gunakan template baru.');
                        $this->ctx->db->table($kind)->insert($payload);
                    }
                }
            }
            $this->ctx->db->table('bab')->where('id',$babId)
                ->set('struktur_versi','struktur_versi+1',false)->update();
            $this->ctx->db->table('impor_bab')->where('id',$id)->update([
                'status'=>'diterapkan','data_json'=>'{}',
                'diterapkan_pada'=>gmdate('Y-m-d H:i:s'),
            ]);
            $this->ctx->catat((int)$guru['id'],'guru','impor_bab_diterapkan','bab',$babId);
            return ['ulang'=>false,'tambah'=>(int)$batch['jumlah_tambah'],'ubah'=>(int)$batch['jumlah_ubah']];
        });
    }
}
