<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Pemeriksaan awal konten Materi pada TP. Bukan izin publikasi.
 * Pemeriksaan CP, bank soal, pengaturan dan snapshot wajib dilakukan terpisah.
 */
final class ChecklistKontenTpService
{
    /** @param array<string,mixed> $tp
     *  @param list<array<string,mixed>> $materi
     *  @return list<array{label:string,terpenuhi:bool,keterangan:string}>
     */
    public static function periksa(array $tp, array $materi, ?int $jumlahTautanCp = null): array
    {
        $hasil = [];
        foreach (['kode_tp' => 'Kode TP', 'rumusan' => 'Rumusan TP', 'indikator' => 'Indikator ketercapaian'] as $kolom => $label) {
            $ada = trim((string) ($tp[$kolom] ?? '')) !== '';
            $hasil[] = ['label' => $label, 'terpenuhi' => $ada, 'keterangan' => $ada ? 'Terisi' : 'Belum diisi'];
        }

        if ($jumlahTautanCp !== null) {
            $adaCp = $jumlahTautanCp > 0;
            $hasil[] = ['label' => 'Tautan CP pada TP', 'terpenuhi' => $adaCp,
                'keterangan' => $adaCp ? $jumlahTautanCp . ' CP tertaut (status/versi/CP utama belum diverifikasi)'
                    : 'TP belum ditautkan dengan CP'];
        }

        $adaMateri = count($materi) > 0;
        $hasil[] = ['label' => 'Minimal satu Materi', 'terpenuhi' => $adaMateri,
            'keterangan' => $adaMateri ? count($materi) . ' Materi terdaftar' : 'Tambahkan Materi terlebih dahulu'];

        $urutan = 1;
        foreach ($materi as $item) {
            $nama = 'Materi ' . $urutan;
            $media = in_array(($item['jenis'] ?? ''), ['pdf', 'gambar', 'audio'], true)
                ? ! empty($item['berkas_id'])
                : (($item['jenis'] ?? '') === 'youtube' && trim((string) ($item['url_youtube'] ?? '')) !== '');
            $resume = trim((string) ($item['petunjuk_resume'] ?? '')) !== '';
            $hots = trim((string) ($item['petunjuk_hots'] ?? '')) !== '';
            $nomorBenar = (int) ($item['urutan'] ?? 0) === $urutan;
            foreach ([
                ['Media', $media, 'Sumber media belum tersedia'],
                ['Petunjuk Resume', $resume, 'Isi petunjuk Resume'],
                ['Petunjuk HOTS', $hots, 'Isi petunjuk HOTS'],
                ['Urutan', $nomorBenar, 'Periksa nomor urutan Materi'],
            ] as [$aspek, $valid, $pesan]) {
                $hasil[] = ['label' => $nama . ' — ' . $aspek, 'terpenuhi' => $valid,
                    'keterangan' => $valid ? 'Lengkap' : $pesan];
            }
            $urutan++;
        }
        return $hasil;
    }
}
