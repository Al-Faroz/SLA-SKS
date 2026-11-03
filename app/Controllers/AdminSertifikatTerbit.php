<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SertifikatPenerbitanService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** POST penerbitan/revisi + GET PDF privat hanya untuk Admin. */
final class AdminSertifikatTerbit extends BaseController
{
    protected $helpers=['url'];

    private static function kembali(int $paketId): string
    {
        return site_url('admin/sertifikat').'?'.http_build_query(['paket'=>$paketId]);
    }

    public function terbit(int $paketId,int $siswaId): RedirectResponse
    {
        try {
            (new SertifikatPenerbitanService())->terbit($siswaId,$paketId);
            return redirect()->to(self::kembali($paketId))
                ->with('sukses_sertifikat','Sertifikat berhasil diterbitkan/tersedia. PDF dan QR tersimpan privat.');
        } catch (DomainException $e) {
            return redirect()->to(self::kembali($paketId))
                ->with('galat_sertifikat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Terbit sertifikat gagal: {error}',['error'=>$e->getMessage()]);
            return redirect()->to(self::kembali($paketId))
                ->with('galat_sertifikat','Sertifikat belum dapat diterbitkan. Periksa SQL, berkas TTD, log.');
        }
    }

    public function revisi(int $paketId,int $siswaId): RedirectResponse
    {
        $alasan=$this->request->getPost('alasan');
        try {
            if (!is_string($alasan)) {
                throw new DomainException('Alasan revisi wajib diisi.');
            }
            (new SertifikatPenerbitanService())->revisi($siswaId,$paketId,$alasan);
            return redirect()->to(self::kembali($paketId))
                ->with('sukses_sertifikat','Sertifikat pengganti terbit. PDF dan QR lama tetap menjadi arsip.');
        } catch (DomainException $e) {
            return redirect()->to(self::kembali($paketId))
                ->with('galat_sertifikat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','Revisi sertifikat gagal: {error}',['error'=>$e->getMessage()]);
            return redirect()->to(self::kembali($paketId))
                ->with('galat_sertifikat','Revisi gagal. Sertifikat lama tetap berlaku.');
        }
    }

    public function unduh(int $id): ResponseInterface
    {
        try {
            $file=(new SertifikatPenerbitanService())->pdfBerizin($id);
            $bytes=@file_get_contents($file['path']);
            if (!is_string($bytes)) throw new DomainException('Berkas PDF tidak tersedia.');
            return $this->response
                ->setHeader('Content-Type','application/pdf')
                ->setHeader('Content-Disposition','attachment; filename="sertifikat-'.$id.'.pdf"')
                ->setHeader('X-Content-Type-Options','nosniff')
                ->setHeader('Cache-Control','private, no-store, max-age=0')
                ->setBody($bytes);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control','private, no-store')
                ->setBody('PDF sertifikat tidak ditemukan atau tidak diizinkan.');
        }
    }
}
