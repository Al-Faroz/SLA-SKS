<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DashboardSiswaService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A12-3: dashboard Siswa, GET baca-saja dan seluruh akses paket dari Katalog. */
final class SiswaDashboard extends BaseController
{
    protected $helpers=['url'];

    public function index(): ResponseInterface
    {
        try {
            $dashboard=(new DashboardSiswaService())->ringkasan();
            $akun=(new AuthService())->penggunaSaatIni();
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('siswa/dashboard',[
                    'judul'=>'Dashboard Siswa',
                    'namaPengguna'=>$akun['nama']??'',
                    'peranAktif'=>'Siswa',
                    'tautanNavigasi'=>NavigasiService::untuk('siswa','siswa'),
                    'dashboard'=>$dashboard,
                ]));
        } catch (DomainException $e) {
            // Tidak membocorkan apakah Gen/paket siswa lain tersedia.
            log_message('notice','Dashboard Siswa ditolak: {pesan}',
                ['pesan'=>$e->getMessage()]);
            return $this->response->setStatusCode(403)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody(view('publik/ditolak',[
                    'judul'=>'Dashboard Siswa tidak tersedia',
                    'pesan'=>'Periksa status akun, Gen, dan semester mulai kepada Admin.',
                ]));
        } catch (Throwable $e) {
            log_message('error','Dashboard Siswa A12-3: {error}',
                ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody('Dashboard belum dapat dimuat. Silakan coba kembali.');
        }
    }
}
