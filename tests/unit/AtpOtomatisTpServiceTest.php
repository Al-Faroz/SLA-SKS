<?php

declare(strict_types=1);

use App\Services\AtpOtomatisTpService;
use App\Services\AtpTemplateLayoutService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class AtpOtomatisTpServiceTest extends TestCase
{
    public function testEnamDropdownDanEmpatRatusDelapanPuluhFormula(): void
    {
        $buku = IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
        try {
            AtpTemplateLayoutService::terapkan($buku);
            AtpOtomatisTpService::terapkan($buku);
            self::assertSame(AtpOtomatisTpService::VERSI,
                (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue());

            $jumlah = 0;
            for ($semester = 1; $semester <= 6; ++$semester) {
                $sheet = $buku->getSheetByName('SEMESTER ' . $semester);
                self::assertTrue(AtpOtomatisTpService::daftarBabSah($buku, $semester));
                $range = (string) $buku->getDefinedName('BABS_' . $semester)->getValue();
                self::assertStringContainsString("'SEMESTER " . $semester . "'!", $range);
                self::assertStringContainsString('$A$13:$A$32', $range);
                for ($row = 39; $row <= 118; ++$row) {
                    self::assertTrue(AtpOtomatisTpService::formulaSah($sheet, 'B' . $row));
                    ++$jumlah;
                }
            }
            self::assertSame(480, $jumlah);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testPenomoranBabDiselingiTetapBersifatPerBab(): void
    {
        $bab = [1, 2, 1, 2, 2, 1];
        $nomor = [];
        $aktual = [];
        foreach ($bab as $n) {
            $nomor[$n] = ($nomor[$n] ?? 0) + 1;
            $aktual[] = $nomor[$n];
        }
        self::assertSame([1, 1, 2, 2, 3, 3], $aktual);
        self::assertSame(
            '=IF(OR(A39="",D39=""),"",COUNTIFS($A$39:A39,A39,$D$39:D39,"<>"))',
            AtpOtomatisTpService::rumus(39)
        );
    }

    public function testRumusDiSheetContohTidakDiizinkan(): void
    {
        $buku = IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
        try {
            AtpTemplateLayoutService::terapkan($buku);
            AtpOtomatisTpService::terapkan($buku);
            $contoh = $buku->getSheetByName('CONTOH MAT VII');
            $contoh->setCellValue('B39', AtpOtomatisTpService::rumus(39));
            self::assertFalse(AtpOtomatisTpService::formulaSah($contoh, 'B39'));
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testFormulaAtauDefinedNameYangDiubahDitolak(): void
    {
        $buku = IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
        try {
            AtpTemplateLayoutService::terapkan($buku);
            AtpOtomatisTpService::terapkan($buku);
            $sh = $buku->getSheetByName('SEMESTER 5');
            $sh->setCellValue('B40', '=HYPERLINK("https://example.test","x")');
            self::assertFalse(AtpOtomatisTpService::formulaSah($sh, 'B40'));
            $buku->getDefinedName('BABS_5')->setValue(
                '=OFFSET(\'SEMESTER 4\'!$A$12,1,0,1,1)'
            );
            self::assertFalse(AtpOtomatisTpService::daftarBabSah($buku, 5));
        } finally {
            $buku->disconnectWorksheets();
        }
    }
}
