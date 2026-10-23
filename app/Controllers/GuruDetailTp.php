<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class GuruDetailTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function lihat(int $id): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($id);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak', ['judul' => 'Akses TP ditolak'])
            );
        }

        $akun = (new AuthService())->penggunaSaatIni();

        return view('guru/detail_tp', array_merge($rincian, [
            'judul' => 'Rincian TP',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
        ]));
    }
}
