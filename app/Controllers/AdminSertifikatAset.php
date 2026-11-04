<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\SertifikatAsetService;
use App\Services\SertifikatPdfService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Pengelolaan background/TTD privat dan pratinjau dummy sebelum aktivasi. */
final class AdminSertifikatAset extends BaseController
{
    protected $helpers=['url','form'];

    public function index(): ResponseInterface
    {
        try {
            $svc=new SertifikatAsetService();
            $akun=(new AuthService())->penggunaSaatIni();
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('admin/sertifikat_aset',[
                    'judul'=>'Template dan Tanda Tangan Sertifikat',
                    'namaPengguna'=>$akun['nama']??'',
                    'peranAktif'=>'Admin',
                    'tautanNavigasi'=>NavigasiService::untuk('admin','sertifikat'),
                    'aset'=>$svc->daftar(),
                    'aktif'=>$svc->aktif(),
                ]));
        } catch (Throwable $e) {
            log_message('error','Daftar aset sertifikat: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody(
                'Aset sertifikat belum dapat dimuat. Pastikan SQL V4-0011 sudah diimpor.');
        }
    }

    public function unggah(string $jenis): RedirectResponse
    {
        $file=$this->request->getFile('gambar');
        if ($file===null) {
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('galat','Pilih file gambar terlebih dahulu.');
        }
        try {
            (new SertifikatAsetService())->unggah($file,$jenis);
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('sukses','Gambar berhasil disimpan privat. Tinjau sebelum diaktifkan.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Unggah aset sertifikat: {error}',['error'=>$e->getMessage()]);
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('galat','Berkas gagal disimpan. Periksa izin folder dan SQL V4-0011.');
        }
    }

    public function gambar(int $id): ResponseInterface
    {
        try {
            $svc=new SertifikatAsetService();
            $aset=$svc->ambil($id);
            $path=SertifikatAsetService::path($aset);
            $data=@file_get_contents($path);
            if (!is_string($data)) throw new DomainException('Berkas hilang.');
            return $this->response
                ->setHeader('Content-Type',(string)$aset['mime'])
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('Content-Disposition','inline')
                ->setHeader('Cache-Control','private, no-store')
                ->setBody($data);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody('Gambar tidak tersedia.');
        }
    }

    /** Preview PDF data CONTOH, bukan data siswa dari database. */
    public function pratinjau(int $id): ResponseInterface
    {
        try {
            $svc=new SertifikatAsetService();
            $background=$id===0?null:$svc->ambil($id,'background');
            $aktif=$svc->aktif();
            $ttd=null;
            if (isset($aktif['ttd'])) {
                $ttd=$svc->ambil((int)$aktif['ttd']['id'],'ttd');
            }
            $pdf=(new SertifikatPdfService())->pratinjau($background,$ttd);
            if ($id>0) session()->set('sertifikat_preview_bg',$id);
            return $this->response
                ->setHeader('Content-Type','application/pdf')
                ->setHeader('Content-Disposition','inline; filename="pratinjau-sertifikat.pdf"')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('Cache-Control','private, no-store')
                ->setBody($pdf);
        } catch (Throwable $e) {
            log_message('error','Preview sertifikat: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(422)
                ->setBody('Pratinjau belum dapat dibuat. Periksa gambar dan ekstensi PDF.');
        }
    }

    public function aktifkan(int $id): RedirectResponse
    {
        try {
            $svc=new SertifikatAsetService();
            $a=$svc->ambil($id);
            if ($a['jenis']==='background'
                && (int)session()->get('sertifikat_preview_bg')!==$id) {
                throw new DomainException(
                    'Pratinjau PDF template ini terlebih dahulu sebelum diaktifkan.');
            }
            $svc->aktifkan($id);
            if ($a['jenis']==='background') session()->remove('sertifikat_preview_bg');
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('sukses','Aset sertifikat aktif. Aset historis tidak ditimpa.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Aktivasi aset sertifikat: {error}',['error'=>$e->getMessage()]);
            return redirect()->to(site_url('admin/sertifikat/aset'))
                ->with('galat','Aset tidak dapat diaktifkan. Periksa berkas dan SQL.');
        }
    }
}
