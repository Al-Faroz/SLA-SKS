<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BabService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class BabInputTest extends TestCase
{
    public function testNamaBab50KarakterDiterima(): void
    {
        $hasil = BabService::validasiInput(1, str_repeat('A', 50), ' Deskripsi BAB ');
        self::assertSame(str_repeat('A', 50), $hasil['nama']);
        self::assertSame('Deskripsi BAB', $hasil['deskripsi']);
    }

    public function testNamaBab51KarakterDitolak(): void
    {
        $this->expectException(DomainException::class);
        BabService::validasiInput(1, str_repeat('A', 51), 'Deskripsi');
    }

    public function testDeskripsi225KarakterDiterima(): void
    {
        self::assertSame(225, mb_strlen(
            BabService::validasiInput(1, 'Nama BAB', str_repeat('x', 225))['deskripsi']));
    }

    public function testDeskripsi226KarakterDitolak(): void
    {
        $this->expectException(DomainException::class);
        BabService::validasiInput(1, 'Nama BAB', str_repeat('x', 226));
    }

    public function testBABKosongDitolak(): void
    {
        $this->expectException(DomainException::class);
        BabService::validasiInput(1, ' ', 'Deskripsi');
    }
}
