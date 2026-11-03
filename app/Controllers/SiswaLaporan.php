<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\LaporanAkademikService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Laporan akademik milik Siswa, GET baca-saja tanpa siswa_id URL. */
final class SiswaLaporan extends BaseController
{
    protected $helpers = ['url'];

    private function data(): array
    {
        $a = (new AuthService())->penggunaSaatIni();
        return [
            'judul'=>'Laporan Akademik Siswa',
            'namaPengguna'=>$a['nama'] ?? '',
            'peranAktif'=>'Siswa',
            'tautanNavigasi'=>NavigasiService::untuk('siswa','laporan'),
        ];
    }

    public function index(): ResponseInterface|string
    {
        try {
            $daftar=(new LaporanAkademikService())->daftarLaporanSendiri();
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('siswa/laporan_daftar',$this->data()+['daftar'=>$daftar]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)->setBody(
                view('publik/ditolak',['judul'=>'Laporan tidak tersedia'])
            );
        } catch (Throwable $e) {
            log_message('error','A10 daftar laporan siswa: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Laporan belum dapat dimuat. Coba lagi nanti.');
        }
    }

    public function detail(int $paketId): ResponseInterface|string
    {
        try {
            $laporan=(new LaporanAkademikService())->laporanSendiri($paketId);
            return $this->response->setHeader('Cache-Control','private, no-store')
                ->setBody(view('laporan/detail',$this->data()+[
                    'laporan'=>$laporan,'kembali'=>'siswa/laporan',
                    'pratinjau'=>false,
                ]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)->setBody(
                view('publik/ditolak',['judul'=>'Laporan tidak tersedia'])
            );
        } catch (Throwable $e) {
            log_message('error','A10 detail laporan siswa: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Laporan belum dapat dimuat. Coba lagi nanti.');
        }
    }
}
