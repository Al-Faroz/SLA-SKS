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
        // Fragment memastikan pesan pasca-redirect tampil di area form,
        // bukan di atas layar yang sudah tidak terlihat.
        $kembali = site_url('guru/peta-atp/impor') . '?pilihan=' . $indeks
            . '&paket_id=' . (ctype_digit($paketId) ? (int) $paketId : 0)
            . '#atp-upload-area';

        if (! ctype_digit($paketId) || strlen($paketId) > 15 || (int) $paketId < 1) {
            return redirect()->to($kembali)->with('galat', 'Paket ATP tidak valid.');
        }
        // Penanda aman untuk membedakan form tidak terkirim/CSRF (tanpa
        // log ini) dari error yang terjadi sesudah Controller menerima POST.
        log_message('info', 'POST pratinjau ATP diterima; paket={paket}', [
            'paket' => (int) $paketId,
        ]);
        $file = $this->request->getFile('atp_excel');
        if ($file === null || ! $file->isValid()) {
            return redirect()->to($kembali)->with(
                'galat', 'Unggah Excel gagal. Pilih file ATP V25-3 (.xlsx, maksimal 5 MB).'
                . ' Pastikan ukuran unggahan PHP pada XAMPP mengizinkan file tersebut.'
            );
        }

        $tahap = 'berkas';
        try {
            AtpValidasiUnggahService::periksa(
                $file->getTempName(),
                $file->getClientName(),
                (int) $file->getSize()
            );
            $tahap = 'aturan-akademik';
            $hasil = (new AtpImporPratinjauService())->pratinjau((int) $paketId, $file->getTempName());
            $hasil['stage_token'] = null;
            if ($hasil['boleh_konfirmasi']) {
                $tahap = 'berkas-sementara';
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
            log_message('notice', 'Pratinjau ATP ditolak pada tahap {tahap}; paket={paket}', [
                'tahap' => $tahap, 'paket' => (int) $paketId,
            ]);
            return redirect()->to($kembali)->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Pratinjau ATP gagal pada tahap {tahap}; paket={paket}; kelas={kelas}', [
                'tahap' => $tahap, 'paket' => (int) $paketId,
                'kelas' => get_class($e),
            ]);
            return redirect()->to($kembali)->with('galat',
                'Pemeriksaan Excel terhenti pada tahap ' . $tahap
                . '. Tidak ada BAB/TP yang disimpan. '
                . 'Periksa file dan coba unggah kembali; bila berulang, periksa log CI4.');
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
