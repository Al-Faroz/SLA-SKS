<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\ReferensiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

final class AdminReferensi extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function masukan(string $nama): string
    {
        $nilai = $this->request->getPost($nama);

        return is_string($nilai) ? $nilai : '';
    }

    private function service(): ReferensiService
    {
        return new ReferensiService();
    }

    /** @return array<string,mixed> */
    private function layout(string $judul, string $aktif): array
    {
        $akun = (new AuthService())->penggunaSaatIni();

        return [
            'judul' => $judul,
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', $aktif),
        ];
    }

    public function referensi(): string
    {
        return view('admin/referensi', array_merge(
            $this->layout('Gen dan Tahun Ajaran', 'gen'),
            $this->service()->daftarAngkatanDanTahun()
        ));
    }

    public function buatGen(): RedirectResponse
    {
        try {
            $this->service()->buatAngkatan(
                $this->masukan('kode'),
                $this->masukan('nama'),
                (int) $this->request->getPost('semester_mulai')
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/referensi'))->withInput()
                ->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/referensi'))->with('sukses', 'Gen berhasil disimpan.');
    }

    public function buatTahunAjaran(): RedirectResponse
    {
        try {
            $this->service()->buatTahunAjaran($this->masukan('tahun_ajaran'));
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/referensi'))->withInput()
                ->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/referensi'))->with('sukses', 'Tahun ajaran berhasil disimpan.');
    }

    public function mapel(): string|RedirectResponse
    {
        $pilihan = $this->request->getGet('gen');
        if ($pilihan !== null && (! is_string($pilihan) || ! ctype_digit($pilihan) || (int) $pilihan < 1)) {
            return redirect()->to(site_url('admin/mapel-guru'))->with('galat', 'Pilih Gen yang tersedia.');
        }
        try {
            $data = $this->service()->daftarMapel($pilihan === null ? null : (int) $pilihan);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/mapel-guru'))->with('galat', $e->getMessage());
        }
        return view('admin/mapel_guru', array_merge(
            $this->layout('Mapel dan Guru per Gen', 'mapel'),
            $data
        ));
    }

    public function buatMapel(): RedirectResponse
    {
        try {
            $this->service()->buatMapel(
                $this->masukan('kode'),
                $this->masukan('nama')
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/mapel-guru'))->withInput()
                ->with('galat', $e->getMessage());
        }

        return redirect()->to(site_url('admin/mapel-guru'))->with('sukses', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function tetapkanGuru(int $mapelId, int $angkatanId): RedirectResponse
    {
        $masukan = $this->masukan('guru_id');
        $guruId = $masukan === '' ? null : (ctype_digit($masukan) ? (int) $masukan : -1);
        $kembali = site_url('admin/mapel-guru') . '?gen=' . $angkatanId;

        try {
            $this->service()->tetapkanGuru($mapelId, $angkatanId, $guruId, $this->masukan('alasan'));
        } catch (DomainException $e) {
            return redirect()->to($kembali)->with('galat', $e->getMessage());
        }

        return redirect()->to($kembali)->with('sukses',
            'Guru pengampu pada Gen terpilih diperbarui dan riwayat dicatat.');
    }
}
