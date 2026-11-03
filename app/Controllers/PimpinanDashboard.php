<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DashboardPimpinanService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A12-1: beranda KPI baca-saja Pimpinan, bukan mutasi akademik. */
final class PimpinanDashboard extends BaseController
{
    protected $helpers=['url'];

    private function filterAngka(string $key, int $batas): ?int
    {
        $raw=$this->request->getGet($key);
        if ($raw===null || $raw==='') return null;
        if (!is_string($raw) || !ctype_digit($raw) || strlen($raw)>7
            || (int)$raw<1 || (int)$raw>$batas) {
            throw new DomainException('Filter dashboard tidak sah.');
        }
        return (int)$raw;
    }

    public function index(): ResponseInterface
    {
        try {
            $filter=[
                'gen'=>$this->filterAngka('gen',1000000),
                'mapel'=>$this->filterAngka('mapel',1000000),
                'semester'=>$this->filterAngka('semester',6),
            ];
            $halaman=$this->filterAngka('halaman',100000)??1;
            $akun=(new AuthService())->penggunaSaatIni();
            $data=(new DashboardPimpinanService())->ringkasan($filter,$halaman);
            return $this->response
                ->setHeader('Cache-Control','private, no-store')
                ->setBody(view('pimpinan/dashboard',[
                    'judul'=>'Dashboard Pimpinan',
                    'namaPengguna'=>$akun['nama']??'',
                    'peranAktif'=>'Pimpinan',
                    'tautanNavigasi'=>NavigasiService::untuk('pimpinan','pimpinan'),
                    'dashboard'=>$data,
                ]));
        } catch (DomainException $e) {
            if ($this->request->getGet()!==[]) {
                return redirect()->to(site_url('pimpinan'))
                    ->with('galat_dashboard','Filter dashboard tidak sah. Pilih data yang tersedia.');
            }
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak',['judul'=>'Dashboard tidak tersedia'])
            );
        } catch (Throwable $e) {
            log_message('error','Dashboard Pimpinan A12-1: {error}',
                ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody('Ringkasan akademik belum dapat dimuat. Periksa log aplikasi.');
        }
    }
}
