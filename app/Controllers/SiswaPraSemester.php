<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MesinPraSemesterSiswaService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Mesin Pra-Semester utama: auth:siswa di semua rute, CSRF global untuk POST. */
final class SiswaPraSemester extends BaseController
{
    protected $helpers = ['url','form'];

    private function desktop(): bool
    {
        $agent = (string)$this->request->getUserAgent()->getAgentString();
        return $agent !== ''
            && preg_match('/android|iphone|ipad|ipod|mobile|tablet|windows phone/i',$agent) !== 1;
    }

    private function engine(): MesinPraSemesterSiswaService
    {
        return new MesinPraSemesterSiswaService();
    }

    private function json(array $data, int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)
            ->setHeader('Cache-Control','private, no-store')
            ->setHeader('Referrer-Policy','no-referrer')
            ->setJSON($data + ['csrf'=>csrf_hash()]);
    }

    private function proses(callable $call): ResponseInterface
    {
        if (!$this->desktop()) {
            return $this->json(['ok'=>false,
                'pesan'=>'Pra-Semester hanya tersedia melalui desktop/laptop.'],403);
        }
        try {
            return $this->json(['ok'=>true,'data'=>$call()]);
        } catch (DomainException $e) {
            return $this->json(['ok'=>false,'pesan'=>$e->getMessage()],409);
        } catch (Throwable $e) {
            log_message('error','Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return $this->json(['ok'=>false,
                'pesan'=>'Layanan Pra-Semester gagal memproses permintaan. Periksa koneksi.'],500);
        }
    }

    public function halaman(int $paketId): ResponseInterface|string
    {
        try {
            $data = $this->engine()->ruang($paketId);
            $this->response->setHeader('Cache-Control','private, no-store')
                ->setHeader('Referrer-Policy','no-referrer');
            return view('siswa/pra_semester_ujian',$data + [
                'judul'=>'Pra-Semester','csrfName'=>csrf_token(),'csrfValue'=>csrf_hash(),
                'desktop'=>$this->desktop(),
            ]);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)->setBody(
                view('publik/ditolak',['judul'=>'Pra-Semester tidak tersedia'])
            );
        } catch (Throwable $e) {
            log_message('error','Halaman Pra-Semester: {error}',['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Ujian belum dapat dimuat.');
        }
    }

    public function mulai(int $paketId): ResponseInterface
    {
        return $this->proses(fn () => $this->engine()->mulai(
            $paketId, $this->request->getPost('fullscreen') === 'ya', $this->desktop()
        ));
    }

    public function status(int $paketId, string $token): ResponseInterface
    {
        return $this->proses(fn () => $this->engine()->status($paketId,$token));
    }

    public function jawab(int $paketId, string $token): ResponseInterface
    {
        $id=$this->request->getPost('butir_id');
        $huruf=$this->request->getPost('pilihan');
        if (!is_string($id) || !ctype_digit($id) || !is_string($huruf)) {
            return $this->json(['ok'=>false,'pesan'=>'Jawaban tidak sah.'],422);
        }
        return $this->proses(fn () => $this->engine()->jawab(
            $paketId,$token,(int)$id,$huruf
        ));
    }

    public function tindakan(int $paketId, string $token): ResponseInterface
    {
        $aksi=$this->request->getPost('aksi');
        if (!is_string($aksi) || !in_array($aksi,
            ['heartbeat','keluar_fullscreen','pulih','kirim'],true)) {
            return $this->json(['ok'=>false,'pesan'=>'Tindakan tidak sah.'],422);
        }
        return $this->proses(fn () => $this->engine()->tindakan(
            $paketId,$token,$aksi,$this->request->getPost('fullscreen') === 'ya'
        ));
    }
}
