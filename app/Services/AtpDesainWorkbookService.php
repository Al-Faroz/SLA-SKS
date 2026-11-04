<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Desain Excel ATP manual v2.5 berbasis tampilan workbook Peta Pembelajaran lama.
 * Tidak memindahkan metadata, header, sel input, dropdown, ataupun menambah rumus.
 */
final class AtpDesainWorkbookService
{
    private const NAVY = '12364A';
    private const NAVY_DARK = '0B293B';
    private const TEAL = '078783';
    private const WHITE = 'FFFFFF';
    private const TEXT = '17384C';
    private const SKY = 'E4F3FC';
    private const PALE = 'EAF4F6';
    private const YELLOW = 'FFF7B7';
    private const MINT = 'DAF2E7';

    private static function merge(Worksheet $sheet, string $range, string $text): void
    {
        [$start, $end] = Coordinate::rangeBoundaries($range);
        // Templat resmi sudah memiliki beberapa merge lama A:G atau A:C.
        // Lepaskan hanya merge yang berpotongan dengan area desain baru.
        foreach ($sheet->getMergeCells() as $merged) {
            if ($merged === $range) {
                continue;
            }
            [$mStart, $mEnd] = Coordinate::rangeBoundaries($merged);
            if ($start[0] <= $mEnd[0] && $end[0] >= $mStart[0]
                && $start[1] <= $mEnd[1] && $end[1] >= $mStart[1]) {
                $sheet->unmergeCells($merged);
            }
        }
        if (! in_array($range, $sheet->getMergeCells(), true)) {
            $sheet->mergeCells($range);
        }
        $cell = explode(':', $range)[0];
        $sheet->setCellValue($cell, $text);
    }

