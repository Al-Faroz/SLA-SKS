<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CpReferensiService;
use App\Services\CpDaftarService;
use App\Services\CpOperasionalService;
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
        foreach (['elemen','rumusan'] as $k) {
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
            ['cpFilterGalat' => $filterGalat,
             'cpOperasional' => (new CpOperasionalService())->daftar()]
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
            $id = $this->service()->simpanLangsung(null,
                (int) $this->isian('kurikulum_id'),
                (int) $this->isian('mapel_id'),
                $this->isian('kode'),
                $this->versiInput()
            );
            return redirect()->to(site_url('admin/kurikulum-cp/' . $id))
                ->with('sukses', 'CP berhasil disimpan.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function sunting(int $id): string|RedirectResponse
    {
        try {
            $data = $this->service()->detail($id);
            $opsi = $this->service()->daftar(false);
            $versi = array_values(array_filter($data['versi'],
                static fn (array $baris): bool => $baris['status'] === 'aktif'));
            if ($versi === []) {
                $versi = array_values(array_filter($data['versi'],
                    static fn (array $baris): bool => $baris['status'] === 'draf'));
            }
            return view('admin/cp_sunting', array_merge(
                $this->layout('Sunting CP'), $opsi,
                ['cp' => $data['cp'], 'dataVersi' => $versi[0] ?? []]
            ));
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp');
        }
    }

    public function simpanSunting(int $id): RedirectResponse
    {
        try {
            $cpId = $this->service()->simpanLangsung($id,
                (int) $this->isian('kurikulum_id'), (int) $this->isian('mapel_id'),
                $this->isian('kode'), $this->versiInput()
            );
            return redirect()->to(site_url('admin/kurikulum-cp/' . $cpId))
                ->with('sukses', 'CP berhasil diperbarui. Riwayat tersimpan.');
        } catch (DomainException $e) {
            return $this->gagal($e, 'admin/kurikulum-cp/' . $id . '/sunting');
        } catch (Throwable $e) {
            log_message('error', 'Sunting CP gagal: ' . get_class($e));
            return redirect()->to(site_url('admin/kurikulum-cp/' . $id . '/sunting'))
                ->with('galat', 'CP belum tersimpan. Periksa log server.');
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

}
