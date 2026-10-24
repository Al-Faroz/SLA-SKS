<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BerkasMateriService;
use App\Services\PdfMateriValidator;
use DomainException;
use PHPUnit\Framework\TestCase;

final class PdfMateriValidatorTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            @unlink($path);
        }
    }

    /**
     * Fixture PDF klasik valid tanpa script, generator anonim.
     * Offset xref konsisten dan setiap halaman terdaftar pada Pages.
     */
    private function pdf(int $n, string $extra = ''): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R ' . $extra . ' >>';
        $kids = [];
        for ($i = 1; $i <= $n; ++$i) {
            $kids[] = ($i + 2) . ' 0 R';
        }
        $objects[] = '<< /Type /Pages /Count ' . $n . ' /Kids [' . implode(' ', $kids) . '] >>';
        for ($i = 1; $i <= $n; ++$i) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>';
        }
        $data = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $value) {
            $offsets[] = strlen($data);
            $data .= ($i + 1) . " 0 obj\n" . $value . "\nendobj\n";
        }
        $xref = strlen($data);
        $data .= "xref\n0 " . (count($objects) + 1) . "\n";
        $data .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $pos) {
            $data .= sprintf("%010d 00000 n \n", $pos);
        }
        $data .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $data .= "startxref\n" . $xref . "\n%%EOF\n";
        $file = tempnam(sys_get_temp_dir(), 'sla-pdf-qa-');
        file_put_contents($file, $data);
        $this->files[] = $file;
        return $file;
    }

    public function testPdfDariDompdfAsliDapatDiunggah(): void
    {
        $pdf = new \\Dompdf\\Dompdf();
        $pdf->loadHtml('<html><body><h1>Contoh Materi Belajar</h1><p>Dokumen QA tanpa data siswa.</p></body></html>');
        $pdf->setPaper('A4');
        $pdf->render();
        $path = tempnam(sys_get_temp_dir(), 'sla-pdf-dompdf-');
        file_put_contents($path, $pdf->output());
        $this->files[] = $path;
        $hasil = BerkasMateriService::validasi($path, 'pdf', 'materi.pdf');
        self::assertSame(1, $hasil['halaman']);
        self::assertSame('application/pdf', $hasil['mime']);
    }

    public function testPdfSatuHalamanValid(): void
    {
        $file = $this->pdf(1);
        self::assertSame(1, PdfMateriValidator::periksa($file));
        $v = BerkasMateriService::validasi($file, 'pdf', 'materi.pdf');
        self::assertSame('application/pdf', $v['mime']);
        self::assertSame(1, $v['halaman']);
        self::assertSame('pdf', $v['ext']);
    }

    public function testPdfEmpatPuluhHalamanValid(): void
    {
        self::assertSame(40, PdfMateriValidator::periksa($this->pdf(40)));
    }

    public function testPdfLebihDariEmpatPuluhHalamanDitolak(): void
    {
        $this->expectException(DomainException::class);
        PdfMateriValidator::periksa($this->pdf(41));
    }

    public function testPdfDenganJavascriptDitolak(): void
    {
        $this->expectException(DomainException::class);
        PdfMateriValidator::periksa($this->pdf(1, '/OpenAction << /S /JavaScript /JS (alert) >>'));
    }

    public function testPdfDenganNamaDikodekanDitolak(): void
    {
        $this->expectException(DomainException::class);
        PdfMateriValidator::periksa($this->pdf(1, '/#4aS (alert)'));
    }

    public function testEkstensiTidakSesuaiDitolak(): void
    {
        $this->expectException(DomainException::class);
        BerkasMateriService::validasi($this->pdf(1), 'pdf', 'materi.png');
    }

    public function testPdfPalsuDitolak(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sla-pdf-palsu-');
        file_put_contents($file, '%PDF-1.4' . str_repeat('A', 200));
        $this->files[] = $file;
        $this->expectException(DomainException::class);
        BerkasMateriService::validasi($file, 'pdf', 'materi.pdf');
    }
}
