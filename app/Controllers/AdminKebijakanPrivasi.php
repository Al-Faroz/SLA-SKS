<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KebijakanPrivasiService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class AdminKebijakanPrivasi extends BaseController
{
    protected $helpers=['url','form'];

    private function shell(string $judul): array
    {
        $a=(new AuthService())->penggunaSaatIni();
        return [
            'judul'=>$judul,'namaPengguna'=>$a['nama'],
            'peranAktif'=>'Admin',
            'tautanNavigasi'=>NavigasiService::untuk('admin','privasi'),
        ];
    }

    public function index(): ResponseInterface
    {
        try {
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('admin/kebijakan_privasi', $this->shell('Kelola Kebijakan Privasi')+[
                    'daftar'=>(new KebijakanPrivasiService())->daftar(),
                ]));
        }catch(Throwable $e){
            log_message('error','Kebijakan Privasi daftar: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setHeader('Cache-Control','no-store')
                ->setBody('Kebijakan Privasi belum dapat dibuka. Pastikan SQL V4-0013 terpasang.');
        }
    }

    public function editor(?int $id=null): ResponseInterface
    {
        try {
            $svc=new KebijakanPrivasiService();
            $row=$id? $svc->rincian($id):null;
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('admin/kebijakan_privasi_editor',
                    $this->shell($row?'Rincian Kebijakan Privasi':'Tambah Draf Kebijakan Privasi')+[
                    'baris'=>$row,
                    'riwayat'=>$id?$svc->riwayat($id):[],
                ]));
        }catch(DomainException $e){
            return $this->response->setStatusCode(403)->setHeader('Cache-Control','no-store')
                ->setBody('Versi dokumen tidak ditemukan atau tidak diizinkan.');
        }catch(Throwable $e){
            log_message('error','Kebijakan Privasi editor: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setHeader('Cache-Control','no-store')
                ->setBody('Editor Kebijakan Privasi belum tersedia. Periksa SQL V4-0013.');
        }
    }

    public function simpan(?int $id=null): RedirectResponse
    {
        $tujuan=site_url($id?'admin/kebijakan-privasi/'.$id:'admin/kebijakan-privasi/baru');
        try {
            $input=$this->request->getPost();
            $new=(new KebijakanPrivasiService())->simpanDraf(is_array($input)?$input:[],$id);
            return redirect()->to(site_url('admin/kebijakan-privasi/'.$new))
                ->with('sukses','Draf disimpan bersama audit. Belum diterbitkan ke halaman publik.');
        }catch(DomainException $e){
            return redirect()->to($tujuan)->withInput()->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Kebijakan Privasi simpan: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to($tujuan)->withInput()->with('galat',
                'Draf gagal disimpan. Periksa label versi unik dan koneksi database.');
        }
    }

    public function terbit(int $id): RedirectResponse
    {
        $tujuan=site_url('admin/kebijakan-privasi/'.$id);
        try {
            $revisi=$this->request->getPost('revisi_draf');
            if(!is_scalar($revisi)||!ctype_digit((string)$revisi)){
                throw new DomainException('Nomor revisi tidak sah. Muat ulang editor.');
            }
            (new KebijakanPrivasiService())->terbitkan(
                $id,(int)$revisi,$this->request->getPost('sudah_disahkan'));
            return redirect()->to($tujuan)->with('sukses',
                'Versi telah diterbitkan. Halaman publik mengikuti tanggal berlaku (WIB).');
        }catch(DomainException $e){
            return redirect()->to($tujuan)->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Kebijakan Privasi terbit: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to($tujuan)->with('galat',
                'Penerbitan gagal; tidak ada perubahan parsial. Periksa koneksi database.');
        }
    }
}
