<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PetaTpService;
use App\Services\PenilaianGuruService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** G10/G11: role filter, validasi form dan PRG; otorisasi ulang di Service. */
final class GuruPenilaian extends BaseController
{
    protected $helpers = ['url', 'form'];

    private function angka($nilai, string $field, int $maks = 100000): ?int
    {
        if ($nilai === null || $nilai === '') return null;
        if (!is_string($nilai) || !ctype_digit($nilai)
            || strlen($nilai) > 7 || (int) $nilai > $maks) {
            throw new DomainException('Parameter ' . $field . ' tidak valid.');
        }
        return (int) $nilai;
    }

    private function blokir(string $pesan): ResponseInterface
    {
        return $this->response->setStatusCode(403)->setBody(
            view('publik/ditolak', ['judul' => $pesan])
        );
    }

    public function antrean(): ResponseInterface|string
    {
        try {
            $svc = new PenilaianGuruService();
            $pilihan = $svc->penugasan();
            $index = $this->angka($this->request->getGet('pilihan'), 'pilihan', 10000);
            $dipilih = PetaTpService::pilihPenugasan($pilihan, $index);
            $halaman = $this->angka($this->request->getGet('halaman'), 'halaman') ?? 1;
            $data = $svc->antrean($dipilih, $halaman);
            $akun = (new AuthService())->penggunaSaatIni();
        } catch (DomainException $e) {
            return $this->blokir($e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Antrean penilaian: {error}', ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody(
                view('publik/ditolak', ['judul'=>'Antrean belum dapat dimuat'])
            );
        }
        return view('guru/antrean_penilaian', [
            'judul'=>'Antrean Penilaian Siswa',
            'namaPengguna'=>$akun['nama'],
            'peranAktif'=>'Guru',
            'tautanNavigasi'=>NavigasiService::untuk('guru','penilaian'),
            'penugasan'=>$pilihan,
            'pilihan_aktif'=>$index ?? 0,
            'dipilih'=>$dipilih,
            'antrean'=>$data,
        ]);
    }

    public function lihat(string $jenis, int $id): ResponseInterface|string
    {
        try {
            $tugas = (new PenilaianGuruService())->lihat($jenis, $id);
            $akun = (new AuthService())->penggunaSaatIni();
        } catch (DomainException $e) {
            return $this->blokir($e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Detail penilaian: {error}', ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody(
                view('publik/ditolak', ['judul'=>'Detail belum dapat dibuka'])
            );
        }
        return view('guru/nilai_kegiatan', [
            'judul'=>'Penilaian ' . strtoupper($jenis),
            'namaPengguna'=>$akun['nama'],
            'peranAktif'=>'Guru',
            'tautanNavigasi'=>NavigasiService::untuk('guru','penilaian'),
            'tugas'=>$tugas,
        ]);
    }

    public function simpan(string $jenis, int $id): RedirectResponse
    {
        $tujuan = site_url('guru/penilaian/' . rawurlencode($jenis) . '/' . $id);
        $skor = $this->request->getPost('nilai');
        $umpan = $this->request->getPost('umpan_balik');
        if (!is_string($skor) || preg_match('/^(?:100|[1-9]?[0-9])$/D', $skor) !== 1
            || !is_string($umpan)) {
            return redirect()->to($tujuan)->withInput()
                ->with('nilai_galat', 'Masukkan nilai bulat 0–100 dan umpan balik yang valid.');
        }
        try {
            $svc = new PenilaianGuruService();
            $svc->simpan($jenis, $id, (int) $skor, $umpan);
            if ($this->request->getPost('lanjut') === 'berikutnya') {
                // Berikutnya tetap pada Gen–Mapel tugas yang baru dinilai,
                // jangan melompat ke Gen lain pada akun Guru multi penugasan.
                $dinilai = $svc->lihat($jenis, $id);
                $queue = $svc->antrean([
                    'angkatan_id' => (int) $dinilai['angkatan_id'],
                    'mapel_id' => (int) $dinilai['mapel_id'],
                ], 1);
                if ($queue['baris'] !== []) {
                    $next = $queue['baris'][0];
                    return redirect()->to(site_url('guru/penilaian/'
                        . $next['jenis'] . '/' . $next['id']))
                        ->with('nilai_sukses', 'Nilai berhasil disimpan. Lanjut ke tugas berikutnya.');
                }
                return redirect()->to(site_url('guru/penilaian'))
                    ->with('nilai_sukses', 'Nilai tersimpan. Tidak ada tugas tertunda.');
            }
            return redirect()->to($tujuan)
                ->with('nilai_sukses', 'Nilai berhasil disimpan dan terkunci.');
        } catch (DomainException $e) {
            return redirect()->to($tujuan)->withInput()
                ->with('nilai_galat',$e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Simpan penilaian Guru: {error}',
                ['error'=>$e->getMessage()]);
            return redirect()->to($tujuan)->withInput()
                ->with('nilai_galat', 'Nilai belum tersimpan. Periksa koneksi dan coba lagi.');
        }
    }
}
