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
    private function builder(array $filter): BaseBuilder
    {
        $q = $this->db->table('ref_cp c')
            ->join('ref_mapel m', 'm.id=c.mapel_id')
            ->join('ref_kurikulum k', 'k.id=c.kurikulum_id')
            ->join('ref_fase f', 'f.id=c.fase_id')
            ->join('ref_cp_versi a', "a.cp_id=c.id AND a.status='aktif'", 'left')
            ->join('ref_cp_versi d', "d.cp_id=c.id AND d.status='draf'", 'left');
        if ($filter['mapel'] > 0) {
            $q->where('c.mapel_id', $filter['mapel']);
        }
        if ($filter['kurikulum'] > 0) {
            $q->where('c.kurikulum_id', $filter['kurikulum']);
        }
        if ($filter['status'] === 'aktif') {
            $q->where('a.id IS NOT NULL', null, false);
        } elseif ($filter['status'] === 'draf') {
            $q->where('d.id IS NOT NULL', null, false);
        } elseif ($filter['status'] === 'belum') {
            $q->where('a.id IS NULL', null, false);
        }
        if ($filter['cari'] !== '') {
            $q->groupStart()
                ->like('c.kode', $filter['cari'])
                ->orLike('m.nama', $filter['cari'])
                ->orLike('k.nama', $filter['cari'])
                ->orLike('a.elemen', $filter['cari'])
                ->orLike('d.elemen', $filter['cari'])
                ->groupEnd();
        }
        return $q;
    }

    private static function kolom(): string
    {
        return 'c.id,c.kode,m.nama AS mapel_nama,k.nama AS kurikulum_nama,' .
            'k.versi AS kurikulum_versi,f.kode AS fase,' .
            'a.id AS versi_aktif_id,a.nomor_versi AS versi_aktif,' .
            'd.id AS versi_draf_id,d.nomor_versi AS versi_draf,' .
            'COALESCE(a.elemen,d.elemen) AS elemen,' .
            'COALESCE(a.tanggal_berlaku,d.tanggal_berlaku) AS tanggal_berlaku';
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
        $xlsx = new Spreadsheet();
        try {
            $sheet = $xlsx->getActiveSheet();
            $sheet->setTitle('Daftar CP');
            $labels = ['Kode CP', 'Mata Pelajaran', 'Kurikulum', 'Edisi', 'Fase', 'Elemen CP',
                'Versi Aktif', 'Versi Draf', 'Status', 'Tanggal Berlaku'];
            foreach ($labels as $i => $label) {
                $sheet->setCellValueExplicit([$i + 1, 1], $label, DataType::TYPE_STRING);
            }
            foreach ($rows as $index => $row) {
                $state = $row['versi_aktif_id'] !== null ? 'Aktif'
                    : ($row['versi_draf_id'] !== null ? 'Draf' : 'Belum terbit');
                $values = [
                    $row['kode'], $row['mapel_nama'], $row['kurikulum_nama'],
                    $row['kurikulum_versi'], $row['fase'], $row['elemen'] ?? '',
                    $row['versi_aktif_id'] === null ? '' : 'v' . $row['versi_aktif'],
                    $row['versi_draf_id'] === null ? '' : 'v' . $row['versi_draf'],
                    $state, $row['tanggal_berlaku'] ?? '',
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
            $tmp = tempnam(WRITEPATH . 'cache/', 'cp-');
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
