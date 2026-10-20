<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

/**
 * Dokumen XLSX Administrasi Pengelola/Siswa.
 * Template dan ekspor terpisah dari impor; tidak pernah memuat hash/sandi/sesi.
 */
final class AkunXlsxService
{
    public const VERSI_TEMPLATE = 'AKUN-V1';
    private const BATAS_EKSPOR = 3000;

    /** @return list<string> */
    public static function kolom(string $jenis, bool $template): array
    {
        if (! PenggunaAdminService::validasiJenis($jenis)) {
            throw new DomainException('Jenis akun tidak dikenal.');
        }

        if ($template) {
            return $jenis === 'siswa'
                ? ['nisn', 'nama', 'kode_gen', 'semester_mulai', 'sandi_awal']
                : ['username', 'nama', 'peran', 'sandi_awal'];
        }

        return $jenis === 'siswa'
            ? ['nisn', 'nama', 'kode_gen', 'semester_mulai', 'status']
            : ['username', 'nama', 'peran', 'status'];
    }

    /** Netralisasi formula injection walau sel dibuat bertipe teks. */
    public static function amanUntukXlsx(string $nilai): string
    {
        $nilai = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $nilai) ?? '';
        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $nilai) === 1) {
            return "'" . $nilai;
        }

        return $nilai;
    }

    private function admin(): array
    {
        $akun = (new AuthService())->penggunaSaatIni();
        if ($akun === null || $akun['peran_aktif'] !== 'admin'
            || (int) $akun['wajib_ganti_sandi'] !== 0) {
            throw new DomainException('Hanya Admin aktif yang dapat mengunduh data akun.');
        }

        return $akun;
    }

    /**
     * Data sesuai filter seluruh halaman, dibatasi ukuran untuk mencegah beban shared hosting.
     * @return list<array<string,string>>
     */
    private function dataEkspor(string $jenis, string $cari, string $status, ?int $genFilter): array
    {
        $service = new PenggunaAdminService();
        $data = $service->daftar($jenis, $cari, $status, 1, $genFilter);
        if ($data['total'] > self::BATAS_EKSPOR) {
            throw new DomainException('Jumlah akun melebihi batas ekspor 3000. Persempit filter terlebih dahulu.');
        }

        $genKode = [];
        foreach ($data['gen'] as $gen) {
            $genKode[(int) $gen['id']] = (string) $gen['kode'];
        }

        $hasil = [];
        for ($halaman = 1; $halaman <= max(1, (int) ceil($data['total'] / $data['perHalaman'])); ++$halaman) {
            $daftar = $halaman === 1 ? $data : $service->daftar($jenis, $cari, $status, $halaman, $genFilter);
            foreach ($daftar['pengguna'] as $akun) {
                if ($jenis === 'siswa') {
                    $hasil[] = [
                        'nisn' => (string) $akun['username'],
                        'nama' => (string) $akun['nama'],
                        'kode_gen' => $akun['angkatan_id'] === null ? ''
                            : ($genKode[(int) $akun['angkatan_id']] ?? ''),
                        'semester_mulai' => $akun['semester_mulai'] === null ? ''
                            : (string) $akun['semester_mulai'],
                        'status' => (string) $akun['status'],
                    ];
                } else {
                    $hasil[] = [
                        'username' => (string) $akun['username'],
                        'nama' => (string) $akun['nama'],
                        'peran' => implode('+', $akun['daftar_peran']),
                        'status' => (string) $akun['status'],
                    ];
                }
            }
        }

        return $hasil;
    }

    /**
     * Workbook hanya memuat kolom yang diizinkan.
     * Seluruh kolom teks eksplisit termasuk NISN agar nol di depan tidak hilang.
     *
     * @param list<array<string,string>> $rows
     */
    public static function buatBuku(string $jenis, array $rows, bool $template): Spreadsheet
    {
        $kolom = self::kolom($jenis, $template);
        $excel = new Spreadsheet();
        $excel->getProperties()
            ->setCreator('SLA-SKS')->setTitle(($template ? 'Template ' : 'Ekspor ') . ucfirst($jenis))
            ->setSubject('Administrasi akun v2.3');

        $sheet = $excel->getActiveSheet();
        $sheet->setTitle(ucfirst($jenis));
        $sheet->freezePane('A2');
        $sheet->getSheetView()->setZoomScale(90);

        foreach ($kolom as $i => $nama) {
            $sheet->setCellValueExplicit([$i + 1, 1], $nama, DataType::TYPE_STRING);
        }

        $maxKolom = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($kolom));
        $sheet->getStyle('A1:' . $maxKolom . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '176C50']],
            'alignment' => ['vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getStyle('A1:' . $maxKolom . max(301, count($rows) + 1))
            ->getNumberFormat()->setFormatCode('@');

        foreach ($kolom as $i => $nama) {
            $lebar = match ($nama) {
                'nama' => 38,
                'nisn', 'username' => 23,
                'kode_gen', 'semester_mulai' => 22,
                'sandi_awal' => 26,
                default => 26,
            };
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($lebar);
        }

        foreach ($rows as $i => $record) {
            foreach ($kolom as $c => $nama) {
                $nilai = (string) ($record[$nama] ?? '');
                $sheet->setCellValueExplicit([$c + 1, $i + 2],
                    self::amanUntukXlsx($nilai), DataType::TYPE_STRING);
            }
        }

        if ($template) {
            // Format kolom tetap teks sampai baris 300 untuk menangani NISN nol di depan.
            $sheet->getStyle('A2:' . $maxKolom . '301')->getNumberFormat()->setFormatCode('@');
            $petunjuk = $excel->createSheet();
            $petunjuk->setTitle('Petunjuk');
            $arahan = [
                'Template SLA-SKS ' . self::VERSI_TEMPLATE . ' — ' . ucfirst($jenis),
                'Isikan data hanya pada sheet ' . ucfirst($jenis) . '. Jangan ubah nama sheet/header.',
                'Maksimal 300 baris data per impor; satu akun per baris.',
                'Kolom sandi_awal hanya untuk AKUN BARU, minimal 8 karakter; kosongkan untuk update.',
                'Jangan menyimpan file berisi sandi awal di folder publik atau membagikannya lewat grup.',
                $jenis === 'siswa'
                    ? 'NISN tepat 10 angka dengan format TEKS. kode_gen harus kode Gen yang terdaftar.'
                    : 'peran: admin, guru, pimpinan, admin+guru, admin+pimpinan, atau guru+pimpinan.',
                $jenis === 'siswa'
                    ? 'semester_mulai: kosong untuk mengikuti Gen, atau 1 sampai 6.'
                    : 'Baris dengan username sudah ada hanya memperbarui nama/peran; sandi akun lama tidak diubah.',
                'Pratinjau impor tidak menyimpan akun; periksa hasil sebelum mengonfirmasi.',
            ];
            foreach ($arahan as $i => $teks) {
                $petunjuk->setCellValueExplicit([1, $i + 1], $teks, DataType::TYPE_STRING);
            }
            $petunjuk->getColumnDimension('A')->setWidth(110);
            $petunjuk->getStyle('A1')->getFont()->setBold(true)->getColor()->setRGB('176C50');
            $petunjuk->getRowDimension(1)->setRowHeight(26);
        } else {
            $sheet->setAutoFilter('A1:' . $maxKolom . max(1, count($rows) + 1));
        }

        $excel->setActiveSheetIndex(0);

        return $excel;
    }

    /**
     * @return array{nama:string,isi:string,jumlah:int}
     */
    public function siapkanUnduhan(string $jenis, bool $template, string $cari = '',
        string $status = '', ?int $genFilter = null): array
    {
        $admin = $this->admin();
        if (! PenggunaAdminService::validasiJenis($jenis)) {
            throw new DomainException('Jenis akun tidak valid.');
        }

        $cari = mb_substr(trim($cari), 0, 80);
        if (! in_array($status, ['', 'aktif', 'nonaktif'], true)) {
            throw new DomainException('Filter status tidak valid.');
        }
        if ($genFilter !== null && ($jenis !== 'siswa' || $genFilter < 0)) {
            throw new DomainException('Filter Gen tidak valid.');
        }

        $baris = $template ? [] : $this->dataEkspor($jenis, $cari, $status, $genFilter);
        $buku = self::buatBuku($jenis, $baris, $template);
        $temp = tempnam(WRITEPATH, 'akun_xlsx_');
        if ($temp === false) {
            throw new RuntimeException('Berkas XLSX sementara tidak dapat dibuat.');
        }

        try {
            $writer = new Xlsx($buku);
            $writer->save($temp);
            $bytes = file_get_contents($temp);
            if ($bytes === false || $bytes === '') {
                throw new RuntimeException('Pembuatan XLSX gagal.');
            }
        } finally {
            $buku->disconnectWorksheets();
            @unlink($temp);
        }

        db_connect()->table('log_aktivitas')->insert([
            'pengguna_id' => (int) $admin['id'],
            'peran_aktif' => 'admin',
            'peristiwa' => $template ? 'template_akun_diunduh' : 'ekspor_akun',
            'catatan' => 'Jenis ' . $jenis . '; jumlah ' . count($baris)
                . '; filter status=' . ($status === '' ? 'semua' : $status)
                . '; Gen=' . ($genFilter === null ? 'semua' : (string) $genFilter)
                . '; pencarian=' . ($cari === '' ? 'tanpa' : 'digunakan'),
            'alamat_ip' => service('request')->getIPAddress(),
            'waktu' => gmdate('Y-m-d H:i:s'),
        ]);

        return [
            'nama' => ($template ? 'template-' : 'ekspor-') . $jenis . '-sla-sks.xlsx',
            'isi' => $bytes,
            'jumlah' => count($baris),
        ];
    }
}
