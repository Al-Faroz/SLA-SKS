<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Tahap S05: pemeriksaan SERVER sebelum ujian Diagnostik.
 * Baca-saja, tidak membuat percobaan, tidak mengembalikan soal atau kunci ke View.
 */
final class PetunjukDiagnostikSiswaService
{
    private BaseConnection $db;
    private KatalogPembelajaranSiswaService $katalog;
    private ProgresTpSiswaService $progres;

    /** @param (callable():?array)|null $penggunaSaatIni */
    public function __construct(?BaseConnection $db = null, ?callable $penggunaSaatIni = null)
    {
        $this->db = $db ?? db_connect();
        $this->katalog = new KatalogPembelajaranSiswaService($this->db, $penggunaSaatIni);
        $this->progres = new ProgresTpSiswaService($this->db, $penggunaSaatIni);
    }

    /**
     * Hanya data aman: tidak memuat pertanyaan, opsi ataupun kunci.
     *
     * @return array{
     *     tp:array, paket:array, siswa:array, progres:array,
     *     boleh_uji:bool, jumlah_soal:int, jumlah_tayang:int,
     *     durasi_menit:int, syarat:list<array{label:string,terpenuhi:bool,pesan:string}>
     * }
     */
    public function periksa(int $tpId): array
    {
        $ctx = $this->katalog->rincianTp($tpId); // Gen, semester, paket, TP Siap
        $progres = $this->progres->status($tpId);
        if ($progres === null) {
            throw new DomainException('Aktifkan TP dari halaman rincian sebelum melihat petunjuk Diagnostik.');
        }

        $versi = $this->db->table('versi_konten_tp')
            ->select('id,tp_id,nomor_versi,snapshot_json,checksum')
            ->where('id', (int) $progres['versi_konten_tp_id'])
            ->where('tp_id', $tpId)->get()->getRowArray();
        if ($versi === null || !ProgresTpSiswaService::validasiSnapshot($versi, $tpId)) {
            throw new DomainException('Versi TP yang dipakai siswa tidak sah. Hubungi Guru.');
        }

        $data = json_decode($versi['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        $evaluasi = self::periksaBank($data);
        $status = (string) $progres['status'];
        $sedangDiagnostik = $status === 'diagnostik';

        $syarat = [
            ['label' => 'TP aktif dan terikat ke Gen Anda', 'terpenuhi' => true,
                'pesan' => 'Penugasan dan urutan TP telah diverifikasi di server.'],
            ['label' => 'Snapshot TP sah dan tetap', 'terpenuhi' => true,
                'pesan' => 'Versi konten sudah dikunci untuk progres siswa.'],
            ['label' => 'Bank soal Diagnostik siap', 'terpenuhi' => $evaluasi['sah'],
                'pesan' => $evaluasi['sah'] ? 'Jumlah dan struktur soal sesuai.'
                    : 'Bank soal pada snapshot belum lengkap. Hubungi Guru.'],
            ['label' => 'Status progres memungkinkan Diagnostik', 'terpenuhi' => $sedangDiagnostik,
                'pesan' => $sedangDiagnostik ? 'Belum ada hasil Diagnostik sah.'
                    : 'Progres tidak berada pada tahap Diagnostik.'],
        ];
        return [
            'tp' => $ctx['tp'], 'paket' => $ctx['paket'], 'siswa' => $ctx['siswa'],
            'progres' => $progres, 'boleh_uji' => $sedangDiagnostik && $evaluasi['sah'],
            'jumlah_soal' => $evaluasi['jumlah_bank'],
            'jumlah_tayang' => $evaluasi['jumlah_tayang'],
            'durasi_menit' => $evaluasi['durasi_menit'],
            'syarat' => $syarat,
        ];
    }

    /**
     * Pemeriksaan murni terhadap snapshot yang akan digunakan mesin ujian.
     * Tidak memodifikasi nomor soal atau mengubah jenis ujian.
     *
     * @return array{sah:bool,jumlah_bank:int,jumlah_tayang:int,durasi_menit:int}
     */
    public static function periksaBank(array $snapshot): array
    {
        $tp = $snapshot['tp'] ?? [];
        $bank = $snapshot['soal'] ?? [];
        $target = (int) ($tp['diag_tayang'] ?? 0);
        $durasi = (int) ($tp['diag_durasi'] ?? 0);
        if (!is_array($bank)) {
            $bank = [];
        }
        $soalDiag = array_values(array_filter($bank, static fn ($soal): bool =>
            is_array($soal) && ($soal['jenis'] ?? null) === 'diagnostik'
                && (int) ($soal['aktif'] ?? 1) === 1
        ));
        $jumlah = count($soalDiag);
        $valid = $target >= 10 && $target <= 65535 && $durasi >= 5 && $durasi <= 180
            && $jumlah >= $target;
        $ids = [];
        $urutan = [];
        foreach ($soalDiag as $item) {
            $id = (int) ($item['id'] ?? 0);
            $posisi = (int) ($item['urutan'] ?? 0);
            if ($id < 1 || isset($ids[$id]) || $posisi < 1 || isset($urutan[$posisi])
                || !in_array($item['kunci'] ?? null, ['A', 'B', 'C', 'D', 'E'], true)) {
                $valid = false;
            }
            $ids[$id] = true;
            $urutan[$posisi] = true;
            foreach (['pertanyaan', 'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e'] as $field) {
                if (trim((string) ($item[$field] ?? '')) === '') {
                    $valid = false;
                }
            }
        }
        return ['sah' => $valid, 'jumlah_bank' => $jumlah,
            'jumlah_tayang' => $target, 'durasi_menit' => $durasi];
    }
}
