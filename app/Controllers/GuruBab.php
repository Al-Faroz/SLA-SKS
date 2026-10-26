<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BabService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/** Aksi POST Guru membuat BAB di paket semester yang sudah ada. */
final class GuruBab extends BaseController
{
    public function buat(): RedirectResponse
    {
        $paket = $this->request->getPost('paket_id');
        $nama = $this->request->getPost('nama_bab');
        $deskripsi = $this->request->getPost('deskripsi_bab');
        $pilihan = $this->request->getPost('pilihan');
        $nomor = is_string($pilihan) && ctype_digit($pilihan) && strlen($pilihan) < 6
            ? (int) $pilihan : 0;
        $tujuan = site_url('guru/peta-atp') . '?pilihan=' . $nomor;

        if (! is_string($paket) || ! ctype_digit($paket) || strlen($paket) > 18
            || ! is_string($nama) || ! is_string($deskripsi)) {
            return redirect()->to($tujuan)->with('galat', 'Data BAB tidak valid.');
        }
        try {
            (new BabService())->buat((int) $paket, $nama, $deskripsi);
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->with('galat', $e->getMessage());
        }
        return redirect()->to($tujuan)->with('sukses', 'BAB berhasil ditambahkan.');
    }
}
