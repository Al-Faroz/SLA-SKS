<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TpBabService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class TpBabInputTest extends TestCase
{
    public function testDuaJenisTpDanSpasiPinggir(): void
    {
        self::assertSame(['nama' => 'Bilangan', 'deskripsi' => 'Memahami bilangan', 'jenis' => 'esensial'],
            TpBabService::validasiInput(1, ' Bilangan ', ' Memahami bilangan ', 'esensial'));
        self::assertSame('non_esensial', TpBabService::validasiInput(9,
            'Pecahan', 'Menyelesaikan masalah', 'non_esensial')['jenis']);
    }

    public function testNamaLebihDari35Ditolak(): void
    {
        $this->expectException(DomainException::class);
        TpBabService::validasiInput(1, str_repeat('A', 36), 'Deskripsi', 'esensial');
    }

    public function testDeskripsiLebihDari225Ditolak(): void
    {
        $this->expectException(DomainException::class);
        TpBabService::validasiInput(1, 'Nama', str_repeat('x', 226), 'non_esensial');
    }

    public function testJenisLainDitolak(): void
    {
        $this->expectException(DomainException::class);
        TpBabService::validasiInput(1, 'Nama', 'Deskripsi', 'sumatif');
    }

    public function testIdBABNolDitolak(): void
    {
        $this->expectException(DomainException::class);
        TpBabService::validasiInput(0, 'Nama', 'Deskripsi', 'esensial');
    }
}
