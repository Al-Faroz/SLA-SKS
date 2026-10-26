<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class AuthFilter implements FilterInterface
{
    /**
     * Pesan akun sendiri saat peran aktif tidak memenuhi filter.
     * Tidak mengganti role atau memberi akses secara otomatis.
     *
     * @param array<string,mixed> $akun
     * @return array{pesan:string,tautanPath:string,labelTautan:string}
     */
    public static function informasiPeranDitolak(array $akun, string $wajib): array
    {
        $aktif = ucfirst((string) ($akun['peran_aktif'] ?? ''));
        $tujuan = ucfirst($wajib);
        $roles = $akun['daftar_peran'] ?? [];
        if (is_array($roles) && in_array($wajib, $roles, true)) {
            return [
                'pesan' => 'Peran aktif Anda sekarang ' . $aktif
                    . ', sedangkan halaman ini memerlukan peran ' . $tujuan
                    . '. Buka Profil & Peran, pilih ' . $tujuan
                    . ', lalu tekan Aktifkan peran. Penugasan Guru tidak perlu diubah.',
                'tautanPath' => 'profil',
                'labelTautan' => 'Buka Profil & Peran',
            ];
        }
        return [
            'pesan' => 'Halaman ini memerlukan peran ' . $tujuan
                . ', tetapi akun Anda belum memiliki peran tersebut. '
                . 'Periksa profil dan hubungi Admin jika perlu perubahan peran.',
            'tautanPath' => 'profil',
            'labelTautan' => 'Periksa Profil & Peran',
        ];
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        helper('url');
        if (! session()->get('auth_id')) {
            return redirect()->to(site_url('masuk'));
        }

        $pengguna = (new AuthService())->penggunaSaatIni();
        if ($pengguna === null) {
            return redirect()->to(site_url('masuk'));
        }

        $path = rtrim('/' . ltrim($request->getUri()->getPath(), '/'), '/');
        $bolehUbahSandi = str_ends_with($path, '/ganti-sandi');
        $bolehKeluar = str_ends_with($path, '/keluar');
        if ((int) $pengguna['wajib_ganti_sandi'] !== 0 && ! $bolehUbahSandi && ! $bolehKeluar) {
            return redirect()->to(site_url('ganti-sandi'));
        }

        $wajibPeran = $arguments[0] ?? null;
        if ($wajibPeran !== null && $pengguna['peran_aktif'] !== $wajibPeran) {
            $informasi = self::informasiPeranDitolak($pengguna, (string) $wajibPeran);
            return service('response')->setStatusCode(403)->setBody(
                view('publik/ditolak', ['judul' => 'Akses ditolak'] + $informasi)
            );
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'no-store, private');
    }
}
