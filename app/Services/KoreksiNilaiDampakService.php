<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * A14-2: kalkulasi pratinjau dampak usulan koreksi nilai Resume/HOTS.
 *
 * MURNI / tanpa database / tanpa perubahan nilai.
 * Bukan otorisasi pengajuan, keputusan Admin, atau perubahan sertifikat.
 * Snapshot, sumber final, G1, versi, persetujuan dan audit harus diperiksa
 * lagi oleh adapter transaksi resmi setelah skema pasca-V4-0013 diaudit.
 */
final class KoreksiNilaiDampakService
{
    /**
     * @return array{
     *   komponen:string,nilai_sumber_lama:int,nilai_sumber_usulan:int,
     *   nilai_tp_lama:?float,nilai_tp_usulan:?float,
     *   status_nilai_lama:string,status_nilai_usulan:string,
     *   perubahan_sumber:bool,perubahan_nilai_tp:bool,
     *   perlu_tinjau_sertifikat:bool
     * }
     */
    public static function pratinjau(
        string $komponen,
        int $nilaiSumberLama,
        int $nilaiSumberUsulan,
        string $jalur,
        ?float $resume,
        ?float $hots,
        float $sumatifTerbaik,
        string $statusNilaiTercatat,
        ?float $nilaiTpTercatat
    ): array {
        // Layanan ini khusus skor Guru yang semula tersimpan untuk Resume/HOTS.
        // Nilai Sumatif otomatis butuh audit percobaan sah dan koreksi berbeda.
        if (!in_array($komponen, ['resume', 'hots'], true)) {
            throw new DomainException('Pratinjau ini hanya untuk nilai Resume atau HOTS.');
        }
        if ($jalur !== 'belajar_wajib') {
            throw new DomainException('Resume/HOTS tidak menjadi komponen nilai jalur lulus Diagnostik.');
        }
        PenilaianGuruService::skor($nilaiSumberLama);
        PenilaianGuruService::skor($nilaiSumberUsulan);
        if (!is_finite($sumatifTerbaik)
            || ($resume !== null && !is_finite($resume))
            || ($hots !== null && !is_finite($hots))) {
            throw new DomainException('Komponen nilai harus angka terbatas.');
        }

        $nilaiAktual = $komponen === 'resume' ? $resume : $hots;
        if ($nilaiAktual === null || $nilaiAktual !== (float)$nilaiSumberLama) {
            throw new DomainException('Nilai sumber awal tidak cocok dengan snapshot yang ditinjau.');
        }

        // Gunakan kalkulator resmi v2.5; jangan menduplikasi bobot.
        $lama = NilaiTpService::hitung($jalur, $resume, $hots, $sumatifTerbaik);
        if (!in_array($statusNilaiTercatat, ['sementara', 'final'], true)
            || $lama['status_nilai'] !== $statusNilaiTercatat) {
            throw new DomainException('Status nilai TP telah berubah. Muat ulang data.');
        }
        $nilaiLama = $lama['nilai_tp'];
        if ($nilaiLama === null) {
            if ($nilaiTpTercatat !== null) {
                throw new DomainException('Nilai TP sementara tidak boleh memiliki angka final.');
            }
        } elseif ($nilaiTpTercatat === null
            || !is_finite($nilaiTpTercatat)
            || abs($nilaiTpTercatat - $nilaiLama) > 0.00001) {
            throw new DomainException('Nilai TP tersimpan tidak cocok dengan komponen sah.');
        }

        $baru = NilaiTpService::hitung(
            $jalur,
            $komponen === 'resume' ? (float)$nilaiSumberUsulan : $resume,
            $komponen === 'hots' ? (float)$nilaiSumberUsulan : $hots,
            $sumatifTerbaik
        );
        $berubahSumber = $nilaiSumberLama !== $nilaiSumberUsulan;
        $berubahTp = $nilaiLama !== $baru['nilai_tp'];

        return [
            'komponen' => $komponen,
            'nilai_sumber_lama' => $nilaiSumberLama,
            'nilai_sumber_usulan' => $nilaiSumberUsulan,
            'nilai_tp_lama' => $nilaiLama,
            'nilai_tp_usulan' => $baru['nilai_tp'],
            'status_nilai_lama' => $lama['status_nilai'],
            'status_nilai_usulan' => $baru['status_nilai'],
            'perubahan_sumber' => $berubahSumber,
            'perubahan_nilai_tp' => $berubahTp,
            // Flag tinjauan saja. Jangan menyimpulkan sertifikat sudah terbit.
            // Penerbitan revisi mengikuti Master 09 dan kondisi cetak nyata.
            'perlu_tinjau_sertifikat' => $nilaiLama !== null && $berubahTp,
        ];
    }
}
