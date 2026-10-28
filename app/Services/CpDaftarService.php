<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Daftar CP khusus Admin: filter dan pagination di SQL, export mengikuti filter.
 * Tidak mengubah aturan bisnis penerbitan CP dan versi historis.
 */
final class CpDaftarService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /** @return array{cari:string,mapel:int,kurikulum:int,status:string} */
    public static function filter(array $get): array
    {
        $status = $get['status'] ?? 'semua';
        if (!is_string($status) || !in_array($status, ['semua', 'aktif', 'draf', 'belum'], true)) {
            throw new DomainException('Filter status CP tidak dikenal.');
        }
        $angka = static function ($value): int {
            if ($value === null || $value === '') {
                return 0;
            }
            if (!is_string($value) || !ctype_digit($value) || strlen($value) > 9) {
                throw new DomainException('Filter referensi tidak valid.');
            }
            return (int) $value;
        };
        $cari = $get['cari'] ?? '';
        if (!is_string($cari)) {
            throw new DomainException('Kata pencarian CP tidak valid.');
        }
        $cari = trim($cari);
        if (mb_strlen($cari) > 80) {
            throw new DomainException('Pencarian CP maksimal 80 karakter.');
        }
        return ['cari' => $cari, 'mapel' => $angka($get['mapel'] ?? null),
            'kurikulum' => $angka($get['kurikulum'] ?? null), 'status' => $status];
    }

    public static function halamanGet(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 1;
        }
        if (!is_string($value) || !ctype_digit($value) || strlen($value) > 8) {
            throw new DomainException('Nomor halaman CP tidak valid.');
        }
        return max(1, (int) $value);
    }

    private function admin(): void
    {
        $akun = (new AuthService($this->db))->penggunaSaatIni();
        if ($akun === null || ($akun['peran_aktif'] ?? '') !== 'admin'
            || (int) ($akun['wajib_ganti_sandi'] ?? 1) !== 0) {
            throw new DomainException('Hanya Admin aktif yang boleh melihat daftar CP.');
        }
    }

    /** @param array{cari:string,mapel:int,kurikulum:int,status:string} $filter */
    /** Satu baris satu CP operasional; sumber/elemen boleh lebih dari satu. */
    private function builder(array $filter): BaseBuilder
    {
        $aktif = "(SELECT ce.cp_mapel_id,"
            . " COUNT(DISTINCT CASE WHEN v.status='aktif' THEN v.id ELSE NULL END) AS jumlah_aktif,"
            . " GROUP_CONCAT(DISTINCT CASE WHEN v.status='aktif' THEN v.elemen ELSE NULL END"
            . " ORDER BY v.elemen SEPARATOR ' | ') AS elemen_aktif"
            . " FROM cp_mapel_elemen ce JOIN ref_cp_versi v ON v.id=ce.cp_versi_id"
            . " GROUP BY ce.cp_mapel_id) a";
        $sumber = "(SELECT rc.kurikulum_id,rc.mapel_id,rc.fase_id,"
            . " COUNT(DISTINCT CASE WHEN rv.status='draf' THEN rv.id ELSE NULL END) AS jumlah_draf,"
            . " COUNT(DISTINCT rc.id) AS jumlah_sumber"
            . " FROM ref_cp rc LEFT JOIN ref_cp_versi rv ON rv.cp_id=rc.id"
            . " GROUP BY rc.kurikulum_id,rc.mapel_id,rc.fase_id) d";
        $q = $this->db->table('cp_mapel c')
            ->join('ref_mapel m', 'm.id=c.mapel_id')
            ->join('ref_kurikulum k', 'k.id=c.kurikulum_id')
            ->join('ref_fase f', 'f.id=c.fase_id')
            ->join($aktif, 'a.cp_mapel_id=c.id', 'left', false)
            ->join($sumber, 'd.kurikulum_id=c.kurikulum_id'
                . ' AND d.mapel_id=c.mapel_id AND d.fase_id=c.fase_id', 'left', false)
            ->where('f.kode', 'D');
        if ($filter['mapel'] > 0) {
            $q->where('c.mapel_id', $filter['mapel']);
        }
        if ($filter['kurikulum'] > 0) {
            $q->where('c.kurikulum_id', $filter['kurikulum']);
        }
        if ($filter['status'] === 'aktif') {
            $q->where('COALESCE(a.jumlah_aktif,0) > 0', null, false);
        } elseif ($filter['status'] === 'draf') {
            $q->where('COALESCE(d.jumlah_draf,0) > 0', null, false);
        } elseif ($filter['status'] === 'belum') {
            $q->where('COALESCE(a.jumlah_aktif,0) = 0', null, false);
        }
        if ($filter['cari'] !== '') {
            $q->groupStart()
                ->like('m.kode', $filter['cari'])
                ->orLike('m.nama', $filter['cari'])
                ->orLike('k.nama', $filter['cari'])
                ->orLike('a.elemen_aktif', $filter['cari'])
                ->groupEnd();
        }
        return $q;
    }

    private static function kolom(): string
    {
        return "c.id, CONCAT('CP-',m.kode,'-',f.kode) AS kode,"
            . "m.nama AS mapel_nama,k.nama AS kurikulum_nama,"
            . "k.versi AS kurikulum_versi,f.kode AS fase,"
            . "COALESCE(a.jumlah_aktif,0) AS jumlah_elemen_aktif,"
            . "COALESCE(d.jumlah_draf,0) AS jumlah_elemen_draf,"
            . "COALESCE(d.jumlah_sumber,0) AS jumlah_sumber,"
            . "COALESCE(a.elemen_aktif,'') AS elemen,"
            . "CASE WHEN COALESCE(a.jumlah_aktif,0)>0 THEN 'Aktif'"
            . " WHEN COALESCE(d.jumlah_draf,0)>0 THEN 'Draf' ELSE 'Belum terbit' END AS status_cp";
    }

    /** @param array{cari:string,mapel:int,kurikulum:int,status:string} $filter */
    public function halaman(array $filter, int $nomor = 1): array
    {
        $this->admin();
        $perHalaman = 25;
        $total = (int) $this->builder($filter)->countAllResults();
        $jumlah = max(1, (int) ceil($total / $perHalaman));
        $halaman = min(max(1, $nomor), $jumlah);
        $cp = $this->builder($filter)->select(self::kolom(), false)
            ->orderBy('m.nama', 'ASC')->orderBy('c.kode', 'ASC')->orderBy('c.id', 'ASC')
            ->limit($perHalaman, ($halaman - 1) * $perHalaman)
            ->get()->getResultArray();
        return ['cp' => $cp, 'cpTotal' => $total, 'cpHalaman' => $halaman,
            'cpPerHalaman' => $perHalaman, 'cpFilter' => $filter];
    }

    /**
     * Ekspor seluruh hasil filter, bukan hanya halaman yang sedang dibuka.
     * Batas fisik mencegah memori shared hosting membengkak.
     *
     * @param array{cari:string,mapel:int,kurikulum:int,status:string} $filter
     */
    public function eksporXlsx(array $filter): string
    {
        $this->admin();
        $rows = $this->builder($filter)->select(self::kolom(), false)
            ->orderBy('m.nama', 'ASC')->orderBy('c.kode', 'ASC')->orderBy('c.id', 'ASC')
            ->limit(5001)->get()->getResultArray();
        if (count($rows) > 5000) {
            throw new DomainException('Hasil ekspor melebihi 5.000 CP. Persempit filter sebelum mengunduh.');
        }
        return self::buatBerkas($rows);
    }

    /** Membangun XLSX menggunakan teks eksplisit, bukan formula yang dapat dieksekusi. */
    public static function buatBerkas(array $rows): string
    {
        $xlsx = new Spreadsheet();
        try {
            $sheet = $xlsx->getActiveSheet();
            $sheet->setTitle('Daftar CP');
            $labels = ['CP Operasional', 'Mata Pelajaran', 'Kurikulum', 'Edisi', 'Fase',
                'Elemen CP Aktif', 'Jumlah Elemen Aktif', 'Draf Elemen', 'Status', 'Jumlah Sumber CP'];
            foreach ($labels as $i => $label) {
                $sheet->setCellValueExplicit([$i + 1, 1], $label, DataType::TYPE_STRING);
            }
            foreach ($rows as $index => $row) {
                $aktif = (int) ($row['jumlah_elemen_aktif']
                    ?? (! empty($row['versi_aktif_id']) ? 1 : 0));
                $draf = (int) ($row['jumlah_elemen_draf']
                    ?? (! empty($row['versi_draf_id']) ? 1 : 0));
                $state = $row['status_cp'] ??
                    ($aktif > 0 ? 'Aktif' : ($draf > 0 ? 'Draf' : 'Belum terbit'));
                $values = [
                    $row['kode'], $row['mapel_nama'], $row['kurikulum_nama'],
                    $row['kurikulum_versi'], $row['fase'], $row['elemen'] ?? '',
                    (string) $aktif, (string) $draf, $state,
                    (string) ($row['jumlah_sumber'] ?? ''),
                ];
                foreach ($values as $i => $value) {
                    // Sel teks eksplisit mencegah formula injection pada ekspor XLSX.
                    $sheet->setCellValueExplicit([$i + 1, $index + 2], (string) $value, DataType::TYPE_STRING);
                }
            }
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            foreach (range('A', 'J') as $letter) {
                $sheet->getColumnDimension($letter)->setWidth($letter === 'F' ? 44 : 22);
            }
            $tempDir = is_dir(WRITEPATH . 'cache/') && is_writable(WRITEPATH . 'cache/')
                ? WRITEPATH . 'cache/' : sys_get_temp_dir();
            if (!is_dir($tempDir) || !is_writable($tempDir)) {
                throw new DomainException('Direktori sementara untuk ekspor CP tidak dapat ditulis.');
            }
            $tmp = tempnam($tempDir, 'cp-');
            if (!is_string($tmp)) {
                throw new DomainException('Tempat penyimpanan ekspor sementara tidak tersedia.');
            }
            try {
                (new Xlsx($xlsx))->save($tmp);
                $data = file_get_contents($tmp);
                if (!is_string($data)) {
                    throw new DomainException('File ekspor CP gagal dibuat.');
                }
                return $data;
            } finally {
                @unlink($tmp);
            }
        } finally {
            $xlsx->disconnectWorksheets();
        }
    }
}
