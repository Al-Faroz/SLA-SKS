<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CpReferensiService;
use App\Services\CpDaftarService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

final class AdminKurikulumCp extends BaseController
{
    protected $helpers = ['url','form'];

    private function service(): CpReferensiService
    {
        return new CpReferensiService();
    }

    private function layout(string $judul): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        return [
            'judul' => $judul,
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', 'cp'),
            'uiPrototipe' => true,
        ];
    }

    private function isian(string $key): string
    {
        $value = $this->request->getPost($key);
        return is_string($value) ? trim($value) : '';
    }

    /** @return array<string,string> */
    private function versiInput(): array
    {
        $hasil = [];
        foreach (['elemen','rumusan','otoritas','nomor_dokumen','sumber_url','tanggal_berlaku'] as $k) {
            $hasil[$k] = $this->isian($k);
        }
        return $hasil;
    }

    private function gagal(DomainException $e, string $tujuan): RedirectResponse
    {
        return redirect()->to(site_url($tujuan))->withInput()->with('galat', $e->getMessage());
    }

    public function index(): string
    {
        $filterGalat = null;
        try {
            $filter = CpDaftarService::filter((array) $this->request->getGet());
            $page = CpDaftarService::halamanGet($this->request->getGet('halaman'));
        } catch (DomainException $e) {
            $filter = CpDaftarService::filter([]);
            $page = 1;
            $filterGalat = $e->getMessage();
        }
        return view('admin/kurikulum_cp', array_merge(
            $this->layout('Referensi Kurikulum dan CP'),
            $this->service()->daftar(false),
            (new CpDaftarService())->halaman($filter, $page),
            ['cpFilterGalat' => $filterGalat]
        ));
    }

    public function eksporCp(): \CodeIgniter\HTTP\ResponseInterface
    {
        try {
            $filter = CpDaftarService::filter((array) $this->request->getGet());
            $bytes = (new CpDaftarService())->eksporXlsx($filter);
            return $this->response
                ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="sla-sks-daftar-cp.xlsx"')
                ->setHeader('Cache-Control', 'private, no-store')
                ->setBody($bytes);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/kurikulum-cp'))
                ->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Ekspor daftar CP gagal: {error}', ['error' => $e->getMessage()]);
            return redirect()->to(site_url('admin/kurikulum-cp'))
                ->with('galat', 'Ekspor CP belum dapat dibuat. Periksa ruang penyimpanan server.');
        }
    }

    public function simpanKurikulum(): RedirectResponse
    {
        try {
            $this->service()->buatKurikulum(
                $this->isian('nama'), $this->isian('versi'),
                $this->isian('sumber'), $this->isian('dasar_hukum')
            );
            return redirect()->to(site_url('admin/kurikulum-cp'))
                ->with('sukses', 'Kurikulum berhasil disimpan sebagai referensi resmi.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function simpanCp(): RedirectResponse
    {
        try {
            $id = $this->service()->buatCp(
                (int) $this->isian('kurikulum_id'),
                (int) $this->isian('mapel_id'),
                $this->isian('kode'),
                $this->versiInput()
            );
            return redirect()->to(site_url('admin/kurikulum-cp/' . $id))
                ->with('sukses', 'Draf CP berhasil dibuat. Periksa sumber lalu terbitkan jika sudah sesuai.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function detail(int $id): string|RedirectResponse
    {
        try {
            $data = $this->service()->detail($id);
            return view('admin/cp_detail', array_merge(
                $this->layout('Riwayat CP'), $data
            ));
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function versiBaru(int $cpId): string|RedirectResponse
    {
        try {
            $cp = $this->service()->detail($cpId);
            if (array_filter($cp['versi'], static fn (array $v): bool => $v['status'] === 'draf') !== []) {
                return redirect()->to(site_url('admin/kurikulum-cp/' . $cpId))
                    ->with('galat', 'Draf sebelumnya belum diterbitkan. Selesaikan draf tersebut dahulu.');
            }
            $asal = $cp['versi'][0] ?? null;
            return view('admin/cp_form_versi', array_merge($this->layout('Draf versi CP baru'), [
                'cp' => $cp['cp'], 'dataVersi' => $asal, 'edit' => false,
                'aksi' => site_url('admin/kurikulum-cp/' . $cpId . '/versi-baru'),
            ]));
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function simpanVersiBaru(int $cpId): RedirectResponse
    {
        try {
            $this->service()->versiBaru($cpId, $this->versiInput());
            return redirect()->to(site_url('admin/kurikulum-cp/' . $cpId))
                ->with('sukses', 'Draf versi CP baru disimpan tanpa mengubah versi resmi sebelumnya.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp/' . $cpId . '/versi-baru');
        }
    }

    public function editDraf(int $id): string|RedirectResponse
    {
        try {
            $version = $this->service()->detailVersi($id);
            if ($version['status'] !== 'draf') {
                return redirect()->to(site_url('admin/kurikulum-cp/' . (int) $version['cp_id']))
                    ->with('galat', 'Versi CP yang telah terbit tidak dapat diedit.');
            }
            $cp = $this->service()->detail((int) $version['cp_id']);
            return view('admin/cp_form_versi', array_merge(
                $this->layout('Perbaiki draf CP'), [
                    'cp' => $cp['cp'], 'dataVersi' => $version, 'edit' => true,
                    'aksi' => site_url('admin/kurikulum-cp/versi/' . $id . '/edit'),
                ]
            ));
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function simpanDraf(int $id): RedirectResponse
    {
        try {
            $versi = $this->service()->detailVersi($id);
            $this->service()->ubahDraf($id, $this->versiInput());
            return redirect()->to(site_url('admin/kurikulum-cp/' . (int) $versi['cp_id']))
                ->with('sukses', 'Draf CP berhasil diperbaiki.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp/versi/' . $id . '/edit');
        }
    }

    public function konfirmasiTerbit(int $id): string|RedirectResponse
    {
        try {
            $versi = $this->service()->detailVersi($id);
            if ($versi['status'] !== 'draf') {
                return redirect()->to(site_url('admin/kurikulum-cp/' . (int) $versi['cp_id']))
                    ->with('galat', 'Hanya draf yang boleh diterbitkan.');
            }
            $data = $this->service()->detail((int) $versi['cp_id']);
            return view('admin/cp_terbit_konfirmasi', array_merge(
                $this->layout('Konfirmasi penerbitan CP'), [
                    'cp' => $data['cp'], 'versi' => $versi,
                ]
            ));
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function terbitkan(int $id): RedirectResponse
    {
        try {
            $versi = $this->service()->detailVersi($id);
            $this->service()->terbitkan($id);
            return redirect()->to(site_url('admin/kurikulum-cp/' . (int) $versi['cp_id']))
                ->with('sukses', 'CP versi resmi diterbitkan; versi sebelumnya tetap tercatat dalam riwayat.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        } catch (Throwable $e) {
            log_message('error', 'Penerbitan CP gagal (' . get_class($e) . ').');
            return redirect()->to(site_url('admin/kurikulum-cp'))
                ->with('galat', 'Penerbitan CP gagal disimpan. Periksa log server tanpa membagikan data sensitif.');
        }
    }
}
