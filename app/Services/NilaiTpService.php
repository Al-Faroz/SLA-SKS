<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;

/**
 * Nilai TP v2.5: hanya dari percobaan sah dan penilaian Guru yang final.
 * Pemanggil WAJIB mengunci progres_tp dan membuka transaksi sebelum sinkronkan().
 */
final class NilaiTpService
{
    /** @return array{nilai_tp:?float,status_nilai:string} */
    public static function hitung(string $jalur, ?float $resume, ?float $hots, float $sumatif): array
    {
        if (!in_array($jalur, ['lulus_diagnostik', 'belajar_wajib'], true)
            || $sumatif < 0 || $sumatif > 100
            || ($resume !== null && ($resume < 0 || $resume > 100))
            || ($hots !== null && ($hots < 0 || $hots > 100))) {
            throw new DomainException('Komponen nilai TP tidak sah.');
        }
        if ($jalur === 'lulus_diagnostik') {
            return ['nilai_tp' => round($sumatif, 2), 'status_nilai' => 'final'];
        }
        if ($resume === null || $hots === null) {
            return ['nilai_tp' => null, 'status_nilai' => 'sementara'];
        }
        return ['nilai_tp' => round($resume * .20 + $hots * .20 + $sumatif * .60, 2),
            'status_nilai' => 'final'];
    }

    /**
     * Hanya progres TP akun siswa yang login; tidak menerima siswa_id dari URL.
     * Nilai 0 tetap angka, sedangkan komponen yang menunggu Guru tetap NULL.
     */
    public static function untukSiswa(int $tpId, ?BaseConnection $db = null,
        ?callable $akun = null): ?array
    {
        $db = $db ?? db_connect();
        $progres = (new ProgresTpSiswaService($db, $akun))->status($tpId);
        if ($progres === null || $progres['status'] !== 'tuntas') return null;
        return $db->table('nilai_tp')
            ->select('jalur,nilai_resume,nilai_hots,nilai_sumatif,nilai_tp,status_nilai')
            ->where('progres_tp_id', (int) $progres['id'])->get()->getRowArray();
    }

