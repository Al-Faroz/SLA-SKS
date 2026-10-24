<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TemplateWordSoalService;
use DomainException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class TemplateWordSoalServiceTest extends TestCase
{
    private function identitas(): array
    {
        return [
            'tp_id' => 1, 'paket_id' => 1, 'guru_id' => 9, 'struktur_versi' => 2,
            'gen' => 'Gen Uji', 'guru' => 'Guru Penguji', 'mapel' => 'Sejarah',
            'kurikulum' => 'Kurikulum Uji', 'semester' => '4',
            'kode_tp' => 'TP-UJI', 'judul_tp' => 'Tujuan pembelajaran uji',
        ];
    }

    public function testUnduhanMemilikiJumlahSoalDanOpsiKosong(): void
    {
        $data = TemplateWordSoalService::buat($this->identitas(), 'diagnostik', 11, str_repeat('a', 32));
        $file = tempnam(sys_get_temp_dir(), 'sla_test_');
        try {
            file_put_contents($file, $data);
            $zip = new ZipArchive();
            self::assertTrue($zip->open($file) === true);
            $xml = $zip->getFromName('word/document.xml');
            $meta = $zip->getFromName('customXml/sla-sks.xml');
            $zip->close();

            self::assertIsString($xml);
            self::assertSame(11, substr_count($xml, '>Soal '));
            self::assertSame(11, substr_count($xml, '>A</w:t>'));
            self::assertSame(11, substr_count($xml, '>E</w:t>'));
            self::assertStringNotContainsString('✓</w:t>', $xml);
            self::assertIsString($meta);
            self::assertStringContainsString('SLA-SKS-WORD-V1', $meta);
            self::assertStringContainsString('PETUNJUK PENGISIAN', $xml);
            self::assertStringContainsString('w:tblLayout w:type="fixed"', $xml);
            self::assertStringContainsString('w:gridCol w:w="6300"', $xml);
            self::assertStringContainsString('BANK SOAL DIAGNOSTIK', $xml);
            self::assertStringContainsString('DAFTAR SOAL PILIHAN GANDA', $xml);
            self::assertStringContainsString('w:fill="17533F"', $xml);
            self::assertStringContainsString('w:tblHeader w:val="true"', $xml);
            self::assertStringContainsString('<w:keepNext/>', $xml);
            self::assertStringContainsString('w:pgSz w:w="11906" w:h="16838"', $xml);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testJumlahTidakValidDitolak(): void
    {
        $this->expectException(DomainException::class);
        TemplateWordSoalService::buat($this->identitas(), 'sumatif', 201, str_repeat('b', 32));
    }
}
