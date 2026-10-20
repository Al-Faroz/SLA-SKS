<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PenggunaAdminService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class AdminPengguna extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function service(): PenggunaAdminService
    {
        return new PenggunaAdminService();
    }

    private function isian(string $nama): string
    {
        $nilai = $this->request->getPost($nama);
        return is_string($nilai) ? trim($nilai) : '';
    }

    private function layout(string $judul, string $jenis): array
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

    public function pengelola(): string
    {
        return $this->daftar('pengelola');
    }

    public function siswa(): string
    {
        return $this->daftar('siswa');
    }

    private function daftar(string $jenis): string
    {
        $cari = $this->request->getGet('cari');
        $status = $this->request->getGet('status');
        $halaman = $this->request->getGet('halaman');
        $genParam = $this->request->getGet('gen');
        $genFilter = is_string($genParam) && ctype_digit($genParam) ? (int) $genParam : null;
        $data = $this->service()->daftar(
            $jenis,
            is_string($cari) ? $cari : '',
            is_string($status) ? $status : '',
            is_string($halaman) && ctype_digit($halaman) ? (int) $halaman : 1,
            $jenis === 'siswa' ? $genFilter : null
        );

        return view('admin/akun_daftar', array_merge(
            $this->layout($jenis === 'siswa' ? 'Siswa' : 'Pengelola', $jenis),
            $data,
            ['jenis' => $jenis]
        ));
    }

    public function buatPengelola(): RedirectResponse
    {
        return $this->buat('pengelola');
    }

    public function buatSiswa(): RedirectResponse
    {
        return $this->buat('siswa');
    }

    private function buat(string $jenis): RedirectResponse
    {
        $pilihan = $this->request->getPost('peran');
        $peran = $jenis === 'siswa' ? ['siswa']
            : (is_array($pilihan) ? array_values(array_filter($pilihan, 'is_string')) : []);

        $gen = $this->isian('angkatan_id');
        $semester = $this->isian('semester_mulai');
        $genId = ctype_digit($gen) ? (int) $gen : null;
        $semesterMulai = $semester === '' ? null
            : (ctype_digit($semester) ? (int) $semester : -1);

        try {
            $this->service()->buat(
                $jenis, $this->isian('username'), $this->isian('nama'),
                $this->isian('sandi'), $peran, $genId, $semesterMulai
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis))->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/' . $jenis))
            ->with('sukses', 'Akun berhasil dibuat. Sampaikan sandi awal secara privat.');
    }

    public function tempatkanSiswa(int $id): RedirectResponse
    {
        $gen = $this->isian('angkatan_id');
        try {
            $this->service()->tempatkanSiswa($id, ctype_digit($gen) ? (int) $gen : 0);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/siswa'))->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/siswa'))->with('sukses', 'Gen siswa berhasil ditetapkan.');
    }

    /** @return string */
    public function konfirmasiResetPengelola(int $id): string|RedirectResponse
    {
        return $this->konfirmasiReset('pengelola', $id);
    }

    public function konfirmasiResetSiswa(int $id): string|RedirectResponse
    {
        return $this->konfirmasiReset('siswa', $id);
    }

    private function konfirmasiReset(string $jenis, int $id): string|RedirectResponse
    {
        try {
            $target = $this->service()->target($jenis, $id);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis))->with('galat', $e->getMessage());
        }
        $admin = (new AuthService())->penggunaSaatIni();

        return view('admin/akun_reset_konfirmasi', array_merge(
            $this->layout('Konfirmasi reset sandi', $jenis),
            [
                'jenis' => $jenis,
                'target' => $target,
                'akunSendiri' => (int) $admin['id'] === $id,
            ]
        ));
    }

    public function resetPengelola(int $id): RedirectResponse|string
    {
        return $this->reset('pengelola', $id);
    }

    public function resetSiswa(int $id): RedirectResponse|string
    {
        return $this->reset('siswa', $id);
    }

    private function reset(string $jenis, int $id): RedirectResponse|string
    {
        try {
            $target = $this->service()->target($jenis, $id);
            $sandiSementara = $this->service()->resetSandi($jenis, $id);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis))->with('galat', $e->getMessage());
        }

        $this->response->setHeader('Cache-Control', 'no-store, private');
        $this->response->setHeader('Pragma', 'no-cache');
        return view('admin/akun_reset_hasil', array_merge(
            $this->layout('Sandi sementara', $jenis),
            ['jenis' => $jenis, 'target' => $target, 'sandiSementara' => $sandiSementara]
        ));
    }
}
