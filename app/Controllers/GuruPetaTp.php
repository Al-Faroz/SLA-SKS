<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AturanBabService;
use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PaketSemesterGuruService;
use App\Services\PetaTpService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/** Peta ATP per enam semester; aksi tambah BAB diperiksa ulang oleh Service G1. */
final class GuruPetaTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function index(): string|RedirectResponse
    {
        $service = new PetaTpService();
        try {
            $pilihan = $service->genMapelDiampu();
            $nomorPilihan = $this->angkaGet('pilihan');
            $dipilih = PetaTpService::pilihPenugasan($pilihan, $nomorPilihan);

            $semester = [];
            $opsiPaket = ['tahun' => [], 'kurikulum' => []];
            if ($dipilih !== null) {
                $opsiPaket = (new PaketSemesterGuruService())->referensi(
                    (int) $dipilih['angkatan_id'], (int) $dipilih['mapel_id']
                );
                for ($nomor = 1; $nomor <= 6; $nomor++) {
                    $semester[$nomor] = $service->petaSemester(
                        (int) $dipilih['angkatan_id'],
                        (int) $dipilih['mapel_id'],
                        $nomor
                    );
                }
                $semester = AturanBabService::denganStatusForm(
                    $semester,
                    (int) ($dipilih['semester_mulai'] ?? 1)
                );
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
            'pilihan' => $pilihan,
            'dipilih' => $dipilih,
            'semester' => $semester,
            'opsiPaket' => $opsiPaket,
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
