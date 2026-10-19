<?php

declare(strict_types=1);

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class AuthFilter implements FilterInterface
{
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
            return service('response')->setStatusCode(403)->setBody(view('publik/ditolak', [
                'judul' => 'Akses ditolak',
            ]));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Cache-Control', 'no-store, private');
    }
}
