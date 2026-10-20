<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\MutasiPenggunaService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class AdminMutasiPengguna extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function service(): MutasiPenggunaService
    {
        return new MutasiPenggunaService();
    }

    private function dataPanel(string $judul, string $jenis): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        return [
            'judul' => $judul,
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', $jenis),
            'uiPrototipe' => true,
        ];
    }

    private function nilai(string $key): string
    {
        $value = $this->request->getPost($key);
        return is_string($value) ? trim($value) : '';
    }

    public function editPengelola(int $id): RedirectResponse|string
    {
        return $this->edit('pengelola', $id);
    }

    public function editSiswa(int $id): RedirectResponse|string
    {
        return $this->edit('siswa', $id);
    }

    private function edit(string $jenis, int $id): RedirectResponse|string
    {
        try {
            $target = $this->service()->detail($jenis, $id);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis))->with('galat', $e->getMessage());
        }
        return view('admin/akun_edit', array_merge(
            $this->dataPanel('Edit ' . ucfirst($jenis), $jenis),
            ['jenis' => $jenis, 'target' => $target]
        ));
    }

    public function simpanPengelola(int $id): RedirectResponse
    {
        return $this->simpan('pengelola', $id);
    }

    public function simpanSiswa(int $id): RedirectResponse
    {
        return $this->simpan('siswa', $id);
    }

    private function simpan(string $jenis, int $id): RedirectResponse
    {
        $input = $this->request->getPost('peran');
        $peran = $jenis === 'siswa' ? ['siswa']
            : (is_array($input) ? array_values(array_filter($input, 'is_string')) : []);
        try {
            $this->service()->ubahIdentitas(
                $jenis, $id, $this->nilai('username'), $this->nilai('nama'), $peran
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis . '/' . $id . '/edit'))
                ->with('galat', $e->getMessage());
        }

        $akun = (new AuthService())->penggunaSaatIni();
        if ($akun === null) {
            // Mengedit akun sendiri mengubah versi sesi; wajib masuk lagi.
            return redirect()->to(site_url('masuk'))
                ->with('sukses', 'Identitas diperbarui. Silakan masuk kembali.');
        }
        return redirect()->to(site_url('admin/' . $jenis))
            ->with('sukses', 'Identitas berhasil diperbarui dan tercatat pada audit.');
    }

    public function statusPengelola(int $id): RedirectResponse|string
    {
        return $this->konfirmasiStatus('pengelola', $id);
    }

    public function statusSiswa(int $id): RedirectResponse|string
    {
        return $this->konfirmasiStatus('siswa', $id);
    }

    private function konfirmasiStatus(string $jenis, int $id): RedirectResponse|string
    {
        try {
            $target = $this->service()->detail($jenis, $id);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis))->with('galat', $e->getMessage());
        }
        $admin = (new AuthService())->penggunaSaatIni();
        return view('admin/akun_status_konfirmasi', array_merge(
            $this->dataPanel('Konfirmasi status akun', $jenis),
            ['jenis' => $jenis, 'target' => $target, 'akunSendiri' => (int) $admin['id'] === $id]
        ));
    }

    public function ubahStatusPengelola(int $id): RedirectResponse
    {
        return $this->simpanStatus('pengelola', $id);
    }

    public function ubahStatusSiswa(int $id): RedirectResponse
    {
        return $this->simpanStatus('siswa', $id);
    }

    private function simpanStatus(string $jenis, int $id): RedirectResponse
    {
        $status = $this->nilai('status');
        try {
            $this->service()->ubahStatus($jenis, $id, $status);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis . '/' . $id . '/status'))
                ->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/' . $jenis))
            ->with('sukses', $status === 'aktif'
                ? 'Akun berhasil diaktifkan kembali.'
                : 'Akun dinonaktifkan. Sesi lama tidak berlaku dan data tetap tersimpan.');
    }
}
