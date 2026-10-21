<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AkademikGuruContext;
use App\Services\AuthService;
use App\Services\BabKontenService;
use App\Services\BabKesiapanService;
use App\Services\NavigasiService;
use App\Services\PaketBabService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class GuruBab extends BaseController
{
    protected $helpers=['url','form'];

    private function layout(string $judul):array
    {
        $akun=(new AuthService())->penggunaSaatIni();
        return ['judul'=>$judul,'namaPengguna'=>$akun['nama'],'peranAktif'=>'Guru',
            'tautanNavigasi'=>NavigasiService::untuk('guru','paket')];
    }

    private function input(string $k):string
    {
        $v=$this->request->getPost($k);
        return is_string($v)?trim($v):'';
    }

    private function params():array
    {
        $p=[];
        foreach(['kkm','bobot_resume','bobot_tugas','bobot_sumatif','resume_min','tugas_min',
            'diag_tayang','sum_tayang','diag_durasi','sum_durasi','sum_maks_percobaan'] as $k){
            $p[$k]=$this->input($k);
        }
        return $p;
    }

    public function editor(int $id):string|RedirectResponse
    {
        try{
            $ctx=AkademikGuruContext::buka();
            $p=new PaketBabService($ctx);
            return view('guru/bab_editor',array_merge(
                $this->layout('Struktur BAB, TP dan materi'),
                $p->detailBab($id),[
                    'cp'=>$p->cpAktif($id),
                    'checklist'=>(new BabKesiapanService($ctx))->checklist($id),
                ]
            ));
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/paket'))->with('galat',$e->getMessage());
        }
    }

    public function tambahTp(int $id):RedirectResponse
    {
        $ids=$this->request->getPost('cp_versi_id');
        $ids=is_array($ids)?array_values(array_filter($ids,'is_scalar')):[];
        try{
            (new PaketBabService())->tambahTp(
                $id,$this->input('kode_tp'),$this->input('rumusan'),
                $this->input('indikator'),$ids,$this->params()
            );
            return redirect()->to(site_url('guru/bab/'.$id))->with('sukses','TP beserta CP berhasil disimpan.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$id))->withInput()->with('galat',$e->getMessage());
        }
    }

    private function lokasiTp(int $tpId):int
    {
        $row=db_connect()->table('tp')->select('bab_id')->where('id',$tpId)
            ->get()->getRowArray();
        return (int)($row['bab_id']??0);
    }

    public function parameter(int $tpId):RedirectResponse
    {
        $bab=$this->lokasiTp($tpId);
        try{
            (new PaketBabService())->ubahParameter($tpId,$this->params());
            return redirect()->to(site_url('guru/bab/'.$bab))->with('sukses','Pengaturan TP tersimpan.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$bab))->withInput()->with('galat',$e->getMessage());
        }
    }

    public function materi(int $tpId):RedirectResponse
    {
        $bab=$this->lokasiTp($tpId);
        $data=[];
        foreach(['judul','jenis','petunjuk_resume','min_waktu_detik','url_youtube'] as $k)
            $data[$k]=$this->input($k);
        try{
            (new BabKontenService())->simpanMateri($tpId,$data);
            return redirect()->to(site_url('guru/bab/'.$bab))->with('sukses','Materi draf berhasil disimpan.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$bab))->withInput()->with('galat',$e->getMessage());
        }
    }

    public function hots(int $tpId):RedirectResponse
    {
        $bab=$this->lokasiTp($tpId);
        try{
            (new BabKontenService())->simpanHots($tpId,$this->input('pertanyaan'),$this->input('petunjuk'));
            return redirect()->to(site_url('guru/bab/'.$bab))->with('sukses','Tugas HOTS berhasil disimpan.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$bab))->withInput()->with('galat',$e->getMessage());
        }
    }

    public function soal(int $tpId,string $jenis):RedirectResponse
    {
        $bab=$this->lokasiTp($tpId);
        $data=[];
        foreach(['pertanyaan','opsi_a','opsi_b','opsi_c','opsi_d','opsi_e','kunci'] as $k)
            $data[$k]=$this->input($k);
        try{
            (new BabKontenService())->simpanSoal($tpId,$jenis,$data);
            return redirect()->to(site_url('guru/bab/'.$bab))->with('sukses','Soal berhasil disimpan.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$bab))->withInput()->with('galat',$e->getMessage());
        }
    }

    public function checklist(int $id):string|RedirectResponse
    {
        try{
            $ctx=AkademikGuruContext::buka();
            $bab=$ctx->bab($id);
            $check=(new BabKesiapanService($ctx))->checklist($id);
            return view('guru/bab_checklist',array_merge(
                $this->layout('Checklist publikasi BAB'),['bab'=>$bab,'checklist'=>$check]
            ));
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/paket'))->with('galat',$e->getMessage());
        }
    }

    public function siap(int $id):RedirectResponse
    {
        try{
            (new BabKesiapanService())->terbitkan($id);
            return redirect()->to(site_url('guru/bab/'.$id))
                ->with('sukses','BAB berhasil diterbitkan sebagai Siap dan terkunci.');
        }catch(DomainException $e){
            return redirect()->to(site_url('guru/bab/'.$id.'/checklist'))
                ->with('galat',$e->getMessage());
        }
    }
}
