<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class AtpManualTemplateTest extends TestCase
{
    private const BERKAS = 'docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';
    private const VERSI = 'SLA-SKS-ATP-MANUAL-V25-3';

    /**
     * PhpSpreadsheet may return inline-string cells as RichText.
     * Cast populated text to its plain string representation before assertions.
     */
    public function testWorkbookManualSesuaiKontrak(): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::BERKAS;
        self::assertFileExists($path);
        $buku = IOFactory::load($path);
        self::assertSame(self::VERSI,
            (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue(),
            'File master di docs/ wajib V25-3, bukan hanya dikonversi saat diunduh.');
        \App\Services\AtpTemplateLayoutService::terapkan($buku);
        \App\Services\AtpOtomatisTpService::terapkan($buku);
        try {
            self::assertSame([
                'MULAI DI SINI', 'RINGKASAN', 'REFERENSI CP',
                'SEMESTER 1', 'SEMESTER 2', 'SEMESTER 3',
                'SEMESTER 4', 'SEMESTER 5', 'SEMESTER 6', 'CONTOH MAT VII',
            ], $buku->getSheetNames());
            self::assertSame(self::VERSI, (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue());
            for ($n = 1; $n <= 6; ++$n) {
                $sheet = $buku->getSheetByName('SEMESTER ' . $n);
                self::assertNotNull($sheet);
                self::assertSame(self::VERSI, (string) $sheet->getCell('A2')->getValue());
                self::assertSame('Nomor BAB', (string) $sheet->getCell('A12')->getValue());
                self::assertSame('Nama BAB (maks. 50)', (string) $sheet->getCell('B12')->getValue());
                self::assertSame('Nomor BAB', (string) $sheet->getCell('A38')->getValue());
                self::assertSame('Jenis TP', (string) $sheet->getCell('C38')->getValue());
                self::assertSame('Nama TP (maks. 50)', (string) $sheet->getCell('D38')->getValue());
                self::assertSame('Judul satu Materi', (string) $sheet->getCell('F38')->getValue());
                self::assertNull($sheet->getCell('A13')->getValue());
                self::assertNull($sheet->getCell('J118')->getValue());
                foreach ([
                    'A39' => 'A39:A118',
                    'C39' => 'C39:C118',
                    'G39' => 'G39:G118',
                ] as $alamat => $rentang) {
                    $validasi = $sheet->getDataValidation($alamat);
                    self::assertSame('list', $validasi->getType());
                    self::assertSame($rentang, $validasi->getSqref());
                    self::assertTrue($validasi->getShowDropDown(),
                        'PhpSpreadsheet harus menulis showDropDown=0 agar panah Excel terlihat: ' . $alamat);
                    self::assertTrue($validasi->getShowErrorMessage(),
                        'Isian di luar daftar ' . $alamat . ' wajib ditolak Excel.');
                }
                self::assertSame('"Esensial,NonEsensial"',
                    $sheet->getDataValidation('C39')->getFormula1());
                self::assertSame('"YouTube,PDF,Audio,Gambar"',
                    $sheet->getDataValidation('G39')->getFormula1());
                self::assertSame('BABS_' . $n,
                    ltrim($sheet->getDataValidation('A39')->getFormula1(), '='));
                self::assertNotNull($buku->getDefinedName('BABS_' . $n));
                self::assertStringContainsString("'SEMESTER " . $n . "'!" . '$A$13:$A$32',
                    $buku->getDefinedName('BABS_' . $n)->getValue());
                self::assertSame('Catatan', (string) $sheet->getCell('H38')->getValue());
                self::assertSame('', (string) $sheet->getCell('I38')->getValue());
                self::assertSame('', (string) $sheet->getCell('J38')->getValue());
                foreach ($sheet->getCellCollection()->getCoordinates() as $coord) {
                    if ($sheet->getCell($coord)->getDataType() === DataType::TYPE_FORMULA) {
                        self::assertMatchesRegularExpression('/^B(?:39|[4-9][0-9]|10[0-9]|11[0-8])$/D', $coord,
                            'Hanya rumus urutan TP yang diizinkan: ' . $coord);
                        self::assertSame(\App\Services\AtpOtomatisTpService::rumus((int) substr($coord, 1)),
                            (string) $sheet->getCell($coord)->getValue());
                    }
                }
            }
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    /** Kejelasan workbook adalah kontrak QA, bukan sekadar estetika web. */
    public function testKontrakTampilanTemplateExcel(): void
    {
        $buku = IOFactory::load(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::BERKAS);
        \App\Services\AtpTemplateLayoutService::terapkan($buku);
        \App\Services\AtpOtomatisTpService::terapkan($buku);
        try {
            for ($n = 1; $n <= 6; ++$n) {
                $sheet = $buku->getSheetByName('SEMESTER ' . $n);
                self::assertFalse($sheet->getShowGridLines());
                self::assertContains('A1:J1', $sheet->getMergeCells());
                self::assertGreaterThanOrEqual(44.0, (float) $sheet->getRowDimension(1)->getRowHeight());
                self::assertGreaterThanOrEqual(44.0, (float) $sheet->getRowDimension(38)->getRowHeight());
                self::assertSame('12364A', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
                self::assertSame('078783', $sheet->getStyle('A38')->getFill()->getStartColor()->getRGB());
                self::assertSame('DAF2E7', $sheet->getStyle('C39')->getFill()->getStartColor()->getRGB());
                self::assertSame('E0ECFC', $sheet->getStyle('F39')->getFill()->getStartColor()->getRGB());
                self::assertTrue($sheet->getStyle('C38')->getAlignment()->getWrapText());
            }
            self::assertFalse($buku->getSheetByName('MULAI DI SINI')->getShowGridLines());
            self::assertFalse($buku->getSheetByName('RINGKASAN')->getShowGridLines());
            self::assertNull($buku->getSheetByName('RINGKASAN')->getCell('B5')->getValue(),
                'Template repository tidak boleh membawa identitas Gen nyata.');
            self::assertNull($buku->getSheetByName('REFERENSI CP')->getCell('A7')->getValue(),
                'Referensi CP diisi oleh generator dari server.');
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    /** Regression: getShowDropDown() saja tidak cukup, cek atribut ZIP XML final. */
    public function testDropdownTerlihatDiBerkasXlsxNyata(): void
    {
        $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::BERKAS;
        \App\Services\AtpUnduhanService::periksaDropdownXlsx($file);
        self::assertTrue(true, 'Semua 18 dropdown memiliki showDropDown=0 dalam XLSX.');
    }

    public function testTidakAdaMacroDanReferensiEksternal(): void
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::BERKAS) === true);
        try {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name = strtolower((string) $zip->getNameIndex($i));
                self::assertStringNotContainsString('vba', $name);
                self::assertStringNotContainsString('externallink', $name);
                self::assertStringNotContainsString('connections.xml', $name);
            }
        } finally {
            $zip->close();
        }
    }
}
