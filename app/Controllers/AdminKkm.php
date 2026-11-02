<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KkmTpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class AdminKkm extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function index(): string
    {
        $akun = (new AuthService())->penggunaSaatIni();
        return view('admin/kkm', [
            'judul' => 'Pengaturan KKM',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', 'kkm'),
            'kkm_default' => (new KkmTpService())->defaultAdmin(),
        ]);
    }

    public function simpan(): RedirectResponse
    {
        $nilai = $this->request->getPost('kkm_default');
        if (!is_string($nilai)) {
            return redirect()->to(site_url('admin/kkm'))->with('galat', 'KKM harus berupa angka 1–100.');
        }
        try {
            (new KkmTpService())->simpanDefault($nilai);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/kkm'))->withInput()->with('galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan KKM default: {reason}', ['reason' => $e->getMessage()]);
            return redirect()->to(site_url('admin/kkm'))->with('galat', 'Gagal menyimpan KKM. Periksa log server.');
        }
        return redirect()->to(site_url('admin/kkm'))->with('sukses', 'KKM default berhasil disimpan. TP draf tanpa KKM khusus Guru mengikuti nilai ini.');
    }
}
