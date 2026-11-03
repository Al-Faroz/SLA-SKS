<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PetaTpService;
use App\Services\RekapNilaiTpGuruService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A6: rekap nilai baca-saja, izin diperiksa ulang di Service. */
final class GuruRekapNilai extends BaseController
{
    protected $helpers = ['url'];

    private function angka(mixed $nilai, string $nama, int $maks): ?int
    {
        if ($nilai === null || $nilai === '') return null;
        if (!is_string($nilai) || !ctype_digit($nilai)
            || strlen($nilai) > 7 || (int) $nilai > $maks) {
            throw new DomainException('Parameter ' . $nama . ' tidak valid.');
        }
        return (int) $nilai;
    }

    private function ditolak(string $pesan): ResponseInterface
    {
        return $this->response->setStatusCode(403)
            ->setBody(view('publik/ditolak', [
                'judul'=>'Akses rekap nilai ditolak', 'pesan'=>$pesan,
                'tautanPath'=>'guru/peta-atp',
                'labelTautan'=>'Periksa penugasan Peta ATP',
            ]));
    }

    public function index(): ResponseInterface|string
    {
        try {
            $akun = (new AuthService())->penggunaSaatIni();
            if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'guru'
                || ($akun['status'] ?? '') !== 'aktif'
                || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
                return $this->ditolak('Hanya Guru aktif yang boleh melihat nilai siswa.');
            }
            $svc = new RekapNilaiTpGuruService();
            $pilihan = $svc->penugasan();
            $index = $this->angka($this->request->getGet('pilihan'),'pilihan',10000);
            $semester = $this->angka($this->request->getGet('semester'),'semester',6) ?? 1;
            $halaman = $this->angka($this->request->getGet('halaman'),'halaman',100000) ?? 1;
            $jenisTp = $this->request->getGet('jenis_tp') ?? 'non_esensial';
            $bab = $this->angka($this->request->getGet('bab'),'bab',100000) ?? 0;
            if (!is_string($jenisTp) || !in_array($jenisTp,
                ['non_esensial','esensial','semua'],true)
                || $semester < 1 || $halaman < 1) {
                throw new DomainException('Semester/halaman rekap tidak valid.');
            }
            $dipilih = PetaTpService::pilihPenugasan($pilihan, $index);
            $daftarBab = $svc->daftarBab($dipilih,$semester);
            $hasil = $svc->rekap($dipilih, $semester, $halaman, 25,
                $jenisTp,$bab > 0 ? $bab : null);
            return view('guru/rekap_nilai_tp', [
                'judul'=>'Rekap Nilai TP Guru',
                'namaPengguna'=>$akun['nama'],
                'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','rekap_nilai'),
                'penugasan'=>$pilihan,
                'pilihan_aktif'=>$index ?? 0,
                'dipilih'=>$dipilih,
                'semester'=>$semester,
                'jenisTp'=>$jenisTp,
                'bab'=>$bab,
                'daftarBab'=>$daftarBab,
                'rekap'=>$hasil,
            ]);
        } catch (DomainException $e) {
            if ($this->request->getGet('pilihan') !== null
                || $this->request->getGet('semester') !== null
                || $this->request->getGet('halaman') !== null
                || $this->request->getGet('bab') !== null
                || $this->request->getGet('jenis_tp') !== null) {
                return redirect()->to(site_url('guru/rekap-nilai'))
                    ->with('rekap_galat','Filter rekap tidak valid atau tidak diizinkan.');
            }
            return $this->ditolak($e->getMessage());
        } catch (Throwable $e) {
            log_message('error','A6 Rekap TP Guru: {error}', ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)
                ->setBody(view('publik/galat_sistem', [
                    'judul'=>'Rekap nilai belum dapat dimuat',
                    'pesan'=>'Periksa skema nilai TP dan coba kembali.',
                ]));
        }
    }
}
