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
    public function testToolbarSunEditorMemakaiUnggahGambarLangsung(): void
    {
        $js = file_get_contents(__DIR__ . '/../../public/assets/js/soal-editor.js');
        self::assertIsString($js);
        self::assertStringContainsString("'table', 'image', 'link'", $js);
        self::assertStringContainsString('onImageUploadBefore', $js);
        self::assertStringContainsString('plugins.image.submitURL', $js);
        self::assertStringNotContainsString('unggah-gambar-soal', $js);
        $view = file_get_contents(__DIR__ . '/../../app/Views/guru/soal_form.php');
        self::assertIsString($view);
        self::assertStringNotContainsString('id="gambar-editor"', $view);
    }

    public function testTabelBankMemakaiKomponenResponsif(): void
    {
        $view = file_get_contents(__DIR__ . '/../../app/Views/guru/bank_soal.php');
        self::assertIsString($view);
        self::assertStringContainsString('a-tbl--cards a-tbl--bank', $view);
        foreach (['Nomor', 'Pertanyaan', 'Status', 'Asal', 'Tindakan'] as $label) {
            self::assertStringContainsString('data-label="' . $label . '"', $view);
        }
    }


    public function testKetersediaanTerpisahDanBankSoalMemakaiDuaTabJelas(): void
    {
        $view = file_get_contents(__DIR__ . '/../../app/Views/guru/bank_soal.php');
        $css = file_get_contents(__DIR__ . '/../../public/assets/css/app.css');
        self::assertIsString($view);
        self::assertIsString($css);

        $ringkasan = strpos($view, 'id="bank-soal-status"');
        $kerja = strpos($view, 'id="judul-kelola-bank"');
        $tab = strpos($view, 'class="a-bank-soal__tabs"');
        $daftar = strpos($view, 'class="a-soal-daftar"');
        self::assertNotFalse($ringkasan);
        self::assertNotFalse($kerja);
        self::assertNotFalse($tab);
        self::assertNotFalse($daftar);
        self::assertTrue($ringkasan < $kerja && $kerja < $tab && $tab < $daftar);

        self::assertSame(1, substr_count($view, 'class="a-bank-soal__ringkasan-grid"'));
        self::assertSame(1, substr_count($view, 'class="a-bank-soal__tabs"'));
        self::assertStringContainsString("['diagnostik' => 'Diagnostik', 'sumatif' => 'Sumatif']", $view);
        self::assertStringContainsString('bank-soal?jenis=', $view);
        self::assertStringContainsString('$jenisTab', $view);
        self::assertStringContainsString('aria-current="page"', $view);
        self::assertStringContainsString('class="a-bank-soal__tab-konten"', $view);

        self::assertStringContainsString('.a-bank-soal__ringkasan-grid', $css);
        self::assertStringContainsString('.a-bank-soal__tab.is-aktif', $css);
        self::assertStringContainsString('.a-bank-soal__tab:focus-visible', $css);
    }

}