    private static function strip(Worksheet $sheet, string $range, string $fill, string $text,
        int $size = 11, bool $bold = true): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $fill]],
            'font' => ['name' => 'Aptos', 'size' => $size, 'bold' => $bold,
                'italic' => false, 'color' => ['rgb' => $text]],
            'alignment' => ['vertical' => 'center', 'wrapText' => true,
                'horizontal' => 'left', 'indent' => 1],
        ]);
    }

    private static function banner(Worksheet $sheet, string $range, string $label,
        string $fill, string $text, int $size, float $height): void
    {
        self::merge($sheet, $range, $label);
        self::strip($sheet, $range, $fill, $text, $size);
        $sheet->getRowDimension((int) preg_replace('/[^0-9].*$/', '',
            preg_replace('/^[A-Z]+/', '', explode(':', $range)[0])))->setRowHeight($height);
    }

    private static function height(Worksheet $sheet, array $rows): void
    {
        foreach ($rows as $row => $height) {
            $sheet->getRowDimension((int) $row)->setRowHeight((float) $height);
        }
    }

    private static function width(Worksheet $sheet, array $cols): void
    {
        foreach ($cols as $col => $size) {
            $sheet->getColumnDimension($col)->setWidth((float) $size);
        }
    }

    private static function alternating(Worksheet $sheet, string $start, string $end,
        int $from, int $to, string $color1 = self::SKY, string $color2 = 'F4F9FC'): void
    {
        for ($i = $from; $i <= $to; ++$i) {
            $fill = $i % 2 ? $color1 : $color2;
            $range = $start . $i . ':' . $end . $i;
            $sheet->getStyle($range)->applyFromArray([
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $fill]],
                'font' => ['name' => 'Aptos', 'size' => 10, 'italic' => false,
                    'color' => ['rgb' => self::TEXT]],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ]);
            $sheet->getRowDimension($i)->setRowHeight($to <= 32 ? 32 : 28);
        }
    }

    private static function semester(Worksheet $sheet, int $nomor): void
    {
        $kelas = $nomor <= 2 ? 'VII' : ($nomor <= 4 ? 'VIII' : 'IX');
        $sheet->setShowGridLines(false);
        self::width($sheet, ['A'=>16,'B'=>16,'C'=>20,'D'=>43,'E'=>58,
            'F'=>39,'G'=>25,'H'=>38,'I'=>13,'J'=>13]);

        // A2 sengaja dipertahankan: marker versi workbook dibaca parser.
        self::banner($sheet, 'A1:J1', 'PETA PEMBELAJARAN  —  SEMESTER ' . $nomor,
            self::NAVY, self::WHITE, 22, 60);
        self::strip($sheet, 'A2:J2', self::NAVY_DARK, 'B7E8E4', 10);
        self::height($sheet, [2=>25]);
        self::banner($sheet, 'A3:J3',
            'KELAS ' . $kelas . '   •   FASE D   •   STRUKTUR BAB → TP   •   FORMAT MANUAL',
            self::TEAL, self::WHITE, 12, 37);
        self::banner($sheet, 'A4:J4',
            'PANDUAN  |  Isi BAGIAN A (BAB), kemudian BAGIAN B (TP). Sel biru muda adalah area input Guru.',
            self::PALE, self::TEXT, 11, 38);
        self::banner($sheet, 'A5:J5',
            'PENTING  |  Nomor BAB berurutan; Kode TP dibuat server. Jangan mengubah header/posisi.',
            self::YELLOW, self::TEXT, 11, 38);

        self::banner($sheet, 'A6:C6', '20 BARIS BAB TERSEDIA', self::MINT, '086E58', 11, 40);
        self::banner($sheet, 'D6:G6', '80 BARIS TP TERSEDIA', 'E3EFFA', self::NAVY, 11, 40);
        self::banner($sheet, 'H6:J6', 'SEMESTER ' . $nomor . ' • KELAS ' . $kelas,
            self::NAVY, self::WHITE, 11, 40);

        // Template sebelumnya menggabung A7:C7; lepaskan agar pita melintang A:J.
        if (in_array('A7:C7', $sheet->getMergeCells(), true)) {
            $sheet->unmergeCells('A7:C7');
        }
        self::banner($sheet, 'A7:J7', 'BAGIAN A    •    DAFTAR BAB / POKOK BAHASAN',
            self::NAVY, self::WHITE, 14, 46);
        self::banner($sheet, 'A8:J8',
            '01  —  ISI NOMOR BAB, NAMA BAB, DAN DESKRIPSI SECARA BERURUTAN.',
            self::YELLOW, self::TEXT, 11, 34);
        self::banner($sheet, 'A9:J9',
            'Nama maks. 50 karakter; deskripsi maks. 225 karakter. Nomor BAB berlanjut pada pasangan semester sekelas.',
            self::PALE, self::TEXT, 10, 33);

        self::strip($sheet, 'A12:C12', self::TEAL, self::WHITE, 11);
        self::height($sheet, [12=>48]);
        self::alternating($sheet, 'A', 'C', 13, 32);
        self::banner($sheet, 'D12:J12', 'PANDUAN PENGISIAN BAGIAN A', self::NAVY_DARK,
            self::WHITE, 11, 48);
        self::banner($sheet, 'D14:J15',
            '1  ·  Nomor BAB dimulai 1 pada kelas baru, kemudian berlanjut pada semester pasangannya.',
            self::PALE, self::TEXT, 11, 32);
        self::banner($sheet, 'D17:J18',
            '2  ·  Gunakan nama BAB yang singkat, spesifik, dan mudah dipahami siswa.',
            'E5F7F0', self::TEXT, 11, 32);
        self::banner($sheet, 'D20:J21',
            '3  ·  Deskripsi BAB menjelaskan cakupan pokok bahasan secara ringkas.',
            self::PALE, self::TEXT, 11, 32);
        self::banner($sheet, 'D24:J25',
            'CONTOH PENOMORAN: BAB 1  →  BAB 2  →  BAB 3',
            self::YELLOW, self::NAVY, 11, 32);
        self::banner($sheet, 'D28:J29',
            'Sel biru muda adalah input Guru. Rencana media bukan tempat mengunggah berkas.',
            'E5F7F0', self::TEXT, 11, 32);

        self::banner($sheet, 'A34:J34', 'BAGIAN B    •    DAFTAR TUJUAN PEMBELAJARAN (TP)',
            self::NAVY, self::WHITE, 14, 48);
        self::banner($sheet, 'A35:J35',
            '02  —  HUBUNGKAN SETIAP TP KE NOMOR BAB PADA BAGIAN A.',
            self::YELLOW, self::NAVY, 11, 34);
        self::banner($sheet, 'A36:J36',
            'Semua TP wajib memiliki satu Materi: isi judul di F dan jenis media di G, termasuk Esensial.',
            self::PALE, self::TEXT, 11, 34);
        self::banner($sheet, 'A37:J37',
            'Kolom A pilih BAB; kolom B otomatis 1, 2, 3 per BAB; Kode TP dibuat server.',
            self::SKY, self::NAVY, 10, 34);

        self::strip($sheet, 'A38:H38', self::TEAL, self::WHITE, 11);
        self::strip($sheet, 'I38:J38', self::NAVY_DARK, self::WHITE, 10);
        self::height($sheet, [38=>58]);
        self::alternating($sheet, 'A', 'H', 39, 118);
        self::strip($sheet, 'C39:C118', self::MINT, '125441', 10, false);
        self::strip($sheet, 'F39:F118', 'E0ECFC', self::TEXT, 10, false);
        self::strip($sheet, 'I39:J118', self::PALE, self::TEXT, 10, false);
        // Dropdown D39:D118/H39:H118 dibiarkan utuh.
    }

    private static function ringkasan(Worksheet $sheet): void
    {
        $sheet->setShowGridLines(false);
        self::width($sheet, ['A'=>34,'B'=>79,'C'=>23,'D'=>23]);
        self::banner($sheet, 'A1:D1', 'RINGKASAN  —  IDENTITAS AKADEMIK',
            self::NAVY, self::WHITE, 22, 59);
        self::banner($sheet, 'A2:D2',
            'SLA-SKS   /   KONTEKS GURU  ·  GEN  ·  MAPEL  ·  KURIKULUM',
            self::TEAL, self::WHITE, 11, 37);
        self::strip($sheet, 'A3:D3', self::NAVY_DARK, self::WHITE, 12);
        self::height($sheet, [3=>42]);
        for ($i = 4; $i <= 15; ++$i) {
            self::strip($sheet, 'A'.$i,
                $i%2 ? self::PALE : 'DCEEF1', self::NAVY, 10);
            self::strip($sheet, 'B'.$i,
                $i%2 ? self::WHITE : 'F5F9FA', self::TEXT, 11, false);
            $sheet->getRowDimension($i)->setRowHeight(40);
        }
        self::banner($sheet, 'C4:D4', 'ALUR KERJA ATP', self::TEAL, self::WHITE, 12, 40);
        $alur = [
            '1    BACA IDENTITAS', '2    CEK REFERENSI CP', '3    BUKA SEMESTER',
            '4    ISI DAFTAR BAB', '5    ISI DAFTAR TP', '6    SIMPAN XLSX',
            '7    SIAP UNTUK IMPOR', '8    PRATINJAU (TAHAP SELANJUTNYA)',
            '9    KONFIRMASI (TAHAP SELANJUTNYA)', 'FORMAT: 6 SEMESTER', 'AREA: BAB DAN TP',
        ];
        foreach ($alur as $i => $label) {
            $row = $i + 5;
            self::banner($sheet, 'C'.$row.':D'.$row, $label,
                $row%2 ? 'DFF1EB' : 'EFF7FA', self::NAVY, 10, 40);
        }
        self::banner($sheet, 'A17:D17',
            'INFORMASI SAJA  •  Hak Guru dan penugasan Gen–Mapel tetap diverifikasi server saat impor.',
            self::YELLOW, self::NAVY, 11, 45);
    }

    private static function referensi(Worksheet $sheet): void
    {
        $sheet->setShowGridLines(false);
        self::width($sheet, ['A'=>16,'B'=>39,'C'=>79,'D'=>16,'E'=>24]);
        self::banner($sheet, 'A1:E1', 'REFERENSI CAPAIAN PEMBELAJARAN (CP)',
            self::NAVY, self::WHITE, 22, 59);
        self::banner($sheet, 'A2:E2', 'FASE D  •  ELEMEN DAN RUMUSAN CP AKTIF  •  SUMBER SERVER',
            self::TEAL, self::WHITE, 11, 36);
        self::banner($sheet, 'A3:E3',
            'BACA SAJA  |  CP diisi otomatis dan bukan tempat mengubah CP akademik.',
            self::PALE, self::NAVY, 11, 40);
        self::banner($sheet, 'A4:E4',
            'Gunakan rujukan CP yang ditetapkan Admin dan cocokkan dengan TP yang disusun.',
            self::YELLOW, self::TEXT, 11, 36);
        self::strip($sheet, 'A6:E6', self::TEAL, self::WHITE, 11);
        self::height($sheet, [6=>46]);
        self::alternating($sheet, 'A', 'E', 7, 106);
        // Rumusan CP dapat memuat banyak butir panjang (SKI: 10 butir).
        // Berikan ruang baca, bukan baris setinggi 28 pt yang memotong teks.
        // Excel membatasi tinggi maksimum baris sekitar 409 pt.
        for ($row = 7; $row <= 106; ++$row) {
            $rumusan = (string) ($sheet->getCell('C' . $row)->getValue() ?? '');
            if ($rumusan === '') {
                continue;
            }
            $lines = preg_split('/\\r\\n|\\r|\\n/u', $rumusan) ?: [];
            $estimated = 0;
            foreach ($lines as $line) {
                $estimated += max(1, (int) ceil(mb_strlen($line) / 74));
            }
            $height = min(400.0, max(58.0, (float) ($estimated * 16 + 18)));
            $sheet->getRowDimension($row)->setRowHeight($height);
            $sheet->getStyle('A' . $row . ':E' . $row)
                ->getAlignment()->setVertical('top');
            $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
        }
    }

    private static function panduan(Worksheet $sheet): void
    {
        $sheet->setShowGridLines(false);
        self::width($sheet, ['A'=>25,'B'=>19,'C'=>19,'D'=>19,'E'=>19,
            'F'=>19,'G'=>19,'H'=>16,'I'=>16,'J'=>16]);
        self::banner($sheet, 'A1:G1', 'PANDUAN PENGISIAN ATP  —  MULAI DI SINI',
            self::NAVY, self::WHITE, 22, 58);
        self::banner($sheet, 'A2:J2',
            'SLA-SKS v2.5  •  WORKBOOK MANUAL  •  TANPA MACRO',
            self::TEAL, self::WHITE, 12, 36);
        self::banner($sheet, 'A3:J3',
            '01   PERIKSA IDENTITAS GURU, GEN, MAPEL DAN KURIKULUM PADA RINGKASAN.',
            self::PALE, self::NAVY, 11, 40);
        self::banner($sheet, 'A4:J4',
            '02   PELAJARI RUJUKAN CP RESMI PADA REFERENSI CP.',
            'E1F2EB', self::NAVY, 11, 40);
        self::banner($sheet, 'A5:J5',
            '03   ISI BAGIAN A (BAB), LALU BAGIAN B (TP) PADA SEMESTER 1–6.',
            self::YELLOW, self::NAVY, 11, 40);
        for ($i=6; $i<=16; ++$i) {
            $old = (string) $sheet->getCell('A'.$i)->getValue();
            self::banner($sheet, 'A'.$i.':J'.$i, $old,
                $i%2 ? 'F1F7F9' : self::WHITE, self::TEXT, 10, 40);
        }
        self::banner($sheet, 'A18:J18',
            'PENTING: CONTOH MAT VII adalah ilustrasi dan tidak akan diimpor.',
            self::YELLOW, self::NAVY, 11, 45);
    }

    private static function contoh(Worksheet $sheet): void
    {
        $sheet->setShowGridLines(false);
        self::width($sheet, ['A'=>19,'B'=>34,'C'=>40,'D'=>20,'E'=>37,
            'F'=>42,'G'=>18,'H'=>18,'I'=>16,'J'=>16]);
        self::banner($sheet, 'A1:J1', 'CONTOH PENGISIAN  —  MATEMATIKA KELAS VII',
            self::NAVY, self::WHITE, 21, 59);
        self::banner($sheet, 'A2:J2', 'ILUSTRASI SAJA  •  TIDAK AKAN DIIMPOR',
            self::TEAL, self::WHITE, 12, 37);
        self::banner($sheet, 'A3:J3',
            'Pelajari nomor BAB, kode TP dan jenis TP. Contoh ini bukan data kurikulum nyata.',
            self::YELLOW, self::NAVY, 11, 38);
        self::strip($sheet, 'A5:C5', self::TEAL, self::WHITE, 11);
        self::strip($sheet, 'A10:F10', self::TEAL, self::WHITE, 11);
        self::height($sheet, [5=>44,10=>45]);
    }

    public static function terapkan(Spreadsheet $buku): void
    {
        self::panduan($buku->getSheetByName('MULAI DI SINI'));
        self::ringkasan($buku->getSheetByName('RINGKASAN'));
        self::referensi($buku->getSheetByName('REFERENSI CP'));
        for ($n=1; $n<=6; ++$n) {
            self::semester($buku->getSheetByName('SEMESTER '.$n), $n);
        }
        self::contoh($buku->getSheetByName('CONTOH MAT VII'));
    }
}
