<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AkunXlsxService;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class AdminXlsx extends BaseController
{
    protected $helpers = ['url'];

    public function templatePengelola(): DownloadResponse|RedirectResponse
    {
        return $this->unduh('pengelola', true);
    }

    public function templateSiswa(): DownloadResponse|RedirectResponse
    {
        return $this->unduh('siswa', true);
    }

    public function eksporPengelola(): DownloadResponse|RedirectResponse
    {
        return $this->unduh('pengelola', false);
    }

    public function eksporSiswa(): DownloadResponse|RedirectResponse
    {
        return $this->unduh('siswa', false);
    }

    private function unduh(string $jenis, bool $template): DownloadResponse|RedirectResponse
    {
        $cari = $this->request->getGet('cari');
        $status = $this->request->getGet('status');
        $gen = $this->request->getGet('gen');
        $genFilter = null;

        if ($gen !== null && $gen !== '') {
            if (! is_string($gen) || ! ctype_digit($gen)) {
                return redirect()->to(site_url('admin/' . $jenis))
                    ->with('galat', 'Filter Gen tidak valid.');
            }
            $genFilter = (int) $gen;
        }

        try {
            $file = (new AkunXlsxService())->siapkanUnduhan(
                $jenis, $template,
                is_string($cari) ? $cari : '',
                is_string($status) ? $status : '',
                $template ? null : $genFilter
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis))
                ->with('galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Ekspor XLSX ' . $jenis . ' gagal: ' . $e->getMessage());
            return redirect()->to(site_url('admin/' . $jenis))
                ->with('galat', 'File XLSX tidak dapat dibuat. Periksa izin folder writable atau hubungi pengelola sistem.');
        }

        return $this->response->download($file['nama'], $file['isi'], true)
            ->setHeader('Cache-Control', 'no-store, private')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff');
    }
}
