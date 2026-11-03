<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\KontakResmiMadrasahService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A13-3: kontak resmi dari pengaturan Admin; GET baca saja, login-only. */
final class HubungiAdmin extends BaseController
{
    protected $helpers = ['url'];

    public function index(): ResponseInterface
    {
        try {
            $kontak = (new KontakResmiMadrasahService())->ambil();

            return $this->response
                ->setHeader('Cache-Control', 'private, no-store')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody(view('bantuan/hubungi_admin', [
                    'judul' => 'Hubungi Admin',
                    'namaPengguna' => $kontak['nama_pengguna'],
                    'peranAktif' => ucfirst($kontak['peran']),
                    'tautanNavigasi' => NavigasiService::untuk($kontak['peran'], 'hubungi_admin'),
                    'kontak' => $kontak,
                ]));
        } catch (DomainException $e) {
            return $this->response
                ->setStatusCode(403)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', [
                    'judul' => 'Kontak tidak tersedia',
                    'pesan' => 'Silakan masuk dengan akun aktif dan peran yang sah.',
                ]));
        } catch (Throwable $e) {
            log_message('error', 'A13-3 kontak resmi tidak dapat dimuat: {jenis}',
                ['jenis' => get_class($e)]);
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Kontak resmi belum dapat ditampilkan. Silakan coba kembali.');
        }
    }
}
