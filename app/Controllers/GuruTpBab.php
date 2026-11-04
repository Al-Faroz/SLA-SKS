<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TpBabService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/** POST pembentukan TP DRAF dengan kode otomatis dari BAB dan penugasan G1. */
final class GuruTpBab extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function buat(): RedirectResponse
    {
        $bab = $this->request->getPost('bab_id');
        $nama = $this->request->getPost('nama_tp');
        $deskripsi = $this->request->getPost('deskripsi_tp');
        $jenis = $this->request->getPost('jenis_tp');
        $pilihan = $this->request->getPost('pilihan');

        $nomor = is_string($pilihan) && ctype_digit($pilihan) && strlen($pilihan) <= 6
            ? (int) $pilihan : 0;
        $tujuan = site_url('guru/peta-atp') . '?pilihan=' . $nomor;
        // Redirect ke semester asal modal; nomor hanya dipakai untuk navigasi.
        // ID objek dan hak G1 tetap divalidasi ulang oleh Service.
        $semesterPost = $this->request->getPost('semester');
        if (is_string($semesterPost) && ctype_digit($semesterPost)
            && (int) $semesterPost >= 1 && (int) $semesterPost <= 6) {
            $tujuan = site_url('guru/peta-atp/semester/' . (int) $semesterPost) . '?pilihan=' . $nomor;
        }

        if (! is_string($bab) || ! ctype_digit($bab) || strlen($bab) > 18
            || ! is_string($nama) || ! is_string($deskripsi) || ! is_string($jenis)) {
            return redirect()->to($tujuan)->with('galat', 'Data formulir TP tidak valid.');
        }
        try {
            $baru = (new TpBabService())->buat((int) $bab, $nama, $deskripsi, $jenis);
            return redirect()->to($tujuan)->with('sukses',
                'TP draf ' . $baru['kode_tp'] . ' berhasil dibuat. Kegiatan TP belum dipublikasikan.');
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->with('galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membuat TP dalam BAB: ' . get_class($e));
            return redirect()->to($tujuan)->with('galat', 'Penyimpanan TP gagal. Hubungi Admin apabila masalah berulang.');
        }
    }
}
