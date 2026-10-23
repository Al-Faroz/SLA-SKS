<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\MateriDrafService;
use App\Services\NavigasiService;
use App\Services\VideoMateriService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;

final class GuruMateri extends BaseController
{
    protected $helpers = ['url', 'form'];

    /** @return array<string,mixed>|null */
    private function formulir(): ?array
    {
        $nama = ['judul', 'jenis', 'petunjuk_resume', 'petunjuk_hots', 'min_waktu_menit', 'url_youtube'];
        $hasil = [];
        foreach ($nama as $kunci) {
            $nilai = $this->request->getPost($kunci);
            if (! is_string($nilai)) {
                return null;
            }
            $hasil[$kunci] = $nilai;
        }
        return $hasil;
    }

    private function versi(): ?int
    {
        $nilai = $this->request->getPost('struktur_versi');
        return is_string($nilai) && ctype_digit($nilai) && strlen($nilai) <= 10
            && (float) $nilai <= PHP_INT_MAX ? (int) $nilai : null;
    }

    private function kembali(int $tpId, string $pesan, bool $sukses = false): RedirectResponse
    {
        return redirect()->to(site_url('guru/tp/' . $tpId))
            ->with($sukses ? 'sukses' : 'galat', $pesan);
    }

    public function tambah(int $tpId): RedirectResponse
    {
        $data = $this->formulir();
        $versi = $this->versi();
        if ($data === null || $versi === null) {
            return $this->kembali($tpId, 'Data formulir Materi tidak valid.');
        }
        try {
            (new MateriDrafService())->tambah($tpId, $versi, $data);
            return $this->kembali($tpId, 'Materi draf berhasil ditambahkan dan belum diaktifkan.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        }
    }

    public function edit(int $tpId, int $materiId): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
            if ($rincian['tp']['status'] !== 'draf') {
                throw new DomainException('TP sudah Siap.');
            }
            $materi = null;
            foreach ($rincian['materi'] as $baris) {
                if ((int) $baris['id'] === $materiId) {
                    $materi = $baris;
                    break;
                }
            }
            if ($materi === null) {
                throw new DomainException('Materi tidak ditemukan.');
            }
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)
                ->setBody(view('publik/ditolak', ['judul' => 'Akses Materi ditolak']));
        }

        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/materi_edit', [
            'judul' => 'Sunting Materi Draf',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'tp' => $rincian['tp'],
            'materi' => $materi,
        ]);
    }

    public function pratinjau(int $tpId, int $materiId): ResponseInterface|string
    {
        try {
            $rincian = (new DetailTpService())->lihat($tpId);
            $materi = null;
            foreach ($rincian['materi'] as $baris) {
                if ((int) $baris['id'] === $materiId) {
                    $materi = $baris;
                    break;
                }
            }
            if ($materi === null || $materi['jenis'] !== 'youtube'
                || ! is_string($materi['url_youtube']) || $materi['url_youtube'] === '') {
                throw new DomainException('Video Materi tidak tersedia.');
            }
            $urlEmbed = VideoMateriService::urlEmbed($materi['url_youtube']);
            $urlAsli = VideoMateriService::urlStandar($materi['url_youtube']);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(403)
                ->setHeader('Cache-Control', 'no-store, private')
                ->setBody(view('publik/ditolak', ['judul' => 'Pratinjau tidak tersedia']));
        }

        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/materi_pratinjau', [
            'judul' => 'Pratinjau Media Materi',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'tp' => $rincian['tp'],
            'materi' => $materi,
            'urlEmbed' => $urlEmbed,
            'urlAsli' => $urlAsli,
        ]);
    }

    public function simpan(int $tpId, int $materiId): RedirectResponse
    {
        $data = $this->formulir();
        $versi = $this->versi();
        if ($data === null || $versi === null) {
            return $this->kembali($tpId, 'Data formulir Materi tidak valid.');
        }
        try {
            (new MateriDrafService())->simpan($tpId, $materiId, $versi, $data);
            return $this->kembali($tpId, 'Materi draf berhasil diperbarui.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        }
    }

    public function urut(int $tpId, int $materiId): RedirectResponse
    {
        $versi = $this->versi();
        $arah = $this->request->getPost('arah');
        if ($versi === null || ! is_string($arah)) {
            return $this->kembali($tpId, 'Permintaan urutan tidak valid.');
        }
        try {
            (new MateriDrafService())->geser($tpId, $materiId, $versi, $arah);
            return $this->kembali($tpId, 'Urutan Materi berhasil diperbarui.', true);
        } catch (DomainException $e) {
            return $this->kembali($tpId, $e->getMessage());
        }
    }
}
