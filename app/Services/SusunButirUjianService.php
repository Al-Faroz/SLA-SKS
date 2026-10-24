<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Pemilihan dan pengacakan server-side dari snapshot TP yang SUDAH dipin.
 * Domain murni untuk tahap S06A; adapter SQL S06B akan menyimpan output ini
 * di tabel butir_percobaan dalam transaksi yang sama dengan percobaan.
 */
final class SusunButirUjianService
{
    private const HURUF = ['A', 'B', 'C', 'D', 'E'];

    /**
     * @param array<string,mixed> $snapshot
     * @return list<array{soal_asal_id:int,posisi:int,pertanyaan:string,konten_terstruktur_json:?string,opsi_acak_json:string,kunci_acak:string}>
     */
    public static function diagnostik(array $snapshot): array
    {
        $tp = $snapshot['tp'] ?? [];
        $jumlahTayang = (int) ($tp['diag_tayang'] ?? 0);
        $bank = $snapshot['soal'] ?? [];
        if (($snapshot['format'] ?? null) !== 'SLA-SKS-TP-V1'
            || !is_array($bank) || $jumlahTayang < 10 || $jumlahTayang > 65535) {
            throw new DomainException('Snapshot Diagnostik tidak memenuhi kontrak.');
        }
        $kandidat = [];
        $idUnik = [];
        foreach ($bank as $soal) {
            if (!is_array($soal) || ($soal['jenis'] ?? null) !== 'diagnostik'
                || (int) ($soal['aktif'] ?? 1) !== 1) {
                continue;
            }
            $id = (int) ($soal['id'] ?? 0);
            if ($id < 1 || isset($idUnik[$id])
                || !in_array($soal['kunci'] ?? null, self::HURUF, true)
                || trim((string) ($soal['pertanyaan'] ?? '')) === '') {
                throw new DomainException('Ada soal Diagnostik tidak sah pada snapshot.');
            }
            $idUnik[$id] = true;
            foreach (self::HURUF as $huruf) {
                if (trim((string) ($soal['opsi_' . strtolower($huruf)] ?? '')) === '') {
                    throw new DomainException('Opsi A–E pada snapshot belum lengkap.');
                }
            }
            $kandidat[] = $soal;
        }
        if (count($kandidat) < $jumlahTayang) {
            throw new DomainException('Bank soal aktif pada snapshot kurang dari jumlah tayang.');
        }
        self::acakAman($kandidat);
        $terpilih = array_slice($kandidat, 0, $jumlahTayang);
        $hasil = [];
        foreach ($terpilih as $index => $soal) {
            $asal = self::HURUF;
            self::acakAman($asal);

            $raw = $soal['konten_terstruktur_json'] ?? null;
            $rich = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if ($raw !== null && $raw !== '' && !is_array($rich)) {
                throw new DomainException('Konten soal pada snapshot rusak.');
            }
            $isiOpsi = [];
            $htmlOpsi = [];
            $kunci = null;
            foreach (self::HURUF as $i => $baru) {
                $lama = $asal[$i];
                $isiOpsi[$baru] = (string) $soal['opsi_' . strtolower($lama)];
                if (is_array($rich) && isset($rich['html_opsi'][$lama])) {
                    $htmlOpsi[$baru] = (string) $rich['html_opsi'][$lama];
                }
                if ($lama === $soal['kunci']) {
                    $kunci = $baru;
                }
            }
            $konten = is_array($rich) ? [
                'html_pertanyaan' => (string) ($rich['html_pertanyaan'] ?? ''),
                'html_opsi' => $htmlOpsi,
                'word_source_id' => $rich['word_source_id'] ?? null,
            ] : null;
            $hasil[] = [
                'soal_asal_id' => (int) $soal['id'],
                'posisi' => $index + 1,
                'pertanyaan' => (string) $soal['pertanyaan'],
                'konten_terstruktur_json' => $konten === null ? null
                    : json_encode($konten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'opsi_acak_json' => json_encode($isiOpsi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'kunci_acak' => (string) $kunci,
            ];
        }
        return $hasil;
    }

    /**
     * Hanya informasi aman untuk UI S06, bukan row database mentah.
     * Token media historis memerlukan resolver berizin pada tahap S06B.
     */
    public static function untukSiswa(array $butir): array
    {
        $opsi = json_decode((string) ($butir['opsi_acak_json'] ?? ''), true);
        if (!is_array($opsi) || array_keys($opsi) !== self::HURUF) {
            throw new DomainException('Butir ujian rusak.');
        }
        $rich = json_decode((string) ($butir['konten_terstruktur_json'] ?? ''), true);
        return [
            'id' => (int) ($butir['id'] ?? 0),
            'posisi' => (int) ($butir['posisi'] ?? 0),
            'pertanyaan' => (string) ($butir['pertanyaan'] ?? ''),
            'opsi' => $opsi,
            'html_pertanyaan' => is_array($rich) ? (string) ($rich['html_pertanyaan'] ?? '') : '',
            'html_opsi' => is_array($rich) && is_array($rich['html_opsi'] ?? null)
                ? $rich['html_opsi'] : [],
        ];
    }

    /**
     * Skor baru boleh dicatat untuk status selesai_sah oleh mesin S06B.
     * Jawaban yang tidak dipilih dihitung salah; tidak perlu mengirim kunci.
     *
     * @param list<array> $butir
     * @param array<int,string> $jawaban keyed by butir_percobaan.id
     * @return array{jumlah_benar:int,jumlah_soal:int,skor:float}
     */
    public static function hitung(array $butir, array $jawaban): array
    {
        if ($butir === []) {
            throw new DomainException('Tidak ada butir sah untuk dinilai.');
        }
        $benar = 0;
        $seen = [];
        foreach ($butir as $item) {
            $id = (int) ($item['id'] ?? 0);
            $kunci = (string) ($item['kunci_acak'] ?? '');
            if ($id < 1 || isset($seen[$id]) || !in_array($kunci, self::HURUF, true)) {
                throw new DomainException('Butir atau kunci tersimpan tidak sah.');
            }
            $seen[$id] = true;
            $pilihan = $jawaban[$id] ?? null;
            if ($pilihan !== null && !in_array($pilihan, self::HURUF, true)) {
                throw new DomainException('Pilihan jawaban tersimpan tidak sah.');
            }
            if ($pilihan === $kunci) {
                $benar++;
            }
        }
        return [
            'jumlah_benar' => $benar, 'jumlah_soal' => count($butir),
            'skor' => round($benar * 100 / count($butir), 2),
        ];
    }

    /** Fisher–Yates dengan random_int (sumber acak kriptografis PHP). */
    private static function acakAman(array &$items): void
    {
        for ($i = count($items) - 1; $i > 0; --$i) {
            $j = random_int(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }
    }
}
