<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PengaturanService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class AdminIdentitas extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function index(): string
    {
        $akun = (new AuthService())->penggunaSaatIni();
        $service = new PengaturanService();

        return view('admin/identitas', [
            'judul' => 'Identitas Madrasah',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', 'identitas'),
            'identitas' => $service->ambilIdentitas(),
            'labelIdentitas' => PengaturanService::IDENTITAS,
        ]);
    }

    public function simpan(): RedirectResponse
    {
        try {
            $input = $this->request->getPost();
            (new PengaturanService())->simpanIdentitas(is_array($input) ? $input : []);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/identitas'))->withInput()
                ->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/identitas'))
            ->with('sukses', 'Identitas madrasah berhasil disimpan dan dicatat pada riwayat.');
    }
}
