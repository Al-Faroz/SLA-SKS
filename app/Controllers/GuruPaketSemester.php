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

    /** Buat paket Semester 1–6 dari Peta ATP, sekali POST dan satu transaksi. */
    public function buatEnam(): RedirectResponse
    {
        $pilihan = $this->request->getPost('pilihan');
        $nomor = is_string($pilihan) && ctype_digit($pilihan) && strlen($pilihan) <= 6
            ? (int) $pilihan : 0;
        $tujuan = site_url('guru/peta-atp') . '?pilihan=' . $nomor;
        $gen = $this->request->getPost('gen_id');
        $mapel = $this->request->getPost('mapel_id');
        $kurikulum = $this->request->getPost('kurikulum_id');
        $tahun = $this->request->getPost('tahun_id');
        foreach ([$gen, $mapel, $kurikulum] as $value) {
            if (! is_string($value) || ! ctype_digit($value) || strlen($value) > 12) {
                return redirect()->to($tujuan)->with('galat', 'Gen, Mapel atau Kurikulum tidak valid.');
            }
        }
        if (! is_array($tahun)) {
            return redirect()->to($tujuan)->with('galat',
                'Pilih Tahun Ajaran masing-masing untuk enam semester.');
        }
        try {
            $hasil = (new PaketSemesterGuruService())->buatEnam(
                (int) $gen, (int) $mapel, (int) $kurikulum, $tahun
            );
            return redirect()->to($tujuan)->with('sukses',
                $hasil['baru'] . ' paket draf berhasil dibuat; '
                . $hasil['tetap'] . ' paket tetap tanpa perubahan. '
                . 'Semua paket draf belum dapat diakses siswa.');
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->with('galat', $e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Gagal membuat enam paket ATP: {kelas} {pesan}', [
                'kelas' => get_class($e), 'pesan' => $e->getMessage(),
            ]);
            return redirect()->to($tujuan)->with('galat',
                'Pembuatan paket draf gagal; tidak ada paket baru yang disimpan. '
                . 'Hubungi Admin apabila masalah berulang.');
        }
    }

    public function buat(): RedirectResponse
    {
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
