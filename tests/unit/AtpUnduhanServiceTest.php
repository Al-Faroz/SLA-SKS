<?php

declare(strict_types=1);

use App\Services\AtpUnduhanService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class AtpUnduhanServiceTest extends TestCase
{
    private function buku(): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        return IOFactory::load(dirname(__DIR__, 2)
            . '/docs/TEMPLATE_ATP_SLA_SKS_v2_5_MANUAL_TANPA_MACRO.xlsx');
    }

    /** @return array<string,string|int> */
    private function konteks(): array
    {
        return [
            'kode_gen' => 'GEN2025', 'nama_gen' => 'Gen 2025', 'guru_id' => '9',
            'nama_guru' => 'Guru Contoh', 'kode_mapel' => 'SKI', 'nama_mapel' => 'Sejarah Kebudayaan Islam',
            'kurikulum' => 'Kurikulum Fase D', 'tahun_ajaran' => '2026/2027',
            'cp_id' => '3', 'semester_mulai' => 4, 'semester_paket' => 4,
        ];
    }

    public function testMengisiIdentitasCpTetapiTidakMemodifikasiBabTp(): void
    {
        $buku = $this->buku();
        try {
            AtpUnduhanService::isiBuku($buku, $this->konteks(), [
                ['id' => 51, 'elemen' => 'Elemen Sejarah', 'rumusan' => 'Memahami sejarah',
                    'nomor_versi' => 1],
            ]);
            self::assertSame('GEN2025', (string) $buku->getSheetByName('RINGKASAN')->getCell('B5')->getValue());
            self::assertSame('SKI', (string) $buku->getSheetByName('RINGKASAN')->getCell('B9')->getValue());
            self::assertSame('2026/2027', (string) $buku->getSheetByName('RINGKASAN')->getCell('B13')->getValue());
            self::assertSame('D', (string) $buku->getSheetByName('REFERENSI CP')->getCell('D7')->getValue());
            self::assertSame('Memahami sejarah', (string) $buku->getSheetByName('REFERENSI CP')->getCell('C7')->getValue());
            self::assertNull($buku->getSheetByName('SEMESTER 4')->getCell('A13')->getValue());
            self::assertNull($buku->getSheetByName('SEMESTER 4')->getCell('A39')->getValue());
            self::assertSame('C39:C118', $buku->getSheetByName('SEMESTER 4')->getDataValidation('C39')->getSqref());
            // Kualitas tampilan output harus mengikuti desain Peta Pembelajaran lama.
            $semester = $buku->getSheetByName('SEMESTER 4');
            self::assertSame('12364A', $semester->getStyle('A1')->getFill()->getStartColor()->getRGB());
            self::assertSame('FFFFFF', $semester->getStyle('A1')->getFont()->getColor()->getRGB());
            self::assertSame('078783', $semester->getStyle('A12')->getFill()->getStartColor()->getRGB());
            self::assertSame('E4F3FC', $semester->getStyle('A13')->getFill()->getStartColor()->getRGB());
            self::assertSame('DAF2E7', $semester->getStyle('C39')->getFill()->getStartColor()->getRGB());
            self::assertFalse($semester->getShowGridLines());
            self::assertSame(AtpUnduhanService::VERSI, (string) $semester->getCell('A2')->getValue());
            self::assertSame('G39:G118', $semester->getDataValidation('G39')->getSqref());

            $temp = tempnam(sys_get_temp_dir(), 'uji_atp_');
            self::assertNotFalse($temp);
            try {
                (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($buku))->save($temp);
                $ulang = IOFactory::load($temp);
                try {
                    self::assertSame('GEN2025', (string) $ulang->getSheetByName('RINGKASAN')->getCell('B5')->getValue());
                    self::assertSame('Memahami sejarah', (string) $ulang->getSheetByName('REFERENSI CP')->getCell('C7')->getValue());
                    self::assertNull($ulang->getSheetByName('SEMESTER 4')->getCell('A39')->getValue());
                } finally {
                    $ulang->disconnectWorksheets();
                }
            } finally {
                @unlink($temp);
            }
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testIsiCpAwalFormulaTetapTeksAman(): void
    {
        $buku = $this->buku();
        try {
            AtpUnduhanService::isiBuku($buku, $this->konteks(), [
                ['id' => '1', 'elemen' => '=HYPERLINK("x")',
                    'rumusan' => '+CMD', 'nomor_versi' => 1],
            ]);
            $s = $buku->getSheetByName('REFERENSI CP');
            self::assertSame("'=HYPERLINK(\"x\")", (string) $s->getCell('B7')->getValue());
            self::assertSame("'+CMD", (string) $s->getCell('C7')->getValue());
            self::assertSame(DataType::TYPE_STRING, $s->getCell('B7')->getDataType());
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testCpKosongDitolak(): void
    {
        $buku = $this->buku();
        try {
            $this->expectException(DomainException::class);
            AtpUnduhanService::isiBuku($buku, $this->konteks(), []);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testUnduhanTetapBolehUntukSemesterSebelumMulaiGen(): void
    {
        $buku = $this->buku();
        try {
            $konteks = $this->konteks();
            $konteks['semester_paket'] = 3;
            AtpUnduhanService::isiBuku($buku, $konteks, [
                ['id' => 1, 'elemen' => 'Sejarah', 'rumusan' => 'Rumusan', 'nomor_versi' => 1],
            ]);
            self::assertSame('SLA-SKS-ATP-MANUAL-V25-3',
                (string) $buku->getSheetByName('RINGKASAN')->getCell('B4')->getValue());
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testVersiTemplateSalahDitolak(): void
    {
        $buku = $this->buku();
        try {
            $buku->getSheetByName('RINGKASAN')->setCellValue('B4', 'TEMPLATE-LAIN');
            $this->expectException(DomainException::class);
            AtpUnduhanService::isiBuku($buku, $this->konteks(), [
                ['id' => 1, 'elemen' => 'Sejarah', 'rumusan' => 'Rumusan', 'nomor_versi' => 1],
            ]);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    public function testUnduhanHanyaDiterimaJikaSemuaSemesterV25TigaDelapanKolom(): void
    {
        $buku = $this->buku();
        try {
            AtpUnduhanService::isiBuku($buku, $this->konteks(), [[
                'id' => 51, 'elemen' => 'Pemahaman Konsep',
                'rumusan' => 'CP sintetis', 'nomor_versi' => 1,
            ]]);
            AtpUnduhanService::periksaTemplateV25Tiga($buku);
            foreach (range(1, 6) as $n) {
                $sh = $buku->getSheetByName('SEMESTER ' . $n);
                self::assertSame('Jenis TP', (string) $sh->getCell('C38')->getValue());
                self::assertSame('Catatan', (string) $sh->getCell('H38')->getValue());
                self::assertSame('', (string) $sh->getCell('I38')->getValue());
                self::assertSame('', (string) $sh->getCell('J38')->getValue());
            }
            $buku->getSheetByName('SEMESTER 6')->setCellValue('I38', 'Elemen CP (rujukan)');
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Kolom TP lama masih ada');
            AtpUnduhanService::periksaTemplateV25Tiga($buku);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

}
