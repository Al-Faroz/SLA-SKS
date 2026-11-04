<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\NotifikasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class Notifikasi extends BaseController
{
    protected $helpers=['url','form'];

    public function index(): ResponseInterface
    {
        try {
            $a=(new AuthService())->penggunaSaatIni();
            $page=$this->request->getGet('halaman');
            $page=is_scalar($page)&&ctype_digit((string)$page)
                ?max(1,min(100000,(int)$page)):1;
            $data=(new NotifikasiService())->daftar($page);
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('notifikasi/index',[
                    'judul'=>'Notifikasi',
                    'namaPengguna'=>$a['nama'],
                    'peranAktif'=>ucfirst((string)$a['peran_aktif']),
                    'tautanNavigasi'=>NavigasiService::untuk((string)$a['peran_aktif'],'notifikasi'),
                    'daftar'=>$data,
                    'peran'=>(string)$a['peran_aktif'],
                ]));
        }catch(DomainException $e){
            return $this->response->setStatusCode(403)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody('Akses Notifikasi tidak diizinkan.');
        }catch(Throwable $e){
            log_message('error','Notifikasi daftar: {jenis}',['jenis'=>get_class($e)]);
            return $this->response->setStatusCode(503)->setHeader('Cache-Control','no-store')
                ->setBody('Notifikasi belum dapat dibuka. Periksa instalasi SQL V4-0013.');
        }
    }

    public function tandai(int $id): RedirectResponse
    {
        try {
            (new NotifikasiService())->tandaiDibaca($id);
            return redirect()->to(site_url('notifikasi'))->with('sukses','Notifikasi ditandai dibaca.');
        }catch(DomainException $e){
            return redirect()->to(site_url('notifikasi'))->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Notifikasi tandai: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to(site_url('notifikasi'))->with('galat',
                'Status notifikasi belum dapat disimpan. Coba kembali.');
        }
    }

    /** Klik GET tidak mengubah status baca. Tujuan di-whitelist dan hak sumber diverifikasi. */
    public function buka(int $id): RedirectResponse
    {
        try {
            $tujuan=(new NotifikasiService())->tujuanBerizin($id);
            return redirect()->to(site_url($tujuan));
        }catch(DomainException $e){
            return redirect()->to(site_url('notifikasi'))->with('galat',$e->getMessage());
        }catch(Throwable $e){
            log_message('error','Notifikasi buka: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to(site_url('notifikasi'))->with('galat',
                'Tujuan notifikasi tidak tersedia. Coba kembali.');
        }
    }
}
