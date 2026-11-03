<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\InformasiGlobalService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A13-2: Pengumuman global baca/login; Admin/Guru kelola, Admin FAQ. */
final class Informasi extends BaseController
{
    protected $helpers = ['url','form'];

    private function akun(): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        if ($akun === null || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Sesi Anda tidak berlaku.');
        }
        return $akun;
    }

    /** @return array<string,mixed> */
    private function tampilan(string $menu): array
    {
        $akun = $this->akun();
        return [
            'namaPengguna' => $akun['nama'],
            'peranAktif' => ucfirst((string) $akun['peran_aktif']),
            'peran' => (string) $akun['peran_aktif'],
            'tautanNavigasi' => NavigasiService::untuk((string) $akun['peran_aktif'], $menu),
        ];
    }

    private function halaman(int $default = 1): int
    {
        $nomor = $this->request->getGet('halaman');
        return is_scalar($nomor) && ctype_digit((string) $nomor)
            ? max(1, min(100000, (int) $nomor)) : $default;
    }

    public function pengumuman(): ResponseInterface
    {
        try {
            $daftar = (new InformasiGlobalService())->daftar('pengumuman', false, $this->halaman());
            return $this->response->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('informasi/pengumuman', $this->tampilan('pengumuman') + [
                    'judul' => 'Pengumuman',
                    'daftar' => $daftar,
                ]));
        } catch (DomainException $e) {
            return $this->ditolak();
        } catch (Throwable $e) {
            log_message('error','A13 Pengumuman: {jenis}',['jenis'=>get_class($e)]);
            return $this->gagal();
        }
    }

    public function adminPengumuman(): ResponseInterface { return $this->kelola('pengumuman'); }
    public function guruPengumuman(): ResponseInterface { return $this->kelola('pengumuman'); }
    public function adminFaq(): ResponseInterface { return $this->kelola('faq'); }

    private function kelola(string $jenis): ResponseInterface
    {
        try {
            $daftar = (new InformasiGlobalService())->daftar($jenis, true, $this->halaman());
            return $this->response->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('informasi/kelola', $this->tampilan($jenis === 'faq' ? 'faq_kelola' : 'pengumuman_kelola') + [
                    'judul' => $jenis === 'faq' ? 'Kelola FAQ' : 'Kelola Pengumuman',
                    'jenis' => $jenis,
                    'daftar' => $daftar,
                    'prefix' => $this->prefix($jenis),
                ]));
        } catch (DomainException $e) {
            return $this->ditolak();
        } catch (Throwable $e) {
            log_message('error','A13 kelola informasi: {jenis}',['jenis'=>get_class($e)]);
            return $this->gagal();
        }
    }

    public function adminPengumumanForm(?int $id = null): ResponseInterface { return $this->form('pengumuman',$id); }
    public function guruPengumumanForm(?int $id = null): ResponseInterface { return $this->form('pengumuman',$id); }
    public function adminFaqForm(?int $id = null): ResponseInterface { return $this->form('faq',$id); }

    private function prefix(string $jenis): string
    {
        $akun = $this->akun();
        return $jenis === 'faq' ? 'admin/faq' : $akun['peran_aktif'] . '/pengumuman';
    }

    private function form(string $jenis, ?int $id): ResponseInterface
    {
        try {
            $service = new InformasiGlobalService();
            $prefix = $this->prefix($jenis);
            $baris = $id !== null ? $service->rincianKelola($jenis, $id) : null;
            if ($id === null) {
                // Otorisasi pembuatan dikelola Service, bukan sekadar tombol UI.
                $service->daftar($jenis, true, 1, 1);
            }
            return $this->response->setHeader('Cache-Control', 'private, no-store')
                ->setBody(view('informasi/editor', $this->tampilan($jenis === 'faq' ? 'faq_kelola' : 'pengumuman_kelola') + [
                    'judul' => ($id === null ? 'Tambah ' : 'Sunting ')
                        . ($jenis === 'faq' ? 'FAQ' : 'Pengumuman'),
                    'jenis' => $jenis,
                    'baris' => $baris,
                    'prefix' => $prefix,
                ]));
        } catch (DomainException $e) {
            return $this->ditolak();
        } catch (Throwable $e) {
            log_message('error','A13 editor informasi: {jenis}',['jenis'=>get_class($e)]);
            return $this->gagal();
        }
    }

    public function adminSimpanPengumuman(?int $id = null): RedirectResponse { return $this->simpan('pengumuman',$id); }
    public function guruSimpanPengumuman(?int $id = null): RedirectResponse { return $this->simpan('pengumuman',$id); }
    public function adminSimpanFaq(?int $id = null): RedirectResponse { return $this->simpan('faq',$id); }

    private function simpan(string $jenis, ?int $id): RedirectResponse
    {
        $root = $jenis === 'faq' ? 'admin/faq' : $this->request->getUri()->getSegment(1) . '/pengumuman';
        $tujuan = site_url($root . ($id ? '/'. $id . '/sunting' : '/tambah'));
        try {
            $input = $this->request->getPost();
            $idBaru = (new InformasiGlobalService())->simpan($jenis, is_array($input) ? $input : [], $id);
            return redirect()->to(site_url($root))
                ->with('sukses', 'Informasi #' . $idBaru . ' berhasil disimpan beserta riwayat perubahannya.');
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->withInput()->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error','A13 simpan informasi: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to($tujuan)->withInput()
                ->with('galat', 'Informasi belum dapat disimpan. Periksa koneksi dan versi database.');
        }
    }

    public function adminArsipPengumuman(int $id): RedirectResponse { return $this->arsip('pengumuman',$id); }
    public function guruArsipPengumuman(int $id): RedirectResponse { return $this->arsip('pengumuman',$id); }
    public function adminArsipFaq(int $id): RedirectResponse { return $this->arsip('faq',$id); }

    private function arsip(string $jenis, int $id): RedirectResponse
    {
        $root = $jenis === 'faq' ? 'admin/faq' : $this->request->getUri()->getSegment(1) . '/pengumuman';
        try {
            $versi = filter_var($this->request->getPost('versi'), FILTER_VALIDATE_INT);
            (new InformasiGlobalService())->arsipkan($jenis, $id, $versi === false ? 0 : (int) $versi);
            return redirect()->to(site_url($root))->with('sukses', 'Informasi berhasil diarsipkan.');
        } catch (DomainException $e) {
            return redirect()->to(site_url($root))->with('galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error','A13 arsip informasi: {jenis}',['jenis'=>get_class($e)]);
            return redirect()->to(site_url($root))
                ->with('galat','Informasi belum dapat diarsipkan. Periksa koneksi database.');
        }
    }

    private function ditolak(): ResponseInterface
    {
        return $this->response->setStatusCode(403)
            ->setHeader('Cache-Control','private, no-store')
            ->setBody(view('publik/ditolak',[
                'judul'=>'Akses informasi ditolak',
                'pesan'=>'Peran aktif tidak mempunyai izin untuk halaman ini.',
            ]));
    }

    private function gagal(): ResponseInterface
    {
        return $this->response->setStatusCode(500)
            ->setHeader('Cache-Control','private, no-store')
            ->setBody('Halaman informasi belum tersedia. Pastikan SQL V4-0012 telah diimpor dengan benar.');
    }
}
