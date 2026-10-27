<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KodeTpService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class KodeTpServiceTest extends TestCase
{
    public function testKodeTpKelasTujuhSampaiSembilan(): void
    {
        self::assertSame('BIND.7.1.1', KodeTpService::bentuk('BIND', 7, 1, 1));
        self::assertSame('SKI.8.1.2', KodeTpService::bentuk('SKI', 8, 1, 2));
        self::assertSame('SKI.8.2.1', KodeTpService::bentuk('SKI', 8, 2, 1));
        self::assertSame('MTK.9.1.1', KodeTpService::bentuk('MTK', 9, 1, 1));
    }

    public function testNamaKelasFaseDDiterjemahkanDenganBenar(): void
    {
        self::assertSame(7, KodeTpService::kelasDariNama('VII'));
        self::assertSame(8, KodeTpService::kelasDariNama('VIII'));
        self::assertSame(9, KodeTpService::kelasDariNama('IX'));
    }

    public function testKodeDariExcelHarusCocokPersisDenganHasilServer(): void
    {
        self::assertTrue(KodeTpService::cocok('SKI.8.1.1', 'SKI', 8, 1, 1));
        self::assertFalse(KodeTpService::cocok('SKI.8.1.2', 'SKI', 8, 1, 1));
        self::assertFalse(KodeTpService::cocok(' SKI.8.1.1 ', 'SKI', 8, 1, 1));
        self::assertFalse(KodeTpService::cocok('SKI.8.01.1', 'SKI', 8, 1, 1));
    }

    public function testNomorTpDalamBabMulaiSatuDanBerurutan(): void
    {
        self::assertSame(1, KodeTpService::urutanBerikutnya([]));
        self::assertSame(3, KodeTpService::urutanBerikutnya([
            ['urutan_dalam_bab' => 1],
            ['urutan_dalam_bab' => 2],
        ]));
    }

    public function testUrutanTpGandaDitolak(): void
    {
        $this->expectException(DomainException::class);
        KodeTpService::urutanBerikutnya([
            ['urutan_dalam_bab' => 1],
            ['urutan_dalam_bab' => 1],
        ]);
    }

    public function testUrutanTpBerlubangDitolak(): void
    {
        $this->expectException(DomainException::class);
        KodeTpService::urutanBerikutnya([
            ['urutan_dalam_bab' => 1],
            ['urutan_dalam_bab' => 3],
        ]);
    }

    public function testKelasDiLuarFaseDDitolak(): void
    {
        $this->expectException(DomainException::class);
        KodeTpService::bentuk('SKI', 10, 1, 1);
    }

    public function testKodeMapelTidakTerstandarDitolak(): void
    {
        $this->expectException(DomainException::class);
        KodeTpService::bentuk('ski', 8, 1, 1);
    }

    public function testNomorBabNolDitolak(): void
    {
        $this->expectException(DomainException::class);
        KodeTpService::bentuk('SKI', 8, 0, 1);
    }
}