    /** @return array<string,mixed>|null */
    public static function sinkronkan(BaseConnection $db, int $progresId): ?array
    {
        $p = $db->table('progres_tp')->select('id,tp_id,versi_konten_tp_id,status')
            ->where('id', $progresId)->get()->getRowArray();
        if ($p === null || $p['status'] !== 'tuntas') return null;

        $attempt = static function (string $jenis, string $tahap) use ($db, $p): ?array {
            return $db->table('percobaan_ujian')
                ->select('id,skor,kkm,status,versi_konten_tp_id')
                ->where('progres_tp_id', (int) $p['id'])
                ->where('versi_konten_tp_id', (int) $p['versi_konten_tp_id'])
                ->where('jenis', $jenis)->where('tahap', $tahap)
                ->where('status', 'selesai_sah')->orderBy('id', 'DESC')
                ->get(1)->getRowArray();
        };
        $diagnostik = $attempt('diagnostik', 'normal');
        $normal = $attempt('sumatif', 'normal');
        $remedial = $attempt('sumatif', 'remedial');
        if ($normal === null || $normal['skor'] === null) {
            throw new DomainException('Nilai TP memerlukan Sumatif sah.');
        }
        $langsungBelajar = false;
        if ($diagnostik === null || $diagnostik['skor'] === null) {
            // Keputusan langsung_belajar oleh Guru setelah Diagnostik batal,
            // tidak menciptakan skor Diagnostik fiktif.
            $langsungBelajar = $db->table('keputusan_ujian_batal k')
                ->join('percobaan_ujian u','u.id=k.percobaan_ujian_id')
                ->where('u.progres_tp_id',$progresId)
                ->where('u.jenis','diagnostik')
                ->where('u.status','dibatalkan_menunggu_guru')
                ->where('k.keputusan','langsung_belajar')
                ->countAllResults() > 0;
            if (!$langsungBelajar) {
                throw new DomainException('Nilai TP memerlukan Diagnostik sah atau keputusan Guru Langsung Belajar.');
            }
        }
        $awal = (float) $normal['skor'];
        $kkm = (int) $normal['kkm'];
        if ($kkm < 1 || $kkm > 100
            || ($awal < $kkm && ($remedial === null || $remedial['skor'] === null))) {
            throw new DomainException('Sumatif remedial sah belum lengkap.');
        }
        $terbaik = max($awal, $remedial !== null ? (float) $remedial['skor'] : $awal);
        $jalur = !$langsungBelajar && (float) $diagnostik['skor'] >= (int) $diagnostik['kkm']
            ? 'lulus_diagnostik' : 'belajar_wajib';
        $resume = null;
        $hots = null;
        if ($jalur === 'belajar_wajib') {
            $versi = $db->table('versi_konten_tp')
                ->select('id,tp_id,nomor_versi,snapshot_json,checksum')
                ->where('id', (int) $p['versi_konten_tp_id'])
                ->where('tp_id', (int) $p['tp_id'])->get()->getRowArray();
            if (!$versi || !ProgresTpSiswaService::validasiSnapshot($versi, (int) $p['tp_id'])) {
                throw new DomainException('Snapshot TP terpin tidak sah untuk nilai.');
            }
            $json = json_decode((string) $versi['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($json['materi'] ?? null) || count($json['materi']) !== 1) {
                throw new DomainException('Nilai jalur belajar memerlukan tepat satu Materi.');
            }
            $key = [
                'progres_tp_id' => (int) $p['id'],
                'versi_konten_tp_id' => (int) $p['versi_konten_tp_id'],
                'materi_id' => (int) $json['materi'][0]['id'],
            ];
            foreach (['resume_materi' => 'resume', 'jawaban_hots_materi' => 'hots'] as $tabel => $label) {
                $row = $db->table($tabel)->select('status,nilai')->where($key)->get()->getRowArray();
                if ($row === null || $row['status'] !== 'final') {
                    throw new DomainException('Jawaban ' . strtoupper($label) . ' final belum tersedia.');
                }
                if ($row['nilai'] !== null) {
                    $$label = (float) $row['nilai'];
                }
            }
        }
        $nilai = self::hitung($jalur, $resume, $hots, $terbaik);
        $data = [
            'jalur' => $jalur, 'nilai_resume' => $resume,
            'nilai_hots' => $hots, 'nilai_sumatif' => round($terbaik, 2),
            'nilai_tp' => $nilai['nilai_tp'], 'status_nilai' => $nilai['status_nilai'],
        ];
        $lama = $db->table('nilai_tp')->where('progres_tp_id', $progresId)->get()->getRowArray();
        if ($lama !== null) {
            $beda = false;
            foreach ($data as $field => $value) {
                $sumber = $lama[$field] ?? null;
                if (is_numeric($value) && is_numeric($sumber)) {
                    $beda = $beda || abs((float) $sumber - (float) $value) > 0.00001;
                } else {
                    $beda = $beda || $sumber !== $value;
                }
            }
            if ($beda) {
                // Nilai final yang pernah dicatat tidak boleh diam-diam
                // ditimpa sinkronisasi otomatis. Koreksi nilai adalah jalur
                // administratif terpisah dengan audit.
                if ($lama['status_nilai'] === 'final') {
                    throw new DomainException('Nilai TP final sudah terkunci. Gunakan prosedur koreksi nilai.');
                }
                $db->table('nilai_tp')->where('id', (int) $lama['id'])
                    ->update($data + ['diubah_pada' => gmdate('Y-m-d H:i:s')]);
            }
        } else {
            $db->table('nilai_tp')->insert(['progres_tp_id' => $progresId]
                + $data + ['diubah_pada' => gmdate('Y-m-d H:i:s')]);
        }
        return $data;
    }
}
