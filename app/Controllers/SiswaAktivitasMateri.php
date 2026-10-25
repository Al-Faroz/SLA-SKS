<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProgresMediaMateriSiswaService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** S08B: status GET baca-saja; detak POST CSRF dan filter auth:siswa. */
final class SiswaAktivitasMateri extends BaseController
{
    private function response(array $body, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Referrer-Policy', 'no-referrer')
            ->setJSON(array_merge($body, ['csrf' => csrf_hash()]));
    }

    private function jalankan(callable $callback): ResponseInterface
    {
        try {
            return $this->response(['ok' => true, 'data' => $callback()]);
        } catch (DomainException $e) {
            return $this->response(['ok' => false, 'pesan' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            log_message('error', 'S08B aktivitas media: {error}', ['error' => $e->getMessage()]);
            return $this->response(['ok' => false,
                'pesan' => 'Aktivitas belum tersimpan. Periksa koneksi dan coba lagi.'], 500);
        }
    }

    public function status(int $tpId, int $materiId): ResponseInterface
    {
        return $this->jalankan(static fn (): array =>
            (new ProgresMediaMateriSiswaService())->status($tpId, $materiId));
    }

    public function detak(int $tpId, int $materiId): ResponseInterface
    {
        $aktif = $this->request->getPost('aktif') === 'ya';
        $rawPos = $this->request->getPost('posisi');
        $rawDur = $this->request->getPost('durasi');
        $rawHalaman = $this->request->getPost('halaman');
        $rawJumlah = $this->request->getPost('jumlah_halaman');
        $parse = static fn ($v): ?int => is_string($v) && preg_match('/^\\d{1,5}$/D', $v)
            ? (int) $v : null;
        if (!$aktif || ($rawPos !== '' && $parse($rawPos) === null)
            || ($rawDur !== '' && $parse($rawDur) === null)
            || ($rawHalaman !== null && $parse($rawHalaman) === null)
            || ($rawJumlah !== null && $parse($rawJumlah) === null)) {
            return $this->response(['ok' => false,
                'pesan' => 'Sinyal aktivitas media tidak sah.'], 422);
        }
        return $this->jalankan(fn (): array =>
            (new ProgresMediaMateriSiswaService())->detak(
                $tpId, $materiId, $aktif, $parse($rawPos), $parse($rawDur),
                $parse($rawHalaman), $parse($rawJumlah),
                $this->request->getPost('reset') === 'ya'));
    }
}
