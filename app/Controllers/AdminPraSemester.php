<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PraSemesterJadwalService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class AdminPraSemester extends BaseController
{
    protected $helpers = ['url','form'];

    public function index(?int $paketId = null): ResponseInterface|string
    {
        try {
            $service = new PraSemesterJadwalService();
            $akun = (new AuthService())->penggunaSaatIni();
            return view('admin/pra_semester', [
                'judul'=>'Jadwal Pra-Semester',
                'namaPengguna'=>$akun['nama'], 'peranAktif'=>'Admin',
                'tautanNavigasi'=>NavigasiService::untuk('admin','pra_semester'),
                'daftar'=>$service->daftar(),
                'pilih'=>$paketId !== null ? $service->lihat($paketId) : null,
            ]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak',['judul'=>'Pengaturan Pra-Semester ditolak'])
            );
        } catch (Throwable $e) {
            log_message('error','Jadwal Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Jadwal belum dapat dimuat.');
        }
    }

    public function simpan(int $paketId): RedirectResponse
    {
        $url=site_url('admin/pra-semester/' . $paketId);
        try {
            (new PraSemesterJadwalService())->simpan($paketId,
                $this->request->getPost('mulai'),
                $this->request->getPost('akhir'),
                $this->request->getPost('durasi'));
            return redirect()->to(site_url('admin/pra-semester'))
                ->with('sukses','Jadwal ujian utama dan durasi per peserta berhasil disimpan.');
        } catch (DomainException $e) {
            return redirect()->to($url)->withInput()->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Simpan jadwal Pra-Semester: {error}', ['error'=>$e->getMessage()]);
            return redirect()->to($url)->withInput()
                ->with('galat','Jadwal belum tersimpan. Periksa log server.');
        }
    }
}
