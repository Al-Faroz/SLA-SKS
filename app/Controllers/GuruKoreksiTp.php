<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\DetailTpService;
use App\Services\KoreksiTpService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class GuruKoreksiTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function keTp(int $tpId): string
    {
        return site_url('guru/tp/' . $tpId . '/koreksi');
    }

    private function keDraf(int $id): string
    {
        return site_url('guru/koreksi-tp/' . $id);
    }

    public function index(int $tpId): string|RedirectResponse
    {
        try {
            $svc = new KoreksiTpService();
            $ctx = (new DetailTpService())->lihat($tpId);
            if ($ctx['tp']['status'] !== 'siap') {
                return redirect()->to(site_url('guru/tp/' . $tpId))
                    ->with('galat', 'Koreksi hanya untuk TP Siap.');
            }
            $riwayat = $svc->tersedia() ? $svc->daftarGuru($tpId) : [];
            $akun = (new AuthService())->penggunaSaatIni();
            return view('guru/koreksi_tp_daftar', array_merge($ctx, [
                'judul' => 'Koreksi TP Siap', 'namaPengguna' => $akun['nama'], 'peranAktif' => 'Guru',
                'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
                'riwayat' => $riwayat, 'tersedia' => $svc->tersedia(),
            ]));
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/peta-atp'))->with('galat', $e->getMessage());
        }
    }

    public function buat(int $tpId): RedirectResponse
    {
        try {
            $id = (new KoreksiTpService())->buat(
                $tpId, (string) $this->request->getPost('kategori'),
                (string) $this->request->getPost('alasan')
            );
            return redirect()->to($this->keDraf($id))->with('sukses', 'Draf usulan siap disunting. TP Siap asli belum berubah.');
        } catch (DomainException $e) {
            return redirect()->to($this->keTp($tpId))->withInput()->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Koreksi TP Guru buat: {e}', ['e' => $e->getMessage()]);
            return redirect()->to($this->keTp($tpId))->with('galat', 'Draf koreksi belum dapat dibuat. Cek log server.');
        }
    }

    public function edit(int $id): string|RedirectResponse
    {
        try {
            $row = (new KoreksiTpService())->lihatGuru($id);
            $akun = (new AuthService())->penggunaSaatIni();
            return view('guru/koreksi_tp_edit', [
                'judul' => 'Draf Koreksi TP', 'namaPengguna' => $akun['nama'], 'peranAktif' => 'Guru',
                'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'), 'koreksi' => $row,
            ]);
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/peta-atp'))->with('galat', $e->getMessage());
        }
    }

    public function rincian(int $id): RedirectResponse
    {
        try {
            $data = $this->request->getPost();
            (new KoreksiTpService())->simpanRincian($id, is_array($data) ? $data : []);
            return redirect()->to($this->keDraf($id) . '#rincian')->with('sukses', 'Draf rincian TP dan Materi berhasil disimpan.');
        } catch (DomainException $e) {
            return redirect()->to($this->keDraf($id) . '#rincian')->withInput()->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Koreksi rincian TP: {e}', ['e' => $e->getMessage()]);
            return redirect()->to($this->keDraf($id))->with('galat', 'Draf belum dapat disimpan.');
        }
    }

    public function soal(int $id, int $soalId): RedirectResponse
    {
        try {
            $data = $this->request->getPost();
            (new KoreksiTpService())->simpanSoal($id, $soalId, is_array($data) ? $data : []);
            return redirect()->to($this->keDraf($id) . '#daftar-soal')->with('sukses', 'Butir soal telah disimpan sebagai usulan koreksi.');
        } catch (DomainException $e) {
            return redirect()->to($this->keDraf($id) . '#daftar-soal')->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Koreksi butir soal: {e}', ['e' => $e->getMessage()]);
            return redirect()->to($this->keDraf($id))->with('galat', 'Butir soal belum dapat disimpan.');
        }
    }

    public function kirim(int $id): RedirectResponse
    {
        try {
            (new KoreksiTpService())->kirim($id);
            return redirect()->to($this->keDraf($id))->with('sukses', 'Permohonan dikirim. Menunggu keputusan Admin.');
        } catch (DomainException $e) {
            return redirect()->to($this->keDraf($id))->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Ajukan koreksi TP: {e}', ['e' => $e->getMessage()]);
            return redirect()->to($this->keDraf($id))->with('galat', 'Pengajuan belum dapat dikirim.');
        }
    }
}
