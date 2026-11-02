<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KeputusanUjianBatalService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use RuntimeException;
use Throwable;

/** A5 revisi: permintaan akses ulang tes mandiri, filter role dan Service G1. */
final class GuruUjianBatal extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function blokir(): ResponseInterface
    {
        return $this->response->setStatusCode(403)->setBody(
            view('publik/ditolak', ['judul'=>'Akses keputusan ujian ditolak',
                'tautanPath'=>'guru/peta-atp',
                'labelTautan'=>'Kembali ke Peta ATP'])
        );
    }

    public function index(?int $id = null): ResponseInterface|string
    {
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
                || ($akun['status'] ?? '') !== 'aktif'
                || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
                return $this->blokir();
            }
            $svc = new KeputusanUjianBatalService();
            $antrean = $svc->antrean();
            $pilih = $id === null ? null : $svc->lihat($id);
            return view('guru/ujian_batal', [
                'judul'=>'Permintaan Akses Ulang',
                'namaPengguna'=>$akun['nama'],
                'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','ujian_batal'),
                'antrean'=>$antrean,
                'pilih'=>$pilih,
                'pilihanKeputusan'=>$pilih === null ? []
                    : KeputusanUjianBatalService::pilihan((string) $pilih['jenis']),
            ]);
        } catch (DomainException $e) {
            return $this->blokir();
        } catch (RuntimeException $e) {
            log_message('error','A5 skema keputusan ujian: {error}',
                ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(503)->setBody(
                view('publik/galat_sistem', [
                    'judul'=>'Keputusan ujian belum siap',
                    'pesan'=>'Administrator perlu mengimpor SQL V4-0008 sebelum menggunakan menu ini.',
                ]));
        } catch (Throwable $e) {
            log_message('error','A5 daftar akses ulang: {error}', ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody(view('publik/galat_sistem', ['judul'=>'Permintaan akses ulang tidak dapat dimuat']));
        }
    }

    public function simpan(int $id): RedirectResponse
    {
        $tujuan = site_url('guru/akses-ulang/' . $id);
        $aksi = $this->request->getPost('keputusan');
        if ($id < 1 || !is_string($aksi)) {
            return redirect()->to($tujuan)->withInput()
                ->with('ujian_gagal','Izin akses ulang tidak valid.');
        }
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            if (!$akun || ($akun['peran_aktif'] ?? '') !== 'guru'
                || ($akun['status'] ?? '') !== 'aktif'
                || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
                throw new DomainException('Hanya Guru aktif boleh memutuskan ujian.');
            }
            (new KeputusanUjianBatalService())->putuskan($id, $aksi);
            return redirect()->to(site_url('guru/akses-ulang'))
                ->with('ujian_sukses','Izin akses ulang sudah disimpan dan dicatat dalam audit.');
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->withInput()
                ->with('ujian_gagal',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','A5 keputusan ujian gagal: {error}', ['error'=>$e->getMessage()]);
            return redirect()->to($tujuan)
                ->with('ujian_gagal','Keputusan belum tersimpan. Periksa koneksi atau SQL V4-0008.');
        }
    }
}
