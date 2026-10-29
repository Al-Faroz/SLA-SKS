<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AtpImporPratinjauService;
use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\AtpStagingService;
use App\Services\AtpValidasiUnggahService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/** Unggah XLSX ATP → validasi → tampilkan pratinjau; tidak menyimpan BAB/TP. */
final class GuruAtpPratinjau extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function tampil(string $paketId): string|RedirectResponse
    {
        $pilihan = $this->request->getPost('pilihan');
        $indeks = is_string($pilihan) && ctype_digit($pilihan) && strlen($pilihan) <= 5
            ? (int) $pilihan : 0;
        $kembali = site_url('guru/peta-atp/impor') . '?pilihan=' . $indeks
            . '&paket_id=' . (ctype_digit($paketId) ? (int) $paketId : 0);

        if (! ctype_digit($paketId) || strlen($paketId) > 15 || (int) $paketId < 1) {
            return redirect()->to($kembali)->with('galat', 'Paket ATP tidak valid.');
        }
        $file = $this->request->getFile('atp_excel');
        if ($file === null || ! $file->isValid()) {
            return redirect()->to($kembali)->with(
                'galat', 'Unggah Excel gagal. Pilih file ATP V25-2 (.xlsx, maksimal 5 MB).'
                . ' Pastikan ukuran unggahan PHP pada XAMPP mengizinkan file tersebut.'
            );
        }

        try {
            AtpValidasiUnggahService::periksa(
                $file->getTempName(),
                $file->getClientName(),
                (int) $file->getSize()
            );
            $hasil = (new AtpImporPratinjauService())->pratinjau((int) $paketId, $file->getTempName());
            $hasil['stage_token'] = null;
            if ($hasil['boleh_konfirmasi']) {
                $akunStaging = (new AuthService())->penggunaSaatIni();
                $stage = (new AtpStagingService())->buat(
                    $file->getTempName(),
                    (int) $akunStaging['id'],
                    (int) $paketId,
                    session_id(),
                    (string) $hasil['fingerprint']
                );
                $hasil['stage_token'] = $stage['token'];
                $hasil['stage_kedaluwarsa'] = $stage['kedaluwarsa'];
            }
            $hasil['paket_id'] = (int) $paketId;
            $hasil['pilihan'] = $indeks;
            $hasil['semester_asal'] = 0;
        } catch (DomainException $e) {
            return redirect()->to($kembali)->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Pratinjau impor ATP gagal: {kelas}', [
                'kelas' => get_class($e),
            ]);
            return redirect()->to($kembali)->with('galat',
                'Excel belum dapat dipratinjau. Periksa isian dan unggah kembali'
                . ' template ATP V25-2 yang baru diunduh.');
        }

        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/pratinjau_atp', [
            'judul' => 'Pratinjau impor ATP',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'kembali' => $kembali,
            'hasil' => $hasil,
        ]);
    }
}
