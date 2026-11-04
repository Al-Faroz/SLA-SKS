<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DashboardGuruService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** GET dashboard tugas Guru, peran aktif dan G1 dikunci di Service. */
final class GuruDashboard extends BaseController
{
    protected $helpers=['url'];

    private function angka(string $nama, int $maks): ?int
    {
        $v=$this->request->getGet($nama);
        if ($v===null || $v==='') return null;
        if (!is_string($v) || !ctype_digit($v) || strlen($v)>7
            || (int)$v>$maks) {
            throw new DomainException('Filter dashboard tidak sah.');
        }
        return (int)$v;
    }

    public function index(): ResponseInterface
    {
        try {
            $pilihan=$this->angka('pilihan',10000)??0;
            $semester=$this->angka('semester',6)??0;
            $dashboard=(new DashboardGuruService())->ringkasan($pilihan,$semester);
            $a=(new AuthService())->penggunaSaatIni();
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('guru/dashboard',[
                    'judul'=>'Dashboard Guru',
                    'namaPengguna'=>$a['nama']??'',
                    'peranAktif'=>'Guru',
                    'tautanNavigasi'=>NavigasiService::untuk('guru','guru'),
                    'dashboard'=>$dashboard,
                ]));
        } catch (DomainException $e) {
            if ($this->request->getGet()!==[]) {
                return redirect()->to(site_url('guru'))->with('galat_dashboard_guru',
                    'Penugasan atau semester tidak valid. Pilih data yang tersedia.');
            }
            return $this->response->setStatusCode(403)->setHeader('Cache-Control','private, no-store')
                ->setBody(view('publik/ditolak',['judul'=>'Dashboard Guru tidak tersedia']));
        } catch (Throwable $e) {
            log_message('error','Dashboard Guru A12-2: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setHeader('Cache-Control','private, no-store')
                ->setBody('Dashboard Guru belum dapat dimuat. Periksa log aplikasi.');
        }
    }
}
