<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Rendering field UI yang sama untuk Tambah/Sunting Materi.
 * Tidak memerlukan browser atau DB; browser/Apache masih harus diuji manual.
 */
final class MateriFormViewTest extends TestCase
{
    /** @param array<string,mixed> $nilai */
    private function render(array $nilai = [], string $galatKolom = ''): string
    {
        ob_start();
        try {
            include __DIR__ . '/../../app/Views/guru/_materi_fields.php';
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function testTambahAwalMemintaJudulDanWaktuTanpaDefaultNol(): void
    {
        $html = $this->render();
        self::assertStringContainsString('name="judul"', $html);
        self::assertStringContainsString('name="min_waktu_menit"', $html);
        self::assertStringNotContainsString('value="0.00"', $html);
        self::assertStringContainsString('data-materi-jenis', $html);
    }

    public function testPilihanPdfSetelahGagalDipulihkanDenganPesanField(): void
    {
        $html = $this->render([
            'judul' => 'Materi PDF QA',
            'jenis' => 'pdf',
            'min_waktu_menit' => '10',
            'petunjuk_resume' => 'Ringkas materi di PDF.',
            'petunjuk_hots' => 'Jelaskan hubungan antar konsep.',
        ], 'media');
        self::assertMatchesRegularExpression('/value="pdf"\\s+selected/', $html);
        self::assertStringContainsString('name="media"', $html);
        self::assertStringContainsString('data-materi-batas-server=', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString('value="10"', $html);
        self::assertStringContainsString('Ringkas materi di PDF.', $html);
        self::assertStringContainsString('Materi PDF QA', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function testSuntingDenganBerkasMempertahankanJenisDanFileOpsional(): void
    {
        $html = $this->render([
            'judul' => 'Materi audio',
            'jenis' => 'audio',
            'berkas_id' => 7,
            'min_waktu_detik' => 90,
        ]);
        self::assertStringContainsString('value="1.50"', $html);
        self::assertMatchesRegularExpression('/value="pdf"[\\s\\S]*?disabled/', $html);
        self::assertStringContainsString('jenis media terkunci', $html);
        self::assertStringContainsString('Kosongkan kolom file', $html);
    }

    public function testTeksInputDisanitasiPadaView(): void
    {
        $html = $this->render([
            'judul' => '<script>test</script>',
            'jenis' => 'youtube',
            'petunjuk_resume' => '<b>uji</b>',
        ]);
        self::assertStringNotContainsString('<script>test</script>', $html);
        self::assertStringNotContainsString('<b>uji</b>', $html);
    }
}
