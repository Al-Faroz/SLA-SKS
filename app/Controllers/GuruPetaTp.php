<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PetaTpService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/** Peta ATP baca-saja per enam semester; hanya pasangan Gen–Mapel yang diampu. */
final class GuruPetaTp extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function index(): string|RedirectResponse
    {
        $service = new PetaTpService();
        try {
            $pilihan = $service->genMapelDiampu();
            $gen = $this->angkaGet('gen');
            $mapel = $this->angkaGet('mapel');
            if (($gen === null) !== ($mapel === null)) {
                throw new DomainException('Pilih Gen dan mata pelajaran yang tersedia.');
            }
            $dipilih = null;
            if ($gen !== null && $mapel !== null) {
                foreach ($pilihan as $item) {
                    if ((int) $item['angkatan_id'] === $gen && (int) $item['mapel_id'] === $mapel) {
                        $dipilih = $item;
                        break;
                    }
                }
                if ($dipilih === null) {
                    throw new DomainException('Anda tidak ditugaskan pada Gen dan mata pelajaran tersebut.');
                }
            } else {
                $dipilih = $pilihan[0] ?? null;
            }

            $semester = [];
            if ($dipilih !== null) {
                for ($nomor = 1; $nomor <= 6; $nomor++) {
                    $semester[$nomor] = $service->petaSemester(
                        (int) $dipilih['angkatan_id'],
                        (int) $dipilih['mapel_id'],
                        $nomor
                    );
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
            'pilihan' => $pilihan,
            'dipilih' => $dipilih,
            'semester' => $semester,
        ]);
    }

    private function angkaGet(string $kunci): ?int
    {
        $nilai = $this->request->getGet($kunci);
        if ($nilai === null) {
            return null;
        }
        if (! is_string($nilai) || ! ctype_digit($nilai) || (int) $nilai < 1) {
            throw new DomainException('Parameter pilihan tidak valid.');
        }

        return (int) $nilai;
    }
}
