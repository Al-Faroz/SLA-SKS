<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\ImporAkunService;
use App\Services\NavigasiService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\DownloadResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use DomainException;
use Throwable;

final class AdminImporAkun extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function layout(string $jenis, string $judul): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        return [
            'judul' => $judul,
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Admin',
            'tautanNavigasi' => NavigasiService::untuk('admin', $jenis),
            'uiPrototipe' => true,
            'jenis' => $jenis,
        ];
    }

    public function halamanPengelola(): string
    {
        return $this->halaman('pengelola');
    }

    public function halamanSiswa(): string
    {
        return $this->halaman('siswa');
    }

    private function halaman(string $jenis): string
    {
        return view('admin/akun_impor', $this->layout($jenis, 'Impor ' . ucfirst($jenis)));
    }

    public function unggahPengelola(): RedirectResponse
    {
        return $this->unggah('pengelola');
    }

    public function unggahSiswa(): RedirectResponse
    {
        return $this->unggah('siswa');
    }

    private function unggah(string $jenis): RedirectResponse
    {
        $file = $this->request->getFile('berkas');
        if ($file === null) {
            return redirect()->to(site_url('admin/' . $jenis . '/impor'))
                ->with('galat', 'Pilih berkas XLSX dahulu.');
        }

        try {
            $hasil = (new ImporAkunService())->pratinjau($jenis, $file);
            return redirect()->to(site_url('admin/' . $jenis . '/impor/' . $hasil['batch']));
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis . '/impor'))->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Unggah impor akun ' . $jenis . ' gagal (' . get_class($e) . ').');
            return redirect()->to(site_url('admin/' . $jenis . '/impor'))
                ->with('galat', 'Impor tidak dapat diproses. Periksa format XLSX, koneksi database dan ruang penyimpanan.');
        } finally {
            $path = $file->getTempName();
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function lihatPengelola(string $id): string|RedirectResponse
    {
        return $this->lihat('pengelola', $id);
    }

    public function lihatSiswa(string $id): string|RedirectResponse
    {
        return $this->lihat('siswa', $id);
    }

    private function lihat(string $jenis, string $id): string|RedirectResponse
    {
        try {
            $data = (new ImporAkunService())->laporan($jenis, $id);
            return view('admin/akun_impor_pratinjau', array_merge(
                $this->layout($jenis, 'Pratinjau impor ' . ucfirst($jenis)), $data
            ));
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis . '/impor'))
                ->with('galat', $e->getMessage());
        }
    }

    public function konfirmasiPengelola(string $id): RedirectResponse
    {
        return $this->konfirmasi('pengelola', $id);
    }

    public function konfirmasiSiswa(string $id): RedirectResponse
    {
        return $this->konfirmasi('siswa', $id);
    }

    public function galatPengelola(string $id): DownloadResponse|RedirectResponse
    {
        return $this->galat('pengelola', $id);
    }

    public function galatSiswa(string $id): DownloadResponse|RedirectResponse
    {
        return $this->galat('siswa', $id);
    }

    private function galat(string $jenis, string $id): DownloadResponse|RedirectResponse
    {
        try {
            $laporan = (new ImporAkunService())->laporan($jenis, $id);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis . '/impor'))
                ->with('galat', $e->getMessage());
        }
        $buku = new Spreadsheet();
        $sheet = $buku->getActiveSheet();
        $sheet->setTitle('Galat impor');
        $headers = ['baris', 'aksi', 'username_atau_nisn', 'nama', 'keterangan'];
        foreach ($headers as $i => $label) {
            $sheet->setCellValueExplicit([$i + 1, 1], $label, DataType::TYPE_STRING);
        }
        $nomor = 2;
        foreach ($laporan['baris'] as $row) {
            if ($row['tindakan'] !== 'ditolak') {
                continue;
            }
            $values = [
                $row['nomor_baris'], $row['tindakan'], $row['username'],
                $row['nama'], $row['galat'] ?? '',
            ];
            foreach ($values as $i => $v) {
                $sheet->setCellValueExplicit([$i + 1, $nomor],
                    \App\Services\AkunXlsxService::amanUntukXlsx((string) $v), DataType::TYPE_STRING);
            }
            $nomor++;
        }
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(75);
        $temp = tempnam(WRITEPATH, 'galat_akun_');
        if ($temp === false) {
            $buku->disconnectWorksheets();
            return redirect()->to(site_url('admin/' . $jenis . '/impor/' . $id))
                ->with('galat', 'Laporan galat tidak dapat dibuat.');
        }
        try {
            (new Xlsx($buku))->save($temp);
            $isi = file_get_contents($temp);
            if ($isi === false) {
                throw new \RuntimeException('Laporan galat tidak dapat dibaca.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Laporan galat impor gagal (' . get_class($e) . ').');
            return redirect()->to(site_url('admin/' . $jenis . '/impor/' . $id))
                ->with('galat', 'Laporan galat tidak dapat dibuat.');
        } finally {
            $buku->disconnectWorksheets();
            @unlink($temp);
        }
        return $this->response->download('galat-impor-' . $jenis . '.xlsx', $isi, true)
            ->setHeader('Cache-Control', 'no-store, private')
            ->setHeader('X-Content-Type-Options', 'nosniff');
    }

    private function konfirmasi(string $jenis, string $id): RedirectResponse
    {
        try {
            $hasil = (new ImporAkunService())->konfirmasi($jenis, $id);
            $pesan = $hasil['ulang'] ? 'Impor ini sudah diterapkan sebelumnya; tidak ada perubahan tambahan.'
                : 'Impor berhasil: ' . $hasil['baru'] . ' dibuat, '
                    . $hasil['ubah'] . ' diperbarui, ' . $hasil['tetap'] . ' tidak berubah.';
            return redirect()->to(site_url('admin/' . $jenis . '/impor/' . $id))
                ->with('sukses', $pesan);
        } catch (DomainException $e) {
            return redirect()->to(site_url('admin/' . $jenis . '/impor/' . $id))
                ->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Konfirmasi impor ' . $jenis . ' gagal (' . get_class($e) . ').');
            return redirect()->to(site_url('admin/' . $jenis . '/impor/' . $id))
                ->with('galat', 'Konfirmasi gagal. Tidak ada perubahan yang boleh diterapkan sebagian. Periksa log dan ulangi pratinjau.');
        }
    }
}
