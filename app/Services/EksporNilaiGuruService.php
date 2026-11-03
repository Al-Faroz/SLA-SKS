<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DomainException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

/**
 * A7: ekspor XLSX dari hasil rekap tersimpan. Read-only, terotorisasi G1
 * pada setiap batch, tanpa memasukkan jawaban ujian/kredensial.
 * Nilai Pra-Semester memiliki halaman dan file ekspor tersendiri.
 */
final class EksporNilaiGuruService
{
    private const MAKS_BARIS = 15000;
    private const BATCH = 500;
    private RekapNilaiTpGuruService $rekap;

    /** @param (callable():?array)|null $akun */
    public function __construct(?BaseConnection $db = null, ?callable $akun = null)
    {
        $this->rekap = new RekapNilaiTpGuruService($db, $akun);
    }

    /**
     * Teks (termasuk =SUM(1,1), +, -, @) harus disimpan sebagai OOXML string,
     * tidak pernah melalui inferensi formula PhpSpreadsheet.
     */
    private static function teks(Worksheet $sheet, string $sel, mixed $nilai): void
    {
        $sheet->setCellValueExplicit($sel, (string) ($nilai ?? ''), DataType::TYPE_STRING);
    }

    private static function angka(Worksheet $sheet, string $sel, mixed $nilai): void
    {
        if ($nilai === null) return; // kosong adalah kosong, bukan nol
        $sheet->setCellValue($sel, round((float) $nilai, 2));
        $sheet->getStyle($sel)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
    }

