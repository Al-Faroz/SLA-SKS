<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class AtpManualTemplateTest extends TestCase
{
    private const BERKAS = 'docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';
    private const VERSI = 'SLA-SKS-ATP-MANUAL-V25-2';

    /**
     * PhpSpreadsheet may return inline-string cells as RichText.
     * Cast populated text to its plain string representation before assertions.
     */
    public function testWorkbookManualSesuaiKontrak(): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::BERKAS;
        self::assertFileExists($path);
        $buku = IOFactory::load($path);
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
                self::assertSame('C39:C118', $sheet->getDataValidation('C39')->getSqref());
                foreach ($sheet->getCellCollection()->getCoordinates() as $coord) {
                    self::assertNotSame(DataType::TYPE_FORMULA, $sheet->getCell($coord)->getDataType(),
                        'Dilarang formula di ' . $sheet->getTitle() . '!' . $coord);
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
        try {
            for ($n = 1; $n <= 6; ++$n) {
                $sheet = $buku->getSheetByName('SEMESTER ' . $n);
                self::assertFalse($sheet->getShowGridLines());
                self::assertContains('A1:J1', $sheet->getMergeCells());
                self::assertGreaterThanOrEqual(44.0, (float) $sheet->getRowDimension(1)->getRowHeight());
                self::assertGreaterThanOrEqual(44.0, (float) $sheet->getRowDimension(38)->getRowHeight());
                self::assertSame('DFF1E8', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
                self::assertSame('183F35', $sheet->getStyle('A38')->getFill()->getStartColor()->getRGB());
                self::assertSame('E2F1EA', $sheet->getStyle('C39')->getFill()->getStartColor()->getRGB());
                self::assertSame('EDF4FF', $sheet->getStyle('F39')->getFill()->getStartColor()->getRGB());
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
