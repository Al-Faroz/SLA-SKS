<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\KegiatanMateriSiswaService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/** S09/S10: POST CSRF + auth:siswa, pengisian manual PRG. */
final class SiswaKegiatanMateri extends BaseController
{
    public function simpan(int $tpId, int $materiId, string $jenis): RedirectResponse
    {
        $tujuan = 'siswa/tp/' . $tpId . '/materi/' . $materiId;
        $aksi = (string) $this->request->getPost('aksi');
        $jawaban = $this->request->getPost('jawaban');
        $revisi = $this->request->getPost('revisi');
        if (!is_string($jawaban) || !is_string($revisi)
            || preg_match('/^\d{1,9}$/D', $revisi) !== 1) {
            return redirect()->to(site_url($tujuan))->withInput()
                ->with('kegiatan_gagal', 'Data formulir tidak sah. Silakan periksa isian.');
        }
        try {
            (new KegiatanMateriSiswaService())->simpan(
                $tpId, $materiId, $jenis, $aksi, $jawaban, (int) $revisi
            );
            return redirect()->to(site_url($tujuan))
                ->with('kegiatan_sukses', $aksi === 'kirim'
                    ? 'Jawaban terkirim final dan terkunci.'
                    : 'Draf tersimpan. Perubahan berikutnya perlu disimpan lagi.');
        } catch (DomainException $e) {
            return redirect()->to(site_url($tujuan))->withInput()
                ->with('kegiatan_gagal', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'S09/S10 kegiatan siswa: {error}',
                ['error' => $e->getMessage()]);
            return redirect()->to(site_url($tujuan))->withInput()
                ->with('kegiatan_gagal', 'Jawaban belum tersimpan. Periksa koneksi lalu coba lagi.');
        }
    }
}
