<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class AtpManualTemplateTest extends TestCase
{
    private const BERKAS = 'docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx';
    private const VERSI = 'SLA-SKS-ATP-MANUAL-V25-1';

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
            self::assertSame(self::VERSI, $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue());
            for ($n = 1; $n <= 6; ++$n) {
                $sheet = $buku->getSheetByName('SEMESTER ' . $n);
                self::assertNotNull($sheet);
                self::assertSame(self::VERSI, $sheet->getCell('A2')->getValue());
                self::assertSame('Nomor BAB', $sheet->getCell('A12')->getValue());
                self::assertSame('Nama BAB (maks. 50)', $sheet->getCell('B12')->getValue());
                self::assertSame('Nomor BAB', $sheet->getCell('A38')->getValue());
                self::assertSame('Kode TP', $sheet->getCell('C38')->getValue());
                self::assertSame('Jenis TP', $sheet->getCell('D38')->getValue());
                self::assertSame('Judul satu Materi', $sheet->getCell('G38')->getValue());
                self::assertNull($sheet->getCell('A13')->getValue());
                self::assertNull($sheet->getCell('J118')->getValue());
                self::assertSame('D39:D118', $sheet->getDataValidation('D39')->getSqref());
                foreach ($sheet->getCellCollection()->getCoordinates() as $coord) {
                    self::assertNotSame(DataType::TYPE_FORMULA, $sheet->getCell($coord)->getDataType(),
                        'Dilarang formula di ' . $sheet->getTitle() . '!' . $coord);
                }
            }
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
