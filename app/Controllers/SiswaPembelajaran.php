<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\KatalogPembelajaranSiswaService;
use App\Services\MateriSiswaService;
use App\Services\NavigasiService;
use App\Services\ProgresTpSiswaService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** Ruang siswa S01–S03, baca saja; tidak ada penyimpanan progres atau ujian palsu. */
final class SiswaPembelajaran extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function data(): array
    {
        $auth = new AuthService();
        if (!$auth->izinkan('siswa')) {
            throw new DomainException('Masuk sebagai Siswa untuk membuka pembelajaran.');
        }
        $akun = $auth->penggunaSaatIni();
        return ['judul' => 'Pembelajaran Siswa',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Siswa',
            'tautanNavigasi' => NavigasiService::untuk('siswa', 'pembelajaran')];
    }

    private function render(string $view, array $data): ResponseInterface|string
    {
        return view($view, array_merge($this->data(), $data));
    }

    private function halaman(callable $pemuat, string $view): ResponseInterface|string
    {
        try {
            return $this->render($view, $pemuat());
        } catch (DomainException $e) {
            // Jangan membedakan ID TP paket Gen lain dan ID yang tak ada.
            log_message('notice', 'Akses katalog siswa ditolak: {pesan}', ['pesan' => $e->getMessage()]);
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('publik/ditolak', ['judul' => 'Pembelajaran tidak tersedia']));
        } catch (Throwable $e) {
            log_message('error', 'Katalog siswa gagal: {pesan}', ['pesan' => $e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody('Pembelajaran belum dapat dimuat. Silakan kembali ke Beranda Siswa.');
        }
    }

    public function mapel(): ResponseInterface|string
    {
        return $this->halaman(static fn (): array =>
            (new KatalogPembelajaranSiswaService())->daftarMapel(), 'siswa/mapel');
    }

    public function semester(int $mapelId): ResponseInterface|string
    {
        return $this->halaman(static fn (): array =>
            (new KatalogPembelajaranSiswaService())->daftarSemester($mapelId), 'siswa/semester');
    }

    public function paket(int $paketId): ResponseInterface|string
    {
        return $this->halaman(static fn (): array =>
            (new KatalogPembelajaranSiswaService())->daftarTp($paketId), 'siswa/daftar_tp');
    }

    public function tp(int $tpId): ResponseInterface|string
    {
        return $this->halaman(static function () use ($tpId): array {
            $data = (new KatalogPembelajaranSiswaService())->rincianTp($tpId);
            $data['progres'] = (new ProgresTpSiswaService())->status($tpId);
            if ($data['progres'] !== null
                && in_array($data['progres']['status'], ['belajar', 'sumatif', 'tuntas'], true)) {
                // Data berasal dari snapshot terpin, bukan versi live milik Guru.
                $data['materi'] = (new MateriSiswaService())->daftar($tpId)['materi'];
            }
            return $data;
        }, 'siswa/rincian_tp');
    }

    /** Hanya aksi POST nyata: mengikat versi TP, tidak memulai percobaan ujian. */
    public function mulaiTp(int $tpId): RedirectResponse
    {
        try {
            $progres = (new ProgresTpSiswaService())->mulai($tpId);
            return redirect()->to(site_url('siswa/tp/' . $tpId))
                ->with('sukses_mulai_tp', 'TP sudah diaktifkan. Versi konten #' .
                    (int) $progres['versi_konten_tp_id'] . ' tersimpan untuk akun Anda.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('siswa/tp/' . $tpId))
                ->with('galat_mulai_tp', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Aktivasi TP siswa gagal: {error}', ['error' => $e->getMessage()]);
            return redirect()->to(site_url('siswa/tp/' . $tpId))
                ->with('galat_mulai_tp', 'Aktivasi TP belum berhasil. Periksa bahwa SQL progres TP sudah diimpor.');
        }
    }
}
