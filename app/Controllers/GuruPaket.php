<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PaketBabService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class GuruPaket extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function layout(string $judul): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        return ['judul' => $judul, 'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'paket')];
    }

    private function post(string $key): string
    {
        $v = $this->request->getPost($key);
        return is_string($v) ? trim($v) : '';
    }

    public function index(): string
    {
        return view('guru/paket_daftar', array_merge(
            $this->layout('Paket pembelajaran'), (new PaketBabService())->beranda()
        ));
    }

    public function buat(): RedirectResponse
    {
        try {
            $pasangan = explode(':', $this->post('konteks'));
            if (count($pasangan) !== 2 || !ctype_digit($pasangan[0]) || !ctype_digit($pasangan[1])) {
                throw new DomainException('Pilih pasangan Gen–Mapel yang benar-benar diampu.');
            }
            $id = (new PaketBabService())->buatPaket(
                (int) $pasangan[0], (int) $pasangan[1],
                (int) $this->post('semester'), (int) $this->post('tahun_ajaran_id'),
                (int) $this->post('kurikulum_id')
            );
            return redirect()->to(site_url('guru/paket/' . $id))
                ->with('sukses', 'Paket berhasil dibuat untuk Gen–Mapel terpilih.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/paket'))->withInput()->with('galat', $e->getMessage());
        }
    }

    public function detail(int $id): string|RedirectResponse
    {
        try {
            return view('guru/paket_detail', array_merge(
                $this->layout('Paket semester'), (new PaketBabService())->detailPaket($id)
            ));
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/paket'))->with('galat', $e->getMessage());
        }
    }

    public function buatBab(int $paketId): RedirectResponse
    {
        try {
            $id = (new PaketBabService())->buatBab($paketId, $this->post('judul'));
            return redirect()->to(site_url('guru/bab/' . $id))
                ->with('sukses', 'BAB Draf berhasil dibuat.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/paket/' . $paketId))
                ->withInput()->with('galat', $e->getMessage());
        }
    }
}
