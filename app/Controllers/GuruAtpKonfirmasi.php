<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AtpImporKonfirmasiService;
use App\Services\AtpStagingService;
use App\Services\AuthService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/** POST satu kali, selalu revalidasi isi file dan hak G1 sebelum commit. */
final class GuruAtpKonfirmasi extends BaseController
{
    protected $helpers = ['url'];

    public function simpan(string $paketId): RedirectResponse
    {
        $pilihan = $this->request->getPost('pilihan');
        $indeks = is_string($pilihan) && ctype_digit($pilihan) && strlen($pilihan) < 6
            ? (int) $pilihan : 0;
        $semester = $this->request->getPost('semester');
        $s = is_string($semester) && ctype_digit($semester)
            && (int) $semester >= 1 && (int) $semester <= 6 ? (int) $semester : 0;
        $kembali = $s > 0
            ? site_url('guru/peta-atp/semester/' . $s) . '?pilihan=' . $indeks
            : site_url('guru/peta-atp') . '?pilihan=' . $indeks;
        $token = $this->request->getPost('stage_token');
        if (! ctype_digit($paketId) || strlen($paketId) > 15 || (int) $paketId < 1
            || ! is_string($token) || ! AtpStagingService::validasiToken($token)) {
            return redirect()->to($kembali)->with('galat', 'Konfirmasi ATP tidak valid.');
        }

        try {
            $akun = (new AuthService())->penggunaSaatIni();
            if ($akun === null) {
                throw new DomainException('Sesi Guru sudah tidak berlaku.');
            }
            $stage = new AtpStagingService();
            $data = $stage->ambil($token, (int) $akun['id'], (int) $paketId, session_id());
            $jumlah = (new AtpImporKonfirmasiService())->simpan(
                (int) $paketId, $data['file'], $data['fingerprint']
            );
            // Token sekali pakai hanya dihapus setelah transaksi commit sukses.
            $stage->hapus($token);
            return redirect()->to($kembali)->with('sukses',
                'Impor ATP berhasil: ' . $jumlah['baru_bab'] . ' BAB baru, '
                . $jumlah['ubah_bab'] . ' BAB diperbarui, '
                . $jumlah['baru_tp'] . ' TP baru, '
                . $jumlah['ubah_tp'] . ' TP diperbarui; '
                . $jumlah['tetap'] . ' data tetap.');
        } catch (DomainException $e) {
            return redirect()->to($kembali)->with('galat', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Impor ATP gagal: {jenis}', ['jenis' => get_class($e)]);
            return redirect()->to($kembali)->with('galat',
                'Impor ATP gagal. Tidak ada perubahan yang disahkan. Periksa log server.');
        }
    }
}
