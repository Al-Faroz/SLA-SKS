<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AtpUnduhanService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/** GET download only; auth:guru diterapkan pada route, G1 dicek kembali oleh Service. */
final class GuruAtpUnduh extends BaseController
{
    protected $helpers = ['url'];

    public function unduh(string $paketId): DownloadResponse|RedirectResponse
    {
        if (! ctype_digit($paketId) || strlen($paketId) > 15 || (int) $paketId < 1) {
            return redirect()->to(site_url('guru/peta-atp/impor'))
                ->with('galat', 'Paket ATP tidak valid.');
        }
        try {
            $hasil = (new AtpUnduhanService())->unduh((int) $paketId);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/peta-atp/impor'))->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            // Simpan pesan akar galat di log privat untuk diagnosis; jangan
            // tampilkan lokasi server, query, atau detail internal kepada Guru.
            log_message('error',
                'Unduh ATP v2.5 gagal: {kelas} — {pesan} pada {file}:{baris}', [
                    'kelas' => get_class($e),
                    'pesan' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'baris' => $e->getLine(),
                ]);
            return redirect()->to(site_url('guru/peta-atp/impor'))
                ->with('galat', 'Template ATP gagal dibuat. Silakan laporkan kepada Admin untuk pemeriksaan log.');
        }

        return $this->response->download($hasil['nama'], $hasil['isi'], true)
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff');
    }
}
