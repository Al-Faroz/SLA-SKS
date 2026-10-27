<?php

declare(strict_types=1);

use App\Services\AtpExcelParserService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

final class AtpExcelParserServiceTest extends TestCase
{
    private function buku(): Spreadsheet
    {
        $buku = IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
        $s = $buku->getSheetByName('RINGKASAN');
        $s->setCellValue('B5', 'GEN2025');
        $s->setCellValue('B9', 'SKI');
        return $buku;
    }

    private function bab(Spreadsheet $buku, int $semester, int $baris, int $nomor): void
    {
        $s = $buku->getSheetByName('SEMESTER ' . $semester);
        $s->setCellValue('A' . $baris, $nomor);
        $s->setCellValue('B' . $baris, 'Sejarah Perkembangan Islam');
        $s->setCellValue('C' . $baris, 'Contoh deskripsi BAB akademik');
    }

    private function tp(Spreadsheet $buku, int $semester, string $kode, string $jenis = 'NonEsensial',
        int $urutan = 1, int $nomorBab = 1): void
    {
        $s = $buku->getSheetByName('SEMESTER ' . $semester);
        $s->setCellValue('A39', $nomorBab);
        $s->setCellValue('B39', $urutan);
        $s->setCellValue('C39', $kode);
        $s->setCellValue('D39', $jenis);
        $s->setCellValue('E39', 'Menganalisis peristiwa');
        $s->setCellValue('F39', 'Mengenali sebab dan akibat peristiwa penting');
        $s->setCellValue('I39', 'Pemahaman Konsep');
    }

    public function testWorkbookKosongMemilikiHasilNol(): void
    {
        $buku = $this->buku();
        try {
            $r = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
            self::assertSame(0, $r['jumlah_bab']);
            self::assertSame(0, $r['jumlah_tp']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testSatuBabSatuTpDibacaDanContohTidakDiimpor(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.8.1.1');
            $r = AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025', ['Pemahaman Konsep']);
            self::assertSame(1, $r['jumlah_bab']);
            self::assertSame(1, $r['jumlah_tp']);
            self::assertSame('SKI.8.1.1', $r['tp'][0]['kode']);
            self::assertSame('non_esensial', $r['tp'][0]['jenis']);
            self::assertSame(39, $r['tp'][0]['baris']);
            self::assertSame(4, $r['bab'][0]['semester']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testKodeTpSalahDitolakDenganLokasi(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.9.1.1');
            $this->expectExceptionMessage('SEMESTER 4 baris 39');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testTpTanpaBabDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->tp($buku, 4, 'SKI.8.1.1');
            $this->expectExceptionMessage('nomor BAB tidak ada');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testDuplikatBabDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->bab($buku, 4, 14, 1);
            $this->expectExceptionMessage('nomor BAB ganda');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testSemesterSebelumMulaiGenDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 3, 13, 1);
            $this->expectExceptionMessage('Gen ini belum dimulai');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testIdentitasGenLainDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->expectExceptionMessage('Identitas Gen/Mapel');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN-LAIN');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testHeaderYangDiubahDitolak(): void
    {
        $buku = $this->buku();
        try {
            $buku->getSheetByName('SEMESTER 4')->setCellValue('C38', 'Kolom Lain');
            $this->expectExceptionMessage('SEMESTER 4!C38');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testRumusTersembunyiDitolak(): void
    {
        $buku = $this->buku();
        try {
            $buku->getSheetByName('CONTOH MAT VII')->setCellValue('J20', '=1+1');
            $this->expectExceptionMessage('Rumus tidak diizinkan');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testTpEsensialDenganMateriDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.8.1.1', 'Esensial');
            $buku->getSheetByName('SEMESTER 4')->setCellValue('G39', 'Materi yang dilarang');
            $this->expectExceptionMessage('Esensial');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testElemenCpAsingDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->bab($buku, 4, 13, 1);
            $this->tp($buku, 4, 'SKI.8.1.1');
            $this->expectExceptionMessage('elemen CP bukan elemen aktif');
            AtpExcelParserService::bacaBuku($buku, 'SKI', 4, 'GEN2025', ['Keterampilan Proses']);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testFileBukanZipDitolak(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'atp_invalid_');
        self::assertIsString($path);
        try {
            file_put_contents($path, 'bukan file Excel');
            $this->expectExceptionMessage('bukan XLSX');
            AtpExcelParserService::baca($path, 'SKI', 4, 'GEN2025');
        } finally {
            @unlink($path);
        }
    }
}
