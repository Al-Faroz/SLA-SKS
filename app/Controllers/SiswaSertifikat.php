<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\SertifikatPenerbitanService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Siswa hanya melihat/unduh versi sertifikat miliknya. */
final class SiswaSertifikat extends BaseController
{
    protected $helpers=['url'];

    public function index(): ResponseInterface
    {
        try {
            $a=(new AuthService())->penggunaSaatIni();
            $data=(new SertifikatPenerbitanService())->daftarSaya();
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('siswa/sertifikat',[
                    'judul'=>'Sertifikat Pembelajaran',
                    'namaPengguna'=>$a['nama']??'',
                    'peranAktif'=>'Siswa',
                    'tautanNavigasi'=>NavigasiService::untuk('siswa','sertifikat'),
                    'sertifikat'=>$data,
                ]));
        } catch (Throwable $e) {
            log_message('error','Daftar sertifikat siswa: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Daftar sertifikat belum dapat dimuat.');
        }
    }

    public function unduh(int $id): ResponseInterface
    {
        try {
            $file=(new SertifikatPenerbitanService())->pdfBerizin($id);
            $bytes=@file_get_contents($file['path']);
            if (!is_string($bytes)) throw new DomainException('PDF tidak tersedia.');
            return $this->response->setHeader('Content-Type','application/pdf')
                ->setHeader('Content-Disposition','attachment; filename="sertifikat-'.$id.'.pdf"')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('Cache-Control','private, no-store')
                ->setBody($bytes);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody('Sertifikat tidak ditemukan atau bukan milik Anda.');
        }
    }
}
