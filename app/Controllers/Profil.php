<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\ProfilService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

final class Profil extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function service(): ProfilService
    {
        return new ProfilService();
    }

    public function simpanKontak(): RedirectResponse
    {
        $tel = $this->request->getPost('telepon_pribadi');
        try {
            $this->service()->ubahKontak(is_string($tel) ? $tel : '');
            return redirect()->to(site_url('profil'))->with('sukses', 'Kontak pribadi berhasil disimpan.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('profil'))->with('galat', $e->getMessage());
        }
    }

    public function unggahFoto(): RedirectResponse
    {
        $file = $this->request->getFile('foto');
        if ($file === null) {
            return redirect()->to(site_url('profil'))->with('galat', 'Pilih foto JPG/PNG dahulu.');
        }
        try {
            $this->service()->unggahFoto($file);
            return redirect()->to(site_url('profil'))->with('sukses',
                'Foto berhasil diunggah secara privat dan menunggu persetujuan Admin.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('profil'))->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Unggah foto profil gagal (' . get_class($e) . ').');
            return redirect()->to(site_url('profil'))->with('galat',
                'Foto tidak dapat diproses. Periksa format JPG/PNG, ukuran, dan izin folder writable.');
        }
    }

    public function mediaFoto(int $id): ResponseInterface
    {
        try {
            $file = $this->service()->mediaFoto($id);
            $isi = file_get_contents($file['path']);
            if ($isi === false) {
                throw new DomainException('Berkas tidak dapat dibaca.');
            }
            return $this->response->setHeader('Content-Type', $file['mime'])
                ->setHeader('Content-Disposition', 'inline')
                ->setHeader('Cache-Control', 'no-store, private')
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($isi);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(404)
                ->setHeader('Cache-Control', 'no-store, private')
                ->setBody('Foto tidak tersedia.');
        }
    }

    public function tinjauFoto(): string
    {
        $akun = (new AuthService())->penggunaSaatIni();
        $data = $this->service()->daftarFotoMenunggu();
        return view('admin/foto_profil_tinjau', [
            'judul' => 'Persetujuan foto profil',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', 'foto_profil'),
            'uiPrototipe' => true,
            'daftarFoto' => $data,
        ]);
    }

    public function setujuiFoto(int $id): RedirectResponse
    {
        try {
            $this->service()->setujuiFoto($id);
            return redirect()->to(site_url('admin/foto-profil'))->with('sukses',
                'Foto profesional berhasil disetujui.');
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/foto-profil'))->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Persetujuan foto gagal (' . get_class($e) . ').');
            return redirect()->to(site_url('admin/foto-profil'))->with('galat',
                'Persetujuan tidak berhasil disimpan. Coba ulangi setelah memeriksa database.');
        }
    }
}
