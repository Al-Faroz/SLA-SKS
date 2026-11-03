<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PetaTpService;
use App\Services\RekapNilaiPraSemesterGuruService;
use CodeIgniter\HTTP\ResponseInterface;
use DomainException;
use Throwable;

/** A9: halaman nilai Pra-Semester terpisah, baca-saja, diperiksa G1. */
final class GuruRekapPraSemester extends BaseController
{
    protected $helpers = ['url'];

    private function angka(mixed $nilai, string $nama, int $maks): ?int
    {
        if ($nilai === null || $nilai === '') return null;
        if (!is_string($nilai) || !ctype_digit($nilai)
            || strlen($nilai) > 7 || (int)$nilai > $maks) {
            throw new DomainException('Filter '.$nama.' tidak sah.');
        }
        return (int)$nilai;
    }

    public function index(): ResponseInterface|string
    {
        try {
            $svc = new RekapNilaiPraSemesterGuruService();
            $pilihan = $svc->penugasan();
            $index = $this->angka($this->request->getGet('pilihan'),'Gen–Mapel',10000);
            $semester = $this->angka($this->request->getGet('semester'),'semester',6) ?? 1;
            $halaman = $this->angka($this->request->getGet('halaman'),'halaman',100000) ?? 1;
            $dipilih = PetaTpService::pilihPenugasan($pilihan,$index);
            $rekap = $svc->rekap($dipilih,$semester,$halaman);
            $akun = (new AuthService())->penggunaSaatIni();
            return view('guru/rekap_nilai_pra_semester', [
                'judul'=>'Rekap Nilai Pra-Semester',
                'namaPengguna'=>$akun['nama'],
                'peranAktif'=>'Guru',
                'tautanNavigasi'=>NavigasiService::untuk('guru','rekap_pra_semester'),
                'penugasan'=>$pilihan,
                'pilihan_aktif'=>$index ?? 0,
                'semester'=>$semester,
                'dipilih'=>$dipilih,
                'rekap'=>$rekap,
            ]);
        } catch (DomainException $e) {
            if ($this->request->getGet() !== []) {
                return redirect()->to(site_url('guru/rekap-nilai/pra-semester'))
                    ->with('rekap_galat','Filter tidak valid atau tidak diizinkan.');
            }
            return $this->response->setStatusCode(403)->setBody(view('publik/ditolak',[
                'judul'=>'Akses rekap Pra-Semester ditolak',
                'pesan'=>$e->getMessage(),
                'tautanPath'=>'guru/peta-atp',
                'labelTautan'=>'Periksa penugasan Guru',
            ]));
        } catch (Throwable $e) {
            log_message('error','A9 Rekap Pra-Semester Guru: {error}',
                ['error'=>$e->getMessage()]);
            return $this->response->setStatusCode(500)->setBody(
                view('publik/galat_sistem',[
                    'judul'=>'Rekap Pra-Semester gagal dimuat',
                    'pesan'=>'Data ujian belum dapat dibaca. Periksa log server.',
                ])
            );
        }
    }
}
