<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KesiapanTpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class GuruKesiapanTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function lihat(int $tpId): ResponseInterface|string
    {
        try {
            $hasil = (new KesiapanTpService())->periksa($tpId);
            $akun = (new AuthService())->penggunaSaatIni();
            return view('guru/kesiapan_tp', array_merge($hasil, [
                'judul' => 'Kesiapan TP Siap',
                'namaPengguna' => $akun['nama'],
                'peranAktif' => 'Guru',
                'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            ]));
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', ['judul' => 'TP tidak tersedia untuk penugasan Anda']));
        } catch (Throwable $e) {
            log_message('error', 'Checklist final TP: {error}', ['error' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody('Pemeriksaan TP belum dapat dimuat. Periksa koneksi database.');
        }
    }

    public function siapkan(int $tpId): RedirectResponse
    {
        $versi = $this->request->getPost('struktur_versi');
        $fingerprint = $this->request->getPost('sidik_jari');
        if (!is_string($versi) || !ctype_digit($versi)
            || strlen($versi) > 10 || (float) $versi > PHP_INT_MAX
            || !is_string($fingerprint) || !preg_match('/^[a-f0-9]{64}$/D', $fingerprint)
            || $this->request->getPost('tinjau_akademik') !== 'ya') {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/kesiapan'))
                ->with('galat_siapan', 'Periksa seluruh komponen dan centang konfirmasi akademik.');
        }
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            (new KesiapanTpService())->terbitkan($tpId, (int) $akun['id'], (int) $versi, true, $fingerprint);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/kesiapan'))
                ->with('sukses_siapan', 'TP berhasil disiapkan dan dikunci. Versi konten telah disimpan.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/tp/' . $tpId . '/kesiapan'))
                ->with('galat_siapan', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Publikasi TP gagal: {error}', ['error' => $e->getMessage()]);
            return redirect()->to(site_url('guru/tp/' . $tpId . '/kesiapan'))
                ->with('galat_siapan', 'TP belum disiapkan. Tidak ada publikasi parsial; hubungi Admin bila masalah berulang.');
        }
    }
}
