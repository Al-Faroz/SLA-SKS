<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class Akun extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function servis(): AuthService
    {
        return new AuthService();
    }

    public function masuk(): RedirectResponse|string
    {
        if (session()->get('auth_id')) {
            $akun = $this->servis()->penggunaSaatIni();
            if ($akun) {
                return redirect()->to(site_url((int) $akun['wajib_ganti_sandi'] !== 0
                    ? 'ganti-sandi' : $akun['peran_aktif']));
            }
        }

        $this->response->setHeader('Cache-Control', 'no-store, private');

        return view('akun/masuk', ['judul' => 'Masuk']);
    }

    public function prosesMasuk(): RedirectResponse
    {
        $username = (string) $this->request->getPost('username');
        $sandi = (string) $this->request->getPost('sandi');

        if (! $this->servis()->masuk($username, $sandi, $this->request->getIPAddress())) {
            return redirect()->to(site_url('masuk'))->with('galat',
                'Username atau sandi tidak sesuai, atau akses sedang dibatasi. Silakan coba lagi setelah lima menit jika terkunci.');
        }

        $akun = $this->servis()->penggunaSaatIni();

        return redirect()->to(site_url($akun && (int) $akun['wajib_ganti_sandi'] !== 0
            ? 'ganti-sandi' : ($akun['peran_aktif'] ?? 'masuk')));
    }

    public function keluar(): RedirectResponse
    {
        $this->servis()->keluar();

        return redirect()->to(site_url('masuk'))->with('sukses', 'Anda sudah keluar dengan aman.');
    }

    public function gantiSandi(): string
    {
        $akun = $this->servis()->penggunaSaatIni();

        return view('akun/ganti_sandi', [
            'judul' => 'Ganti sandi',
            'wajib' => $akun && (int) $akun['wajib_ganti_sandi'] !== 0,
        ]);
    }

    public function prosesGantiSandi(): RedirectResponse
    {
        $lama = (string) $this->request->getPost('sandi_lama');
        $baru = (string) $this->request->getPost('sandi_baru');
        $konfirmasi = (string) $this->request->getPost('sandi_konfirmasi');

        if ($baru !== $konfirmasi) {
            return redirect()->to(site_url('ganti-sandi'))->with('galat',
                'Konfirmasi sandi baru tidak sama.');
        }

        try {
            $this->servis()->gantiSandi($lama, $baru);
        } catch (DomainException $e) {
            return redirect()->to(site_url('ganti-sandi'))->with('galat', $e->getMessage());
        }

        $akun = $this->servis()->penggunaSaatIni();

        return redirect()->to(site_url($akun['peran_aktif'] ?? 'masuk'))
            ->with('sukses', 'Sandi berhasil diganti.');
    }

    public function profil(): string
    {
        $akun = $this->servis()->penggunaSaatIni();

        return view('akun/profil', [
            'judul' => 'Profil',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => ucfirst($akun['peran_aktif']),
            'peranAktifKode' => $akun['peran_aktif'],
            'daftarPeran' => $akun['daftar_peran'],
            'username' => $akun['username'],
            'tautanNavigasi' => $this->menu($akun['peran_aktif'], 'profil'),
        ]);
    }

    public function prosesGantiPeran(): RedirectResponse
    {
        try {
            $this->servis()->gantiPeran((string) $this->request->getPost('peran'));
        } catch (DomainException $e) {
            return redirect()->to(site_url('profil'))->with('galat', $e->getMessage());
        }
        $akun = $this->servis()->penggunaSaatIni();

        return redirect()->to(site_url($akun['peran_aktif']))->with('sukses', 'Peran aktif berhasil diganti.');
    }


    public function berandaSiswa(): ResponseInterface|string
    {
        return $this->beranda('siswa');
    }

    public function berandaGuru(): ResponseInterface|string
    {
        return $this->beranda('guru');
    }

    public function berandaAdmin(): ResponseInterface|string
    {
        return $this->beranda('admin');
    }

    public function berandaPimpinan(): ResponseInterface|string
    {
        return $this->beranda('pimpinan');
    }

    public function beranda(string $peran): ResponseInterface|string
    {
        // Verifikasi kedua tetap terjadi di Service, bukan hanya filter URL.
        if (! $this->servis()->izinkan($peran)) {
            return $this->ditolak();
        }
        $akun = $this->servis()->penggunaSaatIni();

        return view('akun/beranda', [
            'judul' => 'Beranda ' . ucfirst($peran),
            'namaPengguna' => $akun['nama'],
            'peranAktif' => ucfirst($peran),
            'tautanNavigasi' => $this->menu($peran, $peran),
            'peran' => $peran,
            'daftarPeran' => $akun['daftar_peran'],
        ]);
    }

    public function pengguna(): ResponseInterface|string
    {
        if (! $this->servis()->izinkan('admin')) {
            return $this->ditolak();
        }
        $akun = $this->servis()->penggunaSaatIni();

        return view('akun/daftar_pengguna', [
            'judul' => 'Pengguna',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => $this->menu('admin', 'pengguna'),
            'pengguna' => $this->servis()->daftarPengguna(),
        ]);
    }

    public function prosesBuatPengguna(): RedirectResponse|ResponseInterface
    {
        if (! $this->servis()->izinkan('admin')) {
            return $this->ditolak();
        }

        $peran = $this->request->getPost('peran');
        try {
            $this->servis()->buatPengguna(
                (string) $this->request->getPost('username'),
                (string) $this->request->getPost('nama'),
                (string) $this->request->getPost('sandi'),
                is_array($peran) ? array_values(array_map('strval', $peran)) : []
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/pengguna'))->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/pengguna'))->with('sukses',
            'Akun berhasil dibuat. Sampaikan sandi awal kepada pengguna secara privat.');
    }

    private function ditolak(): ResponseInterface
    {
        return $this->response->setStatusCode(403)
            ->setBody(view('publik/ditolak', ['judul' => 'Akses ditolak']));
    }

    /** @return list<array{label:string,path:string,aktif:bool}> */
    private function menu(string $peran, string $aktif): array
    {
        $menu = [
            ['label' => 'Beranda', 'path' => $peran, 'aktif' => $aktif === $peran],
        ];
        if ($peran === 'admin') {
            $menu[] = ['label' => 'Pengguna', 'path' => 'admin/pengguna', 'aktif' => $aktif === 'pengguna'];
        }
        $menu[] = ['label' => 'Profil & peran', 'path' => 'profil', 'aktif' => $aktif === 'profil'];

        return $menu;
    }
}
