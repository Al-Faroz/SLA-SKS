<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Kontrak jalur feedback G05: kesalahan filter CSRF dan Service akademik
 * tidak boleh berakhir sebagai redirect tanpa pesan yang terlihat.
 */
final class AtpPratinjauFeedbackTest extends TestCase
{
    private function baca(string $path): string
    {
        $file = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($file);
        return $file;
    }

    public function testCsrfDanGalatAkademikTerlihatDiAreaUnggah(): void
    {
        $view = $this->baca('app/Views/guru/impor_atp.php');
        self::assertStringContainsString("session()->getFlashdata('galat')", $view);
        self::assertStringContainsString("session()->getFlashdata('error')", $view);
        self::assertStringContainsString('id="atp-upload-area"', $view);
        self::assertStringContainsString('data-atp-flash-error tabindex="-1"', $view);
        self::assertStringContainsString('role="alert"', $view);
    }

    public function testRedirectHasilPratinjauDanKonfirmasiMengarahKeFeedbackForm(): void
    {
        $preview = $this->baca('app/Controllers/GuruAtpPratinjau.php');
        $confirm = $this->baca('app/Controllers/GuruAtpKonfirmasi.php');
        self::assertStringContainsString(" . '#atp-upload-area'", $preview);
        self::assertStringContainsString(" . '#atp-upload-area'", $confirm);
        self::assertStringContainsString("'galat', \$e->getMessage()", $preview);
        self::assertStringContainsString('POST pratinjau ATP diterima', $preview);
    }

    public function testSubmitterTidakDinonaktifkanSebelumBrowserMengirimRequest(): void
    {
        $script = $this->baca('public/assets/js/atp-upload.js');
        self::assertStringNotContainsString('button.disabled = true', $script);
        self::assertStringContainsString("form.dataset.atpSending = 'true'", $script);
        self::assertStringContainsString('event.preventDefault()', $script);
        self::assertStringContainsString('event.persisted', $script);
        self::assertStringContainsString('window.location.reload()', $script);
        self::assertStringContainsString("querySelector('[data-atp-flash-error]')", $script);
    }
}
