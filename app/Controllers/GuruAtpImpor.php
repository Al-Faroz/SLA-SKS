<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PetaTpService;
use CodeIgniter\HTTP\RedirectResponse;
use DomainException;

/**
 * G05 — Gerbang tunggal Excel ATP enam semester.
 * Paket dipilih sebagai konteks kurikulum/Gen–Mapel, Tahun Ajaran
 * tiap semester selalu berasal dari paket masing-masing di Service.
 */
final class GuruAtpImpor extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function index(): string|RedirectResponse
    {
        $service = new PetaTpService();
        try {
            $pilihan = $service->genMapelDiampu();
            $raw = $this->request->getGet('pilihan');
            if ($raw !== null && (! is_string($raw) || ! ctype_digit($raw)
                || strlen($raw) > 6)) {
                throw new DomainException('Pilihan Gen–Mapel tidak valid.');
            }
            $indeks = $raw === null ? 0 : (int) $raw;
            $dipilih = PetaTpService::pilihPenugasan($pilihan, $indeks);
            $opsi = [];
            $status = [];
            if ($dipilih !== null) {
                for ($n = 1; $n <= 6; ++$n) {
                    $semester = $service->petaSemester(
                        (int) $dipilih['angkatan_id'], (int) $dipilih['mapel_id'], $n
                    );
                    $status[$n] = [
                        'bab' => 0, 'tp' => 0,
                        'paket' => 0, 'tahun_ids' => [],
                    ];
                    $idsPaketNonarsip = [];
                    foreach ($semester['paket'] as $paket) {
                        if ($paket['status'] === 'arsip' || $n < (int) $dipilih['semester_mulai']) {
                            continue;
                        }
                        $status[$n]['paket']++;
                        $idsPaketNonarsip[] = (int) $paket['id'];
                        $status[$n]['tahun_ids'][] = (int) $paket['tahun_ajaran_id'];
                        $opsi[] = $paket;
                    }
                    // Statistik konsisten dengan domain paket yang bisa diimpor.
                    // Paket arsip tidak boleh membuat paket aktif kosong tampak terkunci.
                    foreach ($semester['bab'] as $bab) {
                        if (in_array((int) $bab['paket_semester_id'], $idsPaketNonarsip, true)) {
                            $status[$n]['bab']++;
                        }
                    }
                    foreach ($semester['tp'] as $tp) {
                        if (in_array((int) $tp['paket_semester_id'], $idsPaketNonarsip, true)) {
                            $status[$n]['tp']++;
                        }
                    }
                }
            }
            $ids = array_map(static fn (array $p): int => (int) $p['id'], $opsi);
            $paketRaw = $this->request->getGet('paket_id');
            if ($paketRaw !== null && (! is_string($paketRaw)
                || ! ctype_digit($paketRaw) || strlen($paketRaw) > 15
                || ! in_array((int) $paketRaw, $ids, true))) {
                throw new DomainException('Paket acuan bukan milik Gen–Mapel terpilih.');
            }
            $paketId = $paketRaw === null ? ($ids[0] ?? 0) : (int) $paketRaw;
            $tahun = [];
            $kurikulum = [];
            if ($opsi !== []) {
                $tahun = array_column(db_connect()->table('ref_tahun_ajaran')
                    ->select('nama,id')->get()->getResultArray(), 'nama', 'id');
                foreach (db_connect()->table('ref_kurikulum')
                    ->select('id,nama,versi')->get()->getResultArray() as $k) {
                    $kurikulum[(int) $k['id']] = $k['nama'] . ' — ' . $k['versi'];
                }
            }
        } catch (DomainException $e) {
            return redirect()->to(site_url('guru/peta-atp'))
                ->with('galat', $e->getMessage());
        }
        $akun = (new AuthService())->penggunaSaatIni();
        return view('guru/impor_atp', [
            'judul' => 'Kelola ATP Excel enam semester',
            'namaPengguna' => $akun['nama'],
            'peranAktif' => 'Guru',
            'tautanNavigasi' => NavigasiService::untuk('guru', 'peta_tp'),
            'pilihan' => $pilihan,
            'dipilih' => $dipilih,
            'nomorPilihan' => $indeks,
            'opsiPaket' => $opsi,
            'paketId' => $paketId,
            'statusSemester' => $status,
            'namaTahun' => $tahun,
            'namaKurikulum' => $kurikulum,
        ]);
    }
}
