<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KoreksiTpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class AdminKoreksiTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function layout(): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        return [
            'judul' => 'Persetujuan Koreksi TP',
            'namaPengguna' => $akun['nama'], 'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', 'koreksi_tp'),
        ];
    }

    public function index(): string
    {
        $svc = new KoreksiTpService();
        return view('admin/koreksi_tp_daftar', array_merge($this->layout(), [
            'tersedia' => $svc->tersedia(),
            'daftar' => $svc->tersedia() ? $svc->daftarAdmin() : [],
        ]));
    }

    public function detail(int $id): string|RedirectResponse
    {
        try {
            $row = (new KoreksiTpService())->lihatAdmin($id);
            return view('admin/koreksi_tp_detail', array_merge($this->layout(), ['koreksi' => $row]));
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/koreksi-tp'))->with('galat', $e->getMessage());
        }
    }

    public function putuskan(int $id): RedirectResponse
    {
        $pilihan = $this->request->getPost('keputusan');
        $catatan = $this->request->getPost('catatan');
        if (!in_array($pilihan, ['setuju','tolak'], true) || !is_string($catatan)) {
            return redirect()->to(site_url('admin/koreksi-tp/' . $id))
                ->with('galat', 'Keputusan dan catatan Admin wajib diisi.');
        }
        try {
            $versiId = (new KoreksiTpService())->putuskan($id, $pilihan === 'setuju', $catatan);
            return redirect()->to(site_url('admin/koreksi-tp/' . $id))
                ->with('sukses', $versiId === null
                    ? 'Permohonan ditolak; TP dan semua versi lama tetap utuh.'
                    : 'Permohonan disetujui dan versi TP baru diterbitkan (ID versi ' . $versiId . ').');
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/koreksi-tp/' . $id))->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Putuskan koreksi TP: {e}', ['e' => $e->getMessage()]);
            return redirect()->to(site_url('admin/koreksi-tp/' . $id))
                ->with('galat', 'Keputusan gagal disimpan. Transaksi dibatalkan; periksa log server.');
        }
    }
}
