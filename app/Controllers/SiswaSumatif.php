<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\MesinSumatifSiswaService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Semua endpoint ujian memakai filter auth:siswa dan CSRF global untuk POST. */
final class SiswaSumatif extends BaseController
{
    protected $helpers = ['url', 'form'];

    /**
     * User-Agent hanya indikator; tidak dapat membuktikan perangkat fisik.
     * Preflight browser juga menolak mobile sebelum meminta fullscreen.
     */
    private function desktop(): bool
    {
        $agent = (string) $this->request->getUserAgent()->getAgentString();
        return $agent !== ''
            && preg_match('/android|iphone|ipad|ipod|mobile|tablet|windows phone/i', $agent) !== 1;
    }

    private function engine(): MesinSumatifSiswaService
    {
        return new MesinSumatifSiswaService();
    }

    private function response(array $data, int $code = 200): ResponseInterface
    {
        return $this->response->setStatusCode($code)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setJSON(array_merge($data, ['csrf' => csrf_hash()]));
    }

    private function proses(callable $fn): ResponseInterface
    {
        if (!$this->desktop()) {
            return $this->response(['ok' => false,
                'pesan' => 'Sumatif hanya tersedia melalui desktop/laptop.'], 403);
        }
        try {
            return $this->response(['ok' => true, 'data' => $fn()]);
        } catch (DomainException $e) {
            return $this->response(['ok' => false, 'pesan' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            log_message('error', 'A4 ujian: {error}', ['error' => $e->getMessage()]);
            return $this->response(['ok' => false,
                'pesan' => 'Layanan ujian tidak dapat memproses permintaan. Coba status ulang.'], 500);
        }
    }

    public function halaman(int $tpId): ResponseInterface|string
    {
        if (!$this->desktop()) {
            return $this->response->setStatusCode(403)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Sumatif hanya tersedia di desktop/laptop yang mendukung fullscreen.');
        }
        try {
            $data = $this->engine()->ruang($tpId);
            $this->response->setHeader('Cache-Control', 'private, no-store')
                ->setHeader('Referrer-Policy', 'no-referrer');
            return view('siswa/sumatif_ujian', array_merge($data, [
                'judul' => 'Ujian Sumatif',
                'csrfName' => csrf_token(), 'csrfValue' => csrf_hash(),
            ]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', ['judul' => 'Ujian tidak tersedia']));
        } catch (Throwable $e) {
            log_message('error', 'A4 halaman ujian: {error}', ['error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Ujian tidak dapat dimuat. Silakan kembali ke petunjuk.');
        }
    }

    public function mulai(int $tpId): ResponseInterface
    {
        return $this->proses(fn () => $this->engine()->mulai($tpId,
            $this->request->getPost('fullscreen') === 'ya', $this->desktop()));
    }

    public function status(int $tpId, string $token): ResponseInterface
    {
        return $this->proses(fn () => $this->engine()->status($tpId, $token));
    }

    public function jawab(int $tpId, string $token): ResponseInterface
    {
        $id = $this->request->getPost('butir_id');
        $pilih = $this->request->getPost('pilihan');
        if (!is_string($id) || !ctype_digit($id) || !is_string($pilih)) {
            return $this->response(['ok'=>false, 'pesan'=>'Jawaban tidak sah.'],422);
        }
        return $this->proses(fn () => $this->engine()->jawab($tpId, $token, (int) $id, $pilih));
    }

    public function tindakan(int $tpId, string $token): ResponseInterface
    {
        $aksi = $this->request->getPost('aksi');
        if (!is_string($aksi) || !in_array($aksi,
            ['heartbeat','keluar_fullscreen','pulih','kirim'], true)) {
            return $this->response(['ok'=>false,'pesan'=>'Tindakan tidak tersedia.'],422);
        }
        return $this->proses(fn () => $this->engine()->tindakan($tpId, $token, $aksi,
            $this->request->getPost('fullscreen') === 'ya'));
    }

    public function media(int $tpId, string $token, string $jenis, string $id): ResponseInterface
    {
        if (!$this->desktop()) {
            return $this->response->setStatusCode(403)->setBody('Sumatif hanya untuk desktop/laptop.');
        }
        try {
            $file = $this->engine()->gambar($tpId, $token, $jenis, $id);
            return $this->response->setHeader('Content-Type', $file['mime'])
                ->setHeader('Cache-Control', 'private, no-store')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Referrer-Policy', 'no-referrer')
                ->setBody($file['data']);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)->setBody('Media tidak tersedia.');
        } catch (Throwable $e) {
            log_message('error', 'A4 media: {error}', ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Media gagal dimuat.');
        }
    }
}
