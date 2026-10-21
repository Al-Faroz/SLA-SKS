<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BabKontenService;
use App\Services\ExcelBabService;
use App\Services\AuthService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class GuruBabFile extends BaseController
{
    protected $helpers=['url','form'];

    private function kembali(int $bab,string $message,bool $ok=false):RedirectResponse
    {
        return redirect()->to(site_url('guru/bab/'.$bab))
            ->with($ok?'sukses':'galat',$message);
    }

    public function unggah(int $materiId):RedirectResponse
    {
        $m=db_connect()->table('materi m')->select('t.bab_id')
            ->join('tp t','t.id=m.tp_id')->where('m.id',$materiId)->get()->getRowArray();
        $bab=(int)($m['bab_id']??0);
        $file=$this->request->getFile('berkas');
        if($file===null)return $this->kembali($bab,'Pilih berkas materi.');
        try{
            (new BabKontenService())->unggahBerkas($materiId,$file);
            return $this->kembali($bab,'File materi disimpan privat.',true);
        }catch(DomainException $e){return $this->kembali($bab,$e->getMessage());}
        catch(Throwable $e){
            log_message('error','Upload materi gagal ('.get_class($e).').');
            return $this->kembali($bab,'Upload gagal. Periksa jenis file/izin storage.');
        }
    }

    public function berkas(int $id):ResponseInterface
    {
        try{
            $file=(new BabKontenService())->media($id);
            $bytes=file_get_contents($file['path']);
            if($bytes===false)throw new DomainException('Berkas tidak terbaca.');
            $size=strlen($bytes);
            $response=$this->response->setHeader('Content-Type',$file['mime'])
                ->setHeader('Content-Disposition','inline')
                ->setHeader('Cache-Control','private,no-store')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('Accept-Ranges','bytes');
            $range=$this->request->getHeaderLine('Range');
            if($range!==''){
                if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$range,$match)
                    ||($match[1]===''&&$match[2]==='')){
                    return $response->setStatusCode(416)
                        ->setHeader('Content-Range','bytes */'.$size)->setBody('');
                }
                if($match[1]===''){
                    $suffix=(int)$match[2];
                    $start=max(0,$size-$suffix);$end=$size-1;
                }else{
                    $start=(int)$match[1];$end=$match[2]===''?$size-1:(int)$match[2];
                }
                if($start>=$size || $end<$start){
                    return $response->setStatusCode(416)
                        ->setHeader('Content-Range','bytes */'.$size)->setBody('');
                }
                $end=min($end,$size-1);
                return $response->setStatusCode(206)
                    ->setHeader('Content-Range','bytes '.$start.'-'.$end.'/'.$size)
                    ->setHeader('Content-Length',(string)($end-$start+1))
                    ->setBody(substr($bytes,$start,$end-$start+1));
            }
            return $response->setHeader('Content-Length',(string)$size)->setBody($bytes);
        }catch(DomainException $e){
            return $this->response->setStatusCode(404)->setBody('Berkas tidak tersedia.');
        }
    }

    public function template(int $bab):ResponseInterface
    {
        try{
            $data=(new ExcelBabService())->unduh($bab);
            return $this->response->download($data['nama'],$data['isi'],true)
                ->setHeader('Cache-Control','private,no-store')
                ->setHeader('X-Content-Type-Options','nosniff');
        }catch(DomainException $e){return $this->kembali($bab,$e->getMessage());}
    }

    public function impor(int $bab):RedirectResponse
    {
        $file=$this->request->getFile('berkas');
        if($file===null)return $this->kembali($bab,'Pilih berkas XLSX.');
        try{
            $hasil=(new ExcelBabService())->pratinjau($bab,$file);
            return redirect()->to(site_url('guru/bab/'.$bab.'/impor/'.$hasil['id']));
        }catch(DomainException $e){return $this->kembali($bab,$e->getMessage());}
        catch(Throwable $e){
            log_message('error','Impor BAB gagal ('.get_class($e).').');
            return $this->kembali($bab,'Tidak dapat membaca file. Pastikan template BAB terbaru dan struktur database tersedia.');
        }
    }

    public function pratinjau(int $bab,string $id):string|RedirectResponse
    {
        try{
            $data=(new ExcelBabService())->laporan($bab,$id);
            $a=(new AuthService())->penggunaSaatIni();
            return view('guru/bab_impor_pratinjau',[
                'judul'=>'Pratinjau Impor BAB','namaPengguna'=>$a['nama'],
                'peranAktif'=>'Guru','tautanNavigasi'=>NavigasiService::untuk('guru','paket'),
                'babId'=>$bab,
            ]+$data);
        }catch(DomainException $e){return $this->kembali($bab,$e->getMessage());}
    }

    public function konfirmasi(int $bab,string $id):RedirectResponse
    {
        try{
            $hasil=(new ExcelBabService())->konfirmasi($bab,$id);
            $message=$hasil['ulang']?'Impor ini sudah diterapkan; tidak ada duplikasi.':
                'Impor berhasil: '.$hasil['tambah'].' tambah, '.$hasil['ubah'].' diperbarui.';
            return $this->kembali($bab,$message,true);
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$bab.'/impor/'.$id))
                ->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Konfirmasi impor BAB gagal ('.get_class($e).').');
            return redirect()->to(site_url('guru/bab/'.$bab.'/impor/'.$id))
                ->with('galat','Gagal konfirmasi. Data tetap utuh atau transaksi dibatalkan.');
        }
    }
}
