<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Ringkasan bank soal per TP (baca-saja). Tidak mengeluarkan kunci jawaban.
 * Hak akses G1 wajib diperiksa oleh DetailTpService sebelum dipanggil.
 */
final class RingkasanBankSoalService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** @return array<string,array{aktif:int,target:int,minimal:int,memadai:bool}> */
    public function lihat(int $tpId): array
    {
        $hitung = ['diagnostik' => 0, 'sumatif' => 0];
        foreach ($this->db->table('soal')
            ->select('jenis, COUNT(*) AS jumlah')
            ->where('tp_id', $tpId)
            ->where('aktif', 1)
            ->groupBy('jenis')
            ->get()->getResultArray() as $item) {
            if (array_key_exists($item['jenis'], $hitung)) {
                $hitung[$item['jenis']] = (int) $item['jumlah'];
            }
        }

        $tp = $this->db->table('tp')->select('diag_tayang,sum_tayang')->where('id', $tpId)->get()->getRowArray();
        return self::susun($tp ?? [], $hitung);
    }

    /** @param array<string,int> $hitung
     *  @return array<string,array{aktif:int,target:int,minimal:int,memadai:bool}>
     */
    public static function susun(array $tp, array $hitung): array
    {
        $hasil = [];
        foreach (['diagnostik' => ['diag_tayang', 10], 'sumatif' => ['sum_tayang', 5]] as $jenis => [$kolom, $minimal]) {
            $target = (int) ($tp[$kolom] ?? 0);
            $aktif = max(0, (int) ($hitung[$jenis] ?? 0));
            $hasil[$jenis] = [
                'aktif' => $aktif,
                'target' => $target,
                'minimal' => $minimal,
                'memadai' => $target >= $minimal && $aktif >= $target,
            ];
        }
        return $hasil;
    }
}
