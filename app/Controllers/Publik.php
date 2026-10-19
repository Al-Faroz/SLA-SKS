<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman publik fondasi K0.
 *
 * Belum ada endpoint POST login atau akses panel sebelum AuthService dan SQL K1 siap.
 */
final class Publik extends BaseController
{
    protected $helpers = ['url'];

    public function beranda(): RedirectResponse
    {
        return redirect()->to(site_url('masuk'));
    }

    public function masuk(): string
    {
        return view('publik/masuk', [
            'judul' => 'Akses SLA-SKS',
        ]);
    }

    public function kebijakanPrivasi(): ResponseInterface
    {
        // Konten kebijakan resmi hanya boleh diterbitkan oleh Admin setelah disahkan sekolah.
        // Jangan mengganti kekosongan ini dengan teks kebijakan buatan pengembang.
        return $this->response
            ->setStatusCode(503)
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(view('publik/kebijakan_privasi', [
                'judul' => 'Kebijakan Privasi',
            ]));
    }

    public function tidakDitemukan(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(404)
            ->setBody(view('publik/tidak_ditemukan', [
                'judul' => 'Halaman tidak ditemukan',
            ]));
    }
}
