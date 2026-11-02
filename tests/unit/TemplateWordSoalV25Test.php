<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TemplateWordSoalService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class TemplateWordSoalV25Test extends TestCase
{
    private function identitas(): array
    {
        return [
            'tp_id' => 23, 'paket_id' => 1, 'guru_id' => 7,
            'struktur_versi' => 1, 'kode_tp' => 'SKI.8.1.1',
            'judul_tp' => 'Contoh', 'gen' => 'Gen QA',
            'mapel' => 'SKI', 'guru' => 'Guru QA',
            'kurikulum' => 'KBC', 'semester' => '4',
        ];
    }

    public function testTemplateMinimalSepuluhDanTigaPuluhDapatDibuat(): void
    {
        foreach ([['diagnostik', 10], ['sumatif', 30]] as [$jenis, $jumlah]) {
            $data = TemplateWordSoalService::buat($this->identitas(), $jenis, $jumlah, str_repeat('K', 40));
            self::assertStringStartsWith('PK', $data);
            self::assertGreaterThan(1000, strlen($data));
        }
    }

    public function testTemplateSumatifDuaPuluhSembilanDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('30–200');
        TemplateWordSoalService::buat($this->identitas(), 'sumatif', 29, str_repeat('K', 40));
    }

    public function testTemplateDiagnostikSembilanDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('10–200');
        TemplateWordSoalService::buat($this->identitas(), 'diagnostik', 9, str_repeat('K', 40));
    }
}
