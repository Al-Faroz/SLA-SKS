<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PermintaanAksesUlangSiswaService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;
use Throwable;

/** POST permintaan akses ulang siswa; tetap auth:siswa dan CSRF global. */
final class SiswaAksesUlang extends BaseController
{
    public function minta(int $tpId, string $jenis): RedirectResponse
    {
        $tujuan = site_url('siswa/tp/' . $tpId);
        try {
            (new PermintaanAksesUlangSiswaService())->minta($tpId, $jenis);
            return redirect()->to($tujuan)->with(
                'akses_ulang_sukses',
                'Permintaan akses ulang telah dikirim kepada Guru pengampu. Tunggu izin sebelum mencoba lagi.'
            );
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->with('akses_ulang_gagal', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Permintaan akses ulang: {error}', ['error' => $e->getMessage()]);
            return redirect()->to($tujuan)->with(
                'akses_ulang_gagal', 'Permintaan belum dapat disimpan. Coba kembali.'
            );
        }
    }
}
