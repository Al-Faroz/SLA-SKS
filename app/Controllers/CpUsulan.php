<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AkademikGuruContext;
use App\Services\AuthService;
use App\Services\UsulanCpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class CpUsulan extends BaseController
{
    protected $helpers=['url','form'];

    private function viewData(string $role,string $page):array
    {
        $a=(new AuthService())->penggunaSaatIni();
        return [
            'judul'=>$page,'namaPengguna'=>$a['nama'],'peranAktif'=>ucfirst($role),
            'tautanNavigasi'=>NavigasiService::untuk($role,$role==='guru'?'usulan_cp':'usulan_cp'),
        ];
    }

    public function guru():string
    {
        $ctx=AkademikGuruContext::buka();
        $s=new UsulanCpService($ctx);
        $pilihan=$ctx->daftarGenMapel();
        $cp=[];
        foreach(array_unique(array_column($pilihan,'mapel_id')) as $mapel)
            foreach($s->cpUntukGuru((int)$mapel) as $row)$cp[$row['id']]=$row;
        return view('guru/usulan_cp',array_merge(
            $this->viewData('guru','Usulan CP Guru'),[
                'cp'=>array_values($cp),'riwayat'=>$s->daftarGuru()
            ]
        ));
    }

    public function ajukan():RedirectResponse
    {
        $input=[];
        foreach(['elemen','rumusan','otoritas','nomor_dokumen','sumber_url','tanggal_berlaku'] as $k)
            $input[$k]=$this->string($k);
        try{
            (new UsulanCpService())->ajukan((int)$this->string('cp_id'),
                $this->string('jenis'),$this->string('alasan'),$input);
            return redirect()->to(site_url('guru/usulan-cp'))
                ->with('sukses','Usulan CP terkirim dan menunggu pemeriksaan Admin.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/usulan-cp'))->withInput()->with('galat',$e->getMessage());
        }
    }

    public function admin():string
    {
        $s=new UsulanCpService();
        return view('admin/usulan_cp',array_merge(
            $this->viewData('admin','Persetujuan Usulan CP'),['daftar'=>$s->daftarAdmin()]
        ));
    }

    public function detail(int $id):string|RedirectResponse
    {
        try{
            $s=new UsulanCpService();
            return view('admin/usulan_cp_detail',array_merge(
                $this->viewData('admin','Keputusan Usulan CP'),['usulan'=>$s->detailAdmin($id)]
            ));
        }catch(DomainException $e){
            return redirect()->to(site_url('admin/usulan-cp'))->with('galat',$e->getMessage());
        }
    }

    public function putuskan(int $id):RedirectResponse
    {
        try{
            (new UsulanCpService())->putuskan($id,$this->string('keputusan'),$this->string('catatan'));
            return redirect()->to(site_url('admin/usulan-cp/'.$id))
                ->with('sukses','Keputusan akademik telah disimpan dan diaudit.');
        }catch(DomainException $e){
            return redirect()->to(site_url('admin/usulan-cp/'.$id))->withInput()
                ->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Keputusan usulan CP gagal ('.get_class($e).').');
            return redirect()->to(site_url('admin/usulan-cp/'.$id))
                ->with('galat','Gagal menyimpan keputusan. Periksa log server.');
        }
    }

    private function string(string $key):string
    {
        $v=$this->request->getPost($key);
        return is_string($v)?trim($v):'';
    }
}
