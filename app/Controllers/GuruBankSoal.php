<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\NavigasiService;
use App\Services\RingkasanBankSoalService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class GuruBankSoal extends BaseController
{
    public function index(int $tpId): ResponseInterface|string
    {
        try {
            // Penugasan aktif G1 dan konteks Gen–Mapel diverifikasi oleh Service.
            $rincian = (new DetailTpService())->lihat($tpId);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)->setBody(
                view('publik/ditolak', ['judul' => 'Akses bank soal ditolak'])
            );
        }

        $akun = (new AuthService())->penggunaSaatIni();
        $ringkasan = (new RingkasanBankSoalService())->lihat($tpId);

        return view('guru/bank_soal', array_merge($rincian, [
            'judul' => 'Bank Soal TP',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'ringkasan_bank' => $ringkasan,
        ]));
    }
}
