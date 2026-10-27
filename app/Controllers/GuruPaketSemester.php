<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PaketSemesterGuruService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/** POST pembuatan paket semester draf, tidak menerbitkan ke siswa. */
final class GuruPaketSemester extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function buat(): RedirectResponse
    {
        $pilihan = $this->request->getPost('pilihan');
        $nomor = is_string($pilihan) && ctype_digit($pilihan) && strlen($pilihan) <= 6
            ? (int) $pilihan : 0;
        $tujuan = site_url('guru/peta-atp') . '?pilihan=' . $nomor;
        $semua = [];
        foreach (['gen_id', 'mapel_id', 'semester', 'tahun_id', 'kurikulum_id'] as $kunci) {
            $isi = $this->request->getPost($kunci);
            if (! is_string($isi) || ! ctype_digit($isi) || strlen($isi) > 12) {
                return redirect()->to($tujuan)->with('galat', 'Data paket semester tidak valid.');
            }
            $semua[$kunci] = (int) $isi;
        }

        try {
            (new PaketSemesterGuruService())->buat(
                $semua['gen_id'], $semua['mapel_id'], $semua['semester'],
                $semua['tahun_id'], $semua['kurikulum_id']
            );
            return redirect()->to($tujuan)->with('sukses',
                'Paket semester draf berhasil dibuat. Belum tersedia bagi siswa.');
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->with('galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membuat paket semester: ' . get_class($e));
            return redirect()->to($tujuan)->with('galat',
                'Paket belum tersimpan. Hubungi Admin apabila masalah berulang.');
        }
    }
}
