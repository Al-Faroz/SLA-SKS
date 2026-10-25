<?php

declare(strict_types=1);

use App\Services\KegiatanMateriSiswaService;
use PHPUnit\Framework\TestCase;

final class KegiatanMateriSiswaTest extends TestCase
{
    public function testAturanKarakterDanNormalisasiUnicode(): void
    {
        self::assertSame(50, KegiatanMateriSiswaService::batasMinimal('resume'));
        self::assertSame(200, KegiatanMateriSiswaService::batasMinimal('hots'));
        self::assertSame("مرحبا\nHalo", KegiatanMateriSiswaService::normalisasi("  مرحبا\r\nHalo  "));
        self::assertSame(str_repeat('A',50),
            KegiatanMateriSiswaService::validasi('resume',str_repeat('A',50),true));
        self::assertSame('draf',
            KegiatanMateriSiswaService::validasi('resume','draf',false));
    }

    public function testFinalYangTerlaluSingkatDitolak(): void
    {
        $this->expectException(\DomainException::class);
        KegiatanMateriSiswaService::validasi('hots',str_repeat('X',199),true);
    }

    public function testJenisTidakDikenalDitolak(): void
    {
        $this->expectException(\DomainException::class);
        KegiatanMateriSiswaService::tabel('bypass');
    }

    public function testJawabanTerlaluPanjangDitolak(): void
    {
        $this->expectException(\DomainException::class);
        KegiatanMateriSiswaService::validasi('resume',str_repeat('Q',12001),false);
    }
}
