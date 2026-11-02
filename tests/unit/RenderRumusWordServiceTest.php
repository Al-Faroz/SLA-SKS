<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RenderRumusWordService;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class RenderRumusWordServiceTest extends TestCase
{
    private function math(string $inner): string
    {
        $doc = new DOMDocument();
        self::assertTrue($doc->loadXML(
            '<m:oMath xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">'
            . $inner . '</m:oMath>'
        ));
        return RenderRumusWordService::html($doc->documentElement);
    }

    public function testPecahanDanPangkatTidakKehilanganStruktur(): void
    {
        $html = $this->math(
            '<m:f><m:num><m:r><m:t>2</m:t></m:r></m:num>'
            . '<m:den><m:sSup><m:e><m:r><m:t>x</m:t></m:r></m:e>'
            . '<m:sup><m:r><m:t>2</m:t></m:r></m:sup></m:sSup></m:den></m:f>'
        );
        self::assertStringContainsString('<mfrac>', $html);
        self::assertStringContainsString('<msup>', $html);
        self::assertStringContainsString('<mtext>x</mtext>', $html);
    }

    public function testTeksRumusDiEscape(): void
    {
        $html = $this->math('<m:r><m:t>&lt;script&gt;</m:t></m:r>');
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }
}
