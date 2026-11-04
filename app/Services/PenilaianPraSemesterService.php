<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Domain murni nilai dan kesempatan sah Pra-Semester.
 *
 * Percobaan wajib diambil dari satu ujian_pra_semester_id + siswa_id
 * dengan urutan id ASC oleh adapter yang mengunci transaksi.
 * Tidak membaca DB, tidak memberi izin mengikuti jadwal.
 */
final class PenilaianPraSemesterService
{
    private const STATUS = [
        'baru', 'berjalan', 'jeda_fullscreen', 'jeda_koneksi',
        'selesai_sah', 'dibatalkan_menunggu_guru',
    ];
    private const AKTIF = ['baru','berjalan','jeda_fullscreen','jeda_koneksi'];

    /**
     * @param list<array<string,mixed>> $percobaan
     * @return array{fase:string,tahap_berikut:?string,skor_normal:?float,skor_remedial:?float,nilai_terbaik:?float,status_nilai:?string,kkm:?int,percobaan_aktif_id:?int,jumlah_sah_normal:int,jumlah_sah_remedial:int,jumlah_batal:int}
     */
    public static function ringkas(array $percobaan): array
    {
        $kkm = null;
        $normal = null;
        $remedial = null;
        $aktif = null;
        $terakhir = null;
        $terlihat = [];
        $batal = 0;
        $nomorTerakhir = 0;
        foreach ($percobaan as $a) {
            if (!is_array($a)) throw new DomainException('Percobaan Pra-Semester tidak sah.');
            $id = (int)($a['id'] ?? 0);
            $tahap = $a['tahap'] ?? null;
            $status = $a['status'] ?? null;
            $rawKkm = $a['kkm'] ?? null;
            if ($id < 1 || isset($terlihat[$id]) || $id <= $nomorTerakhir
                || !in_array($tahap,['normal','remedial'],true)
                || !in_array($status,self::STATUS,true)
                || (!is_int($rawKkm) && !(is_string($rawKkm) && ctype_digit($rawKkm)))
                || (int)$rawKkm < 1 || (int)$rawKkm > 100) {
                throw new DomainException('Identitas, urutan, tahap, status atau KKM percobaan rusak.');
            }
            if ($kkm !== null && $kkm !== (int)$rawKkm) {
                throw new DomainException('KKM Pra-Semester tidak boleh berubah antarpercobaan siswa.');
            }
            $kkm = (int)$rawKkm;
            $terlihat[$id] = true;
            $nomorTerakhir = $id;
            $skor = $a['skor'] ?? null;
            if ($status === 'selesai_sah') {
                if ((!is_numeric($skor) || is_bool($skor))
                    || !is_finite((float)$skor)
                    || (float)$skor < 0 || (float)$skor > 100) {
                    throw new DomainException('Skor ujian sah wajib angka 0–100.');
                }
                $skor = round((float)$skor,2);
            } elseif ($skor !== null) {
                throw new DomainException('Percobaan belum sah atau batal tidak boleh memiliki skor.');
            }
            if ($aktif !== null) {
                throw new DomainException('Percobaan aktif harus menjadi percobaan terakhir.');
            }
            if (in_array($status,self::AKTIF,true)) $aktif = $id;
            if ($status === 'dibatalkan_menunggu_guru') $batal++;
            if ($tahap === 'normal') {
                if ($normal !== null || $remedial !== null
                    || ($terakhir !== null && $terakhir['tahap'] === 'remedial')) {
                    throw new DomainException('Ujian normal tidak boleh dimulai setelah hasil sah atau remedial.');
                }
                if ($status === 'selesai_sah') $normal = $skor;
            } else {
                if ($normal === null || $normal >= $kkm || $remedial !== null) {
                    throw new DomainException('Remedial hanya setelah normal sah di bawah KKM.');
                }
                if ($status === 'selesai_sah') $remedial = $skor;
            }
            $terakhir = ['id'=>$id,'tahap'=>$tahap,'status'=>$status];
        }

        $nilai = $normal === null ? null : ($remedial === null
            ? $normal : max($normal,$remedial));
        $statusNilai = $nilai === null ? null
            : (($normal >= (int)$kkm || $remedial !== null) ? 'final' : 'sementara');

        if ($aktif !== null) {
            $fase='sedang_ujian';
            $tahapBerikut=(string)$terakhir['tahap'];
        } elseif ($normal === null) {
            $fase = $terakhir === null ? 'belum_mulai' : 'butuh_susulan_normal';
            $tahapBerikut='normal';
        } elseif ($normal >= (int)$kkm || $remedial !== null) {
            $fase='selesai';
            $tahapBerikut=null;
        } elseif ($terakhir !== null && $terakhir['tahap'] === 'remedial'
            && $terakhir['status'] === 'dibatalkan_menunggu_guru') {
            $fase='butuh_susulan_remedial';
            $tahapBerikut='remedial';
        } else {
            $fase='menunggu_remedial';
            $tahapBerikut='remedial';
        }

        return [
            'fase'=>$fase,
            'tahap_berikut'=>$tahapBerikut,
            'skor_normal'=>$normal,
            'skor_remedial'=>$remedial,
            'nilai_terbaik'=>$nilai,
            'status_nilai'=>$statusNilai,
            'kkm'=>$kkm,
            'percobaan_aktif_id'=>$aktif,
            'jumlah_sah_normal'=>$normal === null ? 0 : 1,
            'jumlah_sah_remedial'=>$remedial === null ? 0 : 1,
            'jumlah_batal'=>$batal,
        ];
    }
}