    /** @param list<string> $label */
    private static function header(Worksheet $sheet, int $row, array $label): void
    {
        foreach ($label as $i => $text) {
            self::teks($sheet, Coordinate::stringFromColumnIndex($i + 1) . $row, $text);
        }
        $end = Coordinate::stringFromColumnIndex(count($label));
        $sheet->getStyle('A' . $row . ':' . $end . $row)->applyFromArray([
            'font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'125C4A']],
            'alignment'=>['vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(32);
        $sheet->freezePane('C' . ($row + 1));
    }

    private static function amanNama(string $nilai): string
    {
        $s = preg_replace('/[^A-Za-z0-9_-]+/', '-', $nilai) ?? '';
        $s = trim($s, '-');
        return substr($s !== '' ? $s : 'GEN', 0, 36);
    }

    private static function labelJalur(?string $jalur): string
    {
        return match ($jalur) {
            'lulus_diagnostik' => 'Lulus Diagnostik',
            'belajar_wajib' => 'Belajar wajib',
            default => 'Belum diketahui',
        };
    }

    private static function komponen(Worksheet $sheet, string $cell, array $row, string $field): void
    {
        if ($row['jenis_tp'] === 'esensial') {
            self::teks($sheet, $cell, '—');
            return;
        }
        if ($row['jalur'] === 'lulus_diagnostik'
            && in_array($field, ['nilai_resume','nilai_hots'], true)) {
            self::teks($sheet, $cell, 'Dilewati');
            return;
        }
        self::angka($sheet, $cell, $row[$field]);
    }

    /** Roster menjamin semua siswa tetap tercantum walaupun tidak ada TP. */
    private function roster(Spreadsheet $book, array $pilihan): int
    {
        $sheet = new Worksheet($book, 'DAFTAR SISWA');
        $book->addSheet($sheet);
        self::teks($sheet,'A1','DAFTAR SISWA GEN — IDENTITAS MINIMAL');
        self::teks($sheet,'A2','Setiap siswa Gen tercantum meskipun belum mempunyai nilai atau TP.');
        self::header($sheet,4,['No.','Nama Siswa']);
        $students = $this->rekap->daftarSiswa($pilihan);
        $row = 5;
        foreach ($students as $i => $siswa) {
            $sheet->setCellValue('A'.$row, $i + 1);
            self::teks($sheet,'B'.$row, $siswa['nama']);
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(48);
        if ($row > 5) $sheet->setAutoFilter('A4:B'.($row-1));
        return count($students);
    }

    /**
     * Tabel nilai per siswa×TP: NULL tetap kosong; skor 0 sebagai numeric 0.
     * @return int jumlah baris nilai
     */
    private function semester(Spreadsheet $book, array $pilihan, int $semester,
        string $jenisTp, ?int $babId): int
    {
        $sheet = new Worksheet($book, 'SEMESTER '.$semester);
        $book->addSheet($sheet);
        self::teks($sheet,'A1','NILAI TP — SEMESTER '.$semester);
        self::teks($sheet,'A2','Jenis TP: '.($jenisTp === 'semua' ? 'Semua' : ($jenisTp === 'esensial' ? 'Esensial' : 'NonEsensial')).'; kosong ≠ angka nol.');
        self::header($sheet,4,[
            'No.','Nama Siswa','Semester','No. BAB','Nama BAB',
            'Kode TP','Nama TP','Jenis TP','Progres TP','Jalur',
            'Resume','HOTS','Sumatif Terbaik','Nilai TP','Status Nilai',
        ]);
        $data = $this->rekap->rekap($pilihan, $semester, 1, self::BATCH,
            $jenisTp,$babId);
        if ($data['total'] > self::MAKS_BARIS) {
            throw new DomainException('Jumlah baris rekap melebihi batas keamanan ekspor.');
        }
        $row = 5;
        for ($page = 1; $page <= $data['jumlah_halaman']; $page++) {
            if ($page !== 1) {
                // Service memeriksa ulang penugasan G1 setiap batch.
                $lanjut = $this->rekap->rekap($pilihan, $semester, $page, self::BATCH,
                    $jenisTp,$babId);
                if ($lanjut['total'] !== $data['total']) {
                    throw new DomainException('Data nilai berubah ketika ekspor. Silakan ulangi unduhan.');
                }
                $batch = $lanjut['baris'];
            } else {
                $batch = $data['baris'];
            }
            foreach ($batch as $nilai) {
                $sheet->setCellValue('A'.$row, $row - 4);
                self::teks($sheet,'B'.$row,$nilai['nama_siswa']);
                $sheet->setCellValue('C'.$row,$semester);
                if ($nilai['nomor_bab'] !== null) {
                    $sheet->setCellValue('D'.$row,(int) $nilai['nomor_bab']);
                }
                foreach ([
                    'E'=>'nama_bab','F'=>'kode_tp','G'=>'nama_tp',
                ] as $col => $field) self::teks($sheet,$col.$row,$nilai[$field]);
                $esensial = $nilai['jenis_tp'] === 'esensial';
                self::teks($sheet,'H'.$row,$esensial?'Esensial':'NonEsensial');
                self::teks($sheet,'I'.$row,$esensial?'Informasi saja':
                    str_replace('_',' ',(string) ($nilai['status_progres'] ?? 'Belum mulai')));
                self::teks($sheet,'J'.$row,$esensial?'—':
                    self::labelJalur($nilai['jalur']));
                foreach ([
                    'K'=>'nilai_resume','L'=>'nilai_hots',
                    'M'=>'nilai_sumatif','N'=>'nilai_tp',
                ] as $col => $field) self::komponen($sheet,$col.$row,$nilai,$field);
                self::teks($sheet,'O'.$row,
                    RekapNilaiTpGuruService::labelNilai($nilai['status_nilai'],$esensial));
                $row++;
            }
        }
        $sheet->getColumnDimension('A')->setWidth(9);
        $sheet->getColumnDimension('B')->setWidth(34);
        foreach (['C','D'] as $col) $sheet->getColumnDimension($col)->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(33);
        $sheet->getColumnDimension('F')->setWidth(19);
        $sheet->getColumnDimension('G')->setWidth(42);
        $sheet->getColumnDimension('H')->setWidth(17);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(22);
        foreach (['K','L','M','N'] as $col) $sheet->getColumnDimension($col)->setWidth(17);
        $sheet->getColumnDimension('O')->setWidth(24);
        if ($row > 5) $sheet->setAutoFilter('A4:O'.($row-1));
        return $row - 5;
    }

    /**
     * @param array<string,mixed> $pilihan Dipilih hanya dari penugasan aktif G1.
     * @param int $semester 0 = enam semester, 1..6 = satu semester.
     * @return array{nama:string,isi:string}
     */
    public function unduh(array $pilihan, int $semester = 0,
        string $jenisTp = 'semua', ?int $babId = null): array
    {
        if ($semester < 0 || $semester > 6
            || !in_array($jenisTp,['semua','esensial','non_esensial'],true)
            || ($babId !== null && ($babId < 1 || $semester === 0))) {
            throw new DomainException('Filter ekspor nilai TP tidak valid.');
        }
        // Otorisasi pada roster dan untuk setiap halaman/semester dilakukan lagi
        // meski Controller telah memverifikasi pilihan dropdown.
        $book = new Spreadsheet();
        $file = null;
        try {
            $info = $book->getActiveSheet();
            $info->setTitle('INFORMASI');
            self::teks($info,'A1','REKAP NILAI TP — SLA-SKS v2.5');
            $info->getStyle('A1')->getFont()->setSize(16)->setBold(true)
                ->getColor()->setRGB('125C4A');
            self::teks($info,'A3','Gen');
            self::teks($info,'B3',$pilihan['nama_gen'] ?? '');
            self::teks($info,'A4','Mata Pelajaran');
            self::teks($info,'B4',$pilihan['nama_mapel'] ?? '');
            self::teks($info,'A5','Cakupan');
            self::teks($info,'B5',$semester === 0 ? 'Semua semester 1–6':'Semester '.$semester);
            self::teks($info,'A6','Waktu ekspor (UTC)');
            self::teks($info,'B6',gmdate('Y-m-d H:i:s'));
            self::teks($info,'A8','KETERANGAN');
            self::teks($info,'A9','Nilai angka 0 adalah skor sah. Sel kosong berarti nilai belum tersedia.');
            self::teks($info,'A10','Aktivitas Dilewati hanya untuk jalur lulus Diagnostik.');
            self::teks($info,'A11','TP Esensial ditampilkan tanpa nilai.');
            self::teks($info,'A12','Nilai TP Sementara belum lengkap: menunggu nilai Guru.');
            self::teks($info,'A13','Nilai Pra-Semester tersedia pada modul rekap/ekspor terpisah.');
            self::teks($info,'A14','Tidak ada Nilai Akhir Semester; nilai TP mandiri per tujuan.');
            $info->getColumnDimension('A')->setWidth(27);
            $info->getColumnDimension('B')->setWidth(45);
            $countSiswa = $this->roster($book,$pilihan);
            $countBaris = 0;
            $semesterPilihan = $semester === 0 ? range(1,6) : [$semester];
            foreach ($semesterPilihan as $s) {
                $countBaris += $this->semester($book,$pilihan,$s,$jenisTp,$babId);
                if ($countBaris > self::MAKS_BARIS) {
                    throw new DomainException('Jumlah baris semua semester melebihi batas ekspor.');
                }
            }
            self::teks($info,'A16','Jumlah siswa Gen');
            $info->setCellValue('B16',$countSiswa);
            self::teks($info,'A17','Jumlah baris siswa–TP');
            $info->setCellValue('B17',$countBaris);
            $book->setActiveSheetIndex(0);
            $file = tempnam(sys_get_temp_dir(),'slaks_xlsx_');
            if ($file === false) throw new RuntimeException('Penyimpanan sementara ekspor gagal.');
            (new Xlsx($book))->save($file);
            $bytes = file_get_contents($file);
            if ($bytes === false || strlen($bytes) < 100) {
                throw new RuntimeException('Berkas ekspor kosong atau gagal dibaca.');
            }
            return [
                'nama'=>'NILAI_TP_'.self::amanNama((string)($pilihan['kode_gen'] ?? 'GEN'))
                    .'_'.self::amanNama((string)($pilihan['kode_mapel'] ?? 'MAPEL'))
                    .'_'.($semester === 0 ? 'SEMESTER_1-6' : 'SEMESTER_'.$semester)
                    .'_'.gmdate('Ymd_His').'.xlsx',
                'isi'=>$bytes,
            ];
        } finally {
            $book->disconnectWorksheets();
            if ($file !== null && is_file($file)) @unlink($file);
        }
    }
}
