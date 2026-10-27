<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KodeTpService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class KodeTpV25TambahanTest extends TestCase
{
    public function testKodeYangSamaDapatDiulangDalamGenLainTapiBedaPaket(): void
    {
        self::assertSame('SKI.8.1.1', KodeTpService::bentuk('SKI', 8, 1, 1));
        self::assertTrue(KodeTpService::cocok('SKI.8.1.1', 'SKI', 8, 1, 1));
        self::assertFalse(KodeTpService::cocok('SKI.8.1.2', 'SKI', 8, 1, 1));
    }

    public function testUrutanTpDrafMempertahankanNomorSebelumnya(): void
    {
        self::assertSame(4, KodeTpService::urutanBerikutnya([
            ['urutan_dalam_bab' => 1],
            ['urutan_dalam_bab' => 2],
            ['urutan_dalam_bab' => 3],
        ]));
    }

    public function testTidakMenerimaNomorBABLebihDariKapasitas(): void
    {
        $this->expectException(DomainException::class);
        KodeTpService::bentuk('SKI', 8, 1000, 1);
    }
}
