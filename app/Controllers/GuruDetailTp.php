<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RedirectResponse;
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

    public function simpan(int $id): RedirectResponse
    {
        // Route auth:guru serta CSRF global; Service tetap memeriksa G1.
        $judul = $this->request->getPost('judul');
        $rumusan = $this->request->getPost('rumusan');
        $indikator = $this->request->getPost('indikator');
        $versi = $this->request->getPost('struktur_versi');

        if (! is_string($judul) || ! is_string($rumusan)
            || ! is_string($indikator) || ! is_string($versi)
            || ! ctype_digit($versi) || strlen($versi) > 10
            || (float) $versi > PHP_INT_MAX) {
            return redirect()->to(site_url('guru/tp/' . $id))
                ->with('galat', 'Data formulir tidak valid.');
        }

        try {
            (new DetailTpService())->simpanDraf($id, $judul, $rumusan, $indikator, (int) $versi);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $id))
                ->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('guru/tp/' . $id))
            ->with('sukses', 'Draf TP berhasil disimpan.');
    }

}
