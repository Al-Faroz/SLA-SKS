<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CheckpointMateriSiswaService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** S08 checkpoint 180 detik, satu endpoint start dan simpan kumulatif. */
final class SiswaCheckpointMateri extends BaseController
{
    private function jawab(array $body, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setJSON($body + ['csrf' => csrf_hash()]);
    }

    public function mulai(int $tpId, int $materiId): ResponseInterface
    {
        try {
            $raw = $this->request->getPost();
            $parse = static function (mixed $value): ?int {
                return is_string($value) && preg_match('/^[0-9]{1,5}$/D',$value)
                    ? (int) $value : null;
            };
            $value = (new CheckpointMateriSiswaService())->mulai(
                $tpId, $materiId,
                $parse($raw['posisi'] ?? null),
                $parse($raw['durasi'] ?? null),
                $parse($raw['halaman'] ?? null)
            );
            return $this->jawab(['ok'=>true,'data'=>$value]);
        } catch (DomainException $e) {
            return $this->jawab(['ok'=>false,'pesan'=>$e->getMessage()],409);
        } catch (Throwable $e) {
            log_message('error','S08 checkpoint mulai: {message}',
                ['message'=>$e->getMessage()]);
            return $this->jawab(['ok'=>false,
                'pesan'=>'Sesi media belum dapat dimulai. Coba lagi.'],500);
        }
    }

    public function kirim(int $tpId, int $materiId): ResponseInterface
    {
        try {
            $raw = $this->request->getPost('laporan');
            if (!is_string($raw) || strlen($raw)>4096) {
                return $this->jawab(['ok'=>false,'pesan'=>'Laporan terlalu panjang.'],422);
            }
            $data = json_decode($raw,true,64,JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return $this->jawab(['ok'=>false,'pesan'=>'Laporan tidak sah.'],422);
            }
            $result = (new CheckpointMateriSiswaService())->kirim(
                $tpId, $materiId, $data);
            return $this->jawab(['ok'=>true,'data'=>$result]);
        } catch (DomainException $e) {
            return $this->jawab(['ok'=>false,'pesan'=>$e->getMessage()],409);
        } catch (Throwable $e) {
            log_message('error','S08 checkpoint kirim: {message}',
                ['message'=>$e->getMessage()]);
            return $this->jawab(['ok'=>false,
                'pesan'=>'Checkpoint belum berhasil disimpan; data lokal tetap ada.'],500);
        }
    }
}
