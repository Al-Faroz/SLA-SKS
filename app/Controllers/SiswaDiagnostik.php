<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PetunjukDiagnostikSiswaService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** S05: petunjuk dan preflight browser, tanpa membuat percobaan ujian. */
final class SiswaDiagnostik extends BaseController
{
    protected $helpers = ['url'];

    public function petunjuk(int $tpId): ResponseInterface|string
    {
        try {
            $auth = new AuthService();
            if (!$auth->izinkan('siswa')) {
                throw new DomainException('Akses khusus Siswa.');
            }
            $akun = $auth->penggunaSaatIni();
            $data = (new PetunjukDiagnostikSiswaService())->periksa($tpId);
            $this->response->setHeader('Cache-Control', 'private, no-store');
            return view('siswa/diagnostik_petunjuk', array_merge($data, [
                'judul' => 'Petunjuk Tes Diagnostik',
                'namaPengguna' => $akun['nama'], 'peranAktif' => 'Siswa',
                'tautanNavigasi' => NavigasiService::untuk('siswa', 'pembelajaran'),
            ]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', ['judul' => 'Petunjuk Diagnostik tidak tersedia']));
        } catch (Throwable $e) {
            log_message('error', 'S05 petunjuk diagnostik: {error}', ['error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody('Pemeriksaan Diagnostik tidak dapat dimuat. Silakan kembali ke rincian TP.');
        }
    }
}
