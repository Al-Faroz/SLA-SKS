<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SertifikatPenerbitanService;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/** Verifikasi publik QR: whitelist identitas dan status; tanpa NISN/TTD/PDF. */
final class VerifikasiSertifikat extends BaseController
{
    protected $helpers=['url'];

    public function index(string $token): ResponseInterface
    {
        // Rate-limit per IP agar token panjang tak menjadi endpoint pencarian massal.
        $ip=(string)$this->request->getIPAddress();
        $key='sertifikat-verifikasi-'.hash('sha256',$ip);
        if (!service('throttler')->check($key,30,60)) {
            return $this->response->setStatusCode(429)
                ->setHeader('Cache-Control','no-store')
                ->setHeader('Retry-After','60')
                ->setBody('Terlalu banyak permintaan verifikasi. Coba lagi nanti.');
        }
        try {
            $data=(new SertifikatPenerbitanService())->verifikasi($token);
            if ($data===null) {
                return $this->response->setStatusCode(404)
                    ->setHeader('Cache-Control','no-store')
                    ->setBody('Kode verifikasi sertifikat tidak ditemukan.');
            }
            return $this->response->setHeader('Cache-Control','no-store')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setBody(view('publik/verifikasi_sertifikat',[
                    'judul'=>'Verifikasi Sertifikat SLA-SKS',
                    'hasil'=>$data,
                ]));
        } catch (Throwable $e) {
            log_message('error','Verifikasi sertifikat: {error}',
                ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(503)
                ->setHeader('Cache-Control','no-store')
                ->setBody('Verifikasi sertifikat belum dapat diproses.');
        }
    }
}
