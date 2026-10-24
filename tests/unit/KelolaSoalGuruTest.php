<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HtmlSoalGuruService;
use App\Services\ImporBankSoalService;
use App\Services\KelolaSoalGuruService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class KelolaSoalGuruTest extends TestCase
{
    public function testEditorMenerimaTabelRTLdanFormatTeks(): void
    {
        $data = HtmlSoalGuruService::bersihkan(
            '<p dir="rtl"><strong>الخلافة</strong></p><table><tr><td>Damaskus</td></tr></table>'
        );
        self::assertStringContainsString('dir="rtl"', $data['html']);
        self::assertStringContainsString('<table>', $data['html']);
        self::assertStringContainsString('الخلافة', $data['teks']);
    }

    public function testEditorMenolakScriptDanGambarDataBase64(): void
    {
        $this->expectException(DomainException::class);
        HtmlSoalGuruService::bersihkan('<p>Soal<img src="data:image/png;base64,a"></p>');
    }

    public function testEditorTidakMenerimaHrefJavascript(): void
    {
        $this->expectException(DomainException::class);
        HtmlSoalGuruService::bersihkan('<p><a href="javascript:alert(1)">Tautan</a></p>');
    }

    public function testImporMenolakSoalYangTelahDieditManual(): void
    {
        $this->expectException(DomainException::class);
        ImporBankSoalService::cocokUntukImpor([
            'konten_terstruktur_json' => json_encode([
                'format' => 'SLA-SKS-RICH-V1', 'origin' => 'manual', 'manual_edited' => true,
            ]),
        ], 3);
    }

    public function testImporMenerimaSlotWordBelumDisuntingManual(): void
    {
        ImporBankSoalService::cocokUntukImpor([
            'konten_terstruktur_json' => json_encode([
                'format' => 'SLA-SKS-RICH-V1',
                'origin' => 'word', 'word_source_id' => str_repeat('a', 32),
                'manual_edited' => false,
            ]),
        ], 3);
        self::assertTrue(true);
    }

    public function testVersiSoalBerubahSaatKontenDiubah(): void
    {
        $soal = ['id' => 8, 'row_uuid' => str_repeat('c', 32), 'urutan' => 1,
            'pertanyaan' => 'Pertanyaan semula', 'kunci' => 'A', 'aktif' => 1];
        $versi = KelolaSoalGuruService::versi($soal);
        $soal['pertanyaan'] = 'Pertanyaan baru';
        self::assertNotSame($versi, KelolaSoalGuruService::versi($soal));
    }
}
