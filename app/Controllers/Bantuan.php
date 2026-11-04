<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\InformasiGlobalService;
use App\Services\NavigasiService;
use App\Services\PanduanBantuanService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/**
 * A13-1 — GET Bantuan hanya untuk akun aktif yang sudah mengganti sandi.
 * Tidak mengambil data siswa, tidak menulis database, tidak mengirim pesan.
 */
final class Bantuan extends BaseController
{
    protected $helpers = ['url'];

    public function index(): ResponseInterface
    {
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            if ($akun === null
                || ($akun['status'] ?? '') !== 'aktif'
                || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
                throw new DomainException('Akun belum dapat mengakses Bantuan.');
            }

            $peran = (string) ($akun['peran_aktif'] ?? '');
            $panduan = PanduanBantuanService::untukPeran($peran);
            $nomorFaq = $this->request->getGet('halaman_faq');
            $halamanFaq = is_scalar($nomorFaq) && ctype_digit((string) $nomorFaq)
                ? min(100000, max(1, (int) $nomorFaq)) : 1;
            $faqResmi = (new InformasiGlobalService())->daftar('faq', false, $halamanFaq, 10);

            return $this->response
                ->setHeader('Cache-Control', 'private, no-store')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody(view('bantuan/index', [
                    'judul' => 'Pusat Bantuan',
                    'namaPengguna' => (string) $akun['nama'],
                    'peranAktif' => $panduan['nama_peran'],
                    'tautanNavigasi' => NavigasiService::untuk($peran, 'bantuan'),
                    'panduan' => $panduan,
                    'faqResmi' => $faqResmi,
                ]));
        } catch (DomainException $e) {
            return $this->response
                ->setStatusCode(403)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', [
                    'judul' => 'Bantuan tidak tersedia',
                    'pesan' => 'Silakan masuk dengan akun aktif dan peran yang sah.',
                ]));
        } catch (Throwable $e) {
            log_message('error', 'A13-1 halaman bantuan: {jenis}',
                ['jenis' => get_class($e)]);
            return $this->response
                ->setStatusCode(500)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Pusat Bantuan belum dapat dimuat. Silakan coba kembali.');
        }
    }
}
