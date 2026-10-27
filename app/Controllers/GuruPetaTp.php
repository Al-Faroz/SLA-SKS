<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AturanBabService;
use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PaketSemesterGuruService;
use App\Services\PetaTpService;
use App\Services\RingkasanSemesterAtpService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/** Peta ATP ringkas dan halaman semester; otorisasi tetap di PetaTpService (G1). */
final class GuruPetaTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    /** @return array{pilihan:array,dipilih:?array,nomor:int} */
    private function konteksPenugasan(PetaTpService $service): array
    {
        $pilihan = $service->genMapelDiampu();
        $nomor = $this->angkaGet('pilihan') ?? 0;
        $dipilih = PetaTpService::pilihPenugasan($pilihan, $nomor);

        return ['pilihan' => $pilihan, 'dipilih' => $dipilih, 'nomor' => $nomor];
    }

    /** @return array<int,array<string,mixed>> */
    private function seluruhSemester(PetaTpService $service, array $dipilih): array
    {
        $hasil = [];
        for ($nomor = 1; $nomor <= 6; $nomor++) {
            $hasil[$nomor] = $service->petaSemester(
                (int) $dipilih['angkatan_id'],
                (int) $dipilih['mapel_id'],
                $nomor
            );
        }
        return AturanBabService::denganStatusForm(
            $hasil, (int) $dipilih['semester_mulai']
        );
    }

    public function index(): string|RedirectResponse
    {
        $service = new PetaTpService();
        try {
            $konteks = $this->konteksPenugasan($service);
            $semesters = [];
            if ($konteks['dipilih'] !== null) {
                foreach ($this->seluruhSemester($service, $konteks['dipilih']) as $nomor => $data) {
                    $semesters[$nomor] = RingkasanSemesterAtpService::dariPeta($data);
                }
            }
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru'))->with('galat', $e->getMessage());
        }

        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/peta_tp', [
            'judul' => 'Peta ATP enam semester',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'pilihan' => $konteks['pilihan'],
            'dipilih' => $konteks['dipilih'],
            'nomorPilihan' => $konteks['nomor'],
            'ringkasanSemester' => $semesters,
        ]);
    }

    public function semester(string $nomorSemester): string|RedirectResponse
    {
        // Penentuan semester dari route; hanya 1–6 yang diizinkan.
        if (! ctype_digit($nomorSemester) || strlen($nomorSemester) > 2
            || (int) $nomorSemester < 1 || (int) $nomorSemester > 6) {
            return redirect()->to(site_url('guru/peta-atp'))->with('galat', 'Semester tidak valid.');
        }
        $nomor = (int) $nomorSemester;
        $service = new PetaTpService();
        try {
            $konteks = $this->konteksPenugasan($service);
            if ($konteks['dipilih'] === null) {
                return redirect()->to(site_url('guru/peta-atp'))
                    ->with('galat', 'Belum ada penugasan Gen–Mapel untuk membuka semester.');
            }
            $semester = $this->seluruhSemester($service, $konteks['dipilih'])[$nomor];
            $opsiPaket = (new PaketSemesterGuruService())->referensi(
                (int) $konteks['dipilih']['angkatan_id'],
                (int) $konteks['dipilih']['mapel_id']
            );
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/peta-atp'))->with('galat', $e->getMessage());
        }
        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/semester_atp', [
            'judul' => 'ATP Semester ' . $nomor,
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'nomorSemester' => $nomor,
            'kelas' => RingkasanSemesterAtpService::namaKelas($nomor),
            'dipilih' => $konteks['dipilih'],
            'nomorPilihan' => $konteks['nomor'],
            'semester' => $semester,
            'opsiPaket' => $opsiPaket,
            'ringkasan' => RingkasanSemesterAtpService::dariPeta($semester),
        ]);
    }

    private function angkaGet(string $kunci): ?int
    {
        $nilai = $this->request->getGet($kunci);
        if ($nilai === null) {
            return null;
        }
        if (! is_string($nilai) || ! ctype_digit($nilai) || strlen($nilai) > 8) {
            throw new DomainException('Parameter pilihan tidak valid.');
        }
        return (int) $nilai;
    }
}
