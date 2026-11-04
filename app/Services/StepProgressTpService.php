<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Step Progress Guru diturunkan dari hasil pemeriksaan final, tidak menghitung
 * ulang aturan akademik. Tampilan tidak pernah membuka TP ketika belum sah.
 */
final class StepProgressTpService
{
    /**
     * @param list<array{kode:string,terpenuhi:bool}> $cek
     * @return list<array{label:string,keterangan:string,terpenuhi:bool,tautan:string}>
     */
    public static function susun(array $cek, string $jenisTp, string $status, int $tpId): array
    {
        $statusCek = [];
        foreach ($cek as $item) {
            $statusCek[$item['kode']] = $item['terpenuhi'] === true;
        }

        $root = 'guru/tp/' . $tpId;
        $groups = [
            [
                'label' => 'TP dan CP',
                'keterangan' => 'Identitas, rumusan, jenis TP dan CP aktif',
                'kode' => ['kode_tp','judul','rumusan','jenis-tp','paket','cp'],
                'tautan' => $root . '#rumusan-tp',
            ],
            [
                'label' => 'Satu Materi',
                'keterangan' => 'Media, waktu belajar dan petunjuk sesuai jenis TP',
                'kode' => ['materi','materi-1','media-1'],
                'tautan' => $root . '#materi-tp',
            ],
        ];
        if ($jenisTp === 'non_esensial') {
            $groups[] = [
                'label' => 'Diagnostik',
                'keterangan' => 'Minimal 10 soal sah dan pengaturan kegiatan',
                'kode' => ['kkm','resume_min','tugas_min','diag_durasi','diag_tayang',
                    'bank-diagnostik','valid-diagnostik'],
                'tautan' => $root . '/bank-soal?jenis=diagnostik',
            ];
            $groups[] = [
                'label' => 'Sumatif',
                'keterangan' => 'Minimal 30 soal sah, KKM terset',
                'kode' => ['kkm','sum_durasi','sum_tayang','sum_maks_percobaan','bobot',
                    'bank-sumatif','valid-sumatif','aset-soal'],
                'tautan' => $root . '/bank-soal?jenis=sumatif',
            ];
        }
        $hasil = [];
        foreach ($groups as $group) {
            $ok = true;
            foreach ($group['kode'] as $kode) {
                // Missing code must fail closed, never signal completeness.
                if (($statusCek[$kode] ?? false) !== true) {
                    $ok = false;
                }
            }
            $hasil[] = [
                'label' => $group['label'],
                'keterangan' => $group['keterangan'],
                'terpenuhi' => $ok,
                'tautan' => $group['tautan'],
            ];
        }
        $semuaSah = $cek !== [] && ! in_array(false, array_values($statusCek), true);
        $hasil[] = [
            'label' => $status === 'siap' ? 'TP Siap' : 'Publikasi',
            'keterangan' => $status === 'siap' ? 'Versi konten tersimpan; penyuntingan mengikuti penggunaan siswa'
                : ($semuaSah ? 'Lengkap — perlu konfirmasi Guru' : 'Menunggu langkah sebelumnya'),
            'terpenuhi' => $status === 'siap',
            'tautan' => $root . '/kesiapan',
        ];
        return $hasil;
    }
}
