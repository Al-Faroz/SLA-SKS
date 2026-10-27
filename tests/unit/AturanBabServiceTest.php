<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AturanBabService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class AturanBabServiceTest extends TestCase
{
    public function testNomorBabLanjutAntarSemesterKelasYangSama(): void
    {
        $sebelumnya = [
            ['semester' => 3, 'nomor_bab' => 1],
            ['semester' => 3, 'nomor_bab' => 2],
            ['semester' => 4, 'nomor_bab' => 3],
        ];
        self::assertSame(4, AturanBabService::nomorBerikutnya(4, $sebelumnya));
        self::assertSame(1, AturanBabService::nomorBerikutnya(5, []));
    }

    public function testBABGanjilTidakBolehDisisipkanSesudahSemesterGenap(): void
    {
        $this->expectException(DomainException::class);
        AturanBabService::nomorBerikutnya(3, [
            ['semester' => 3, 'nomor_bab' => 1],
            ['semester' => 4, 'nomor_bab' => 2],
        ]);
    }

    public function testSemesterGenapTidakBolehDimulaiSebelumGanjil(): void
    {
        $this->expectException(DomainException::class);
        AturanBabService::nomorBerikutnya(4, []);
    }

    public function testSemesterGenapDimulaiPadaNomorSetelahGanjil(): void
    {
        self::assertSame(3, AturanBabService::nomorBerikutnya(2, [
            ['semester' => 1, 'nomor_bab' => 1],
            ['semester' => 1, 'nomor_bab' => 2],
        ]));
    }

    public function testNomorGandaDitolak(): void
    {
        $this->expectException(DomainException::class);
        AturanBabService::nomorBerikutnya(2, [
            ['semester' => 1, 'nomor_bab' => 1],
            ['semester' => 2, 'nomor_bab' => 1],
        ]);
    }

    public function testUrutanBerlubangDitolak(): void
    {
        $this->expectException(DomainException::class);
        AturanBabService::nomorBerikutnya(2, [
            ['semester' => 1, 'nomor_bab' => 1],
            ['semester' => 1, 'nomor_bab' => 3],
        ]);
    }

    public function testSemesterDiLuarFaseDitolak(): void
    {
        $this->expectException(DomainException::class);
        AturanBabService::nomorBerikutnya(7, []);
    }
}
