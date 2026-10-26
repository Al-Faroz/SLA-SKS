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
    public function simpan(int $tpId, int $materiId, string $jenis,
        ?string $aksiRute = null): RedirectResponse
    {
        $tujuan = 'siswa/tp/' . $tpId . '/materi/' . $materiId;
        $aksiMasuk = $this->request->getPost('aksi');
        $aksiForm = is_string($aksiMasuk) ? $aksiMasuk : '';
        $aksi = $aksiRute ?? $aksiForm;
        // Rute adalah sumber aksi yang tegas; nilai form berbeda ditolak.
        $tujuanKembali = site_url($tujuan) . '#kegiatan-'
            . (in_array($jenis, ['resume', 'hots'], true) ? $jenis : 'resume');
        if (!in_array($jenis, ['resume', 'hots'], true)
            || !in_array($aksi, ['simpan', 'kirim'], true)
            || ($aksiRute !== null && $aksiForm !== '' && $aksiForm !== $aksiRute)) {
            return redirect()->to($tujuanKembali)->withInput()
                ->with('kegiatan_jenis', $jenis)
                ->with('kegiatan_gagal', 'Aksi formulir tidak cocok. Muat ulang dan coba lagi.');
        }
        $jawaban = $this->request->getPost('jawaban');
        $revisi = $this->request->getPost('revisi');
        if (!is_string($jawaban) || !is_string($revisi)
            || preg_match('/^\d{1,9}$/D', $revisi) !== 1) {
            return redirect()->to($tujuanKembali)->withInput()
                ->with('kegiatan_jenis', $jenis)
                ->with('kegiatan_gagal', 'Data formulir tidak sah. Silakan periksa isian.');
        }
        try {
            (new KegiatanMateriSiswaService())->simpan(
                $tpId, $materiId, $jenis, $aksi, $jawaban, (int) $revisi
            );
            return redirect()->to($tujuanKembali)
                ->with('kegiatan_jenis', $jenis)
                ->with('kegiatan_sukses', $aksi === 'kirim'
                    ? 'Jawaban terkirim final dan terkunci.'
                    : 'Draf tersimpan. Perubahan berikutnya perlu disimpan lagi.');
        } catch (DomainException $e) {
            return redirect()->to($tujuanKembali)->withInput()
                ->with('kegiatan_jenis', $jenis)
                ->with('kegiatan_gagal', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'S09/S10 kegiatan siswa: {error}',
                ['error' => $e->getMessage()]);
            return redirect()->to($tujuanKembali)->withInput()
                ->with('kegiatan_jenis', $jenis)
                ->with('kegiatan_gagal', 'Jawaban ' . strtoupper($jenis) . ' belum tersimpan akibat kendala teknis.'
                    . ' Silakan coba lagi atau hubungi pengelola jika terus gagal.');
        }
    }
}
