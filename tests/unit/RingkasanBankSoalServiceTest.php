<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\RingkasanBankSoalService;
use PHPUnit\Framework\TestCase;

final class RingkasanBankSoalServiceTest extends TestCase
{
    public function testBankKosongBelumMemenuhiTarget(): void
    {
        $hasil = RingkasanBankSoalService::susun(['diag_tayang'=>10,'sum_tayang'=>20], []);
        self::assertSame(0, $hasil['diagnostik']['aktif']);
        self::assertFalse($hasil['diagnostik']['memadai']);
        self::assertFalse($hasil['sumatif']['memadai']);
    }

    public function testJumlahCukupBukanValidasiIsiSoal(): void
    {
        $hasil = RingkasanBankSoalService::susun(['diag_tayang'=>10,'sum_tayang'=>20],
            ['diagnostik'=>10,'sumatif'=>25]);
        self::assertTrue($hasil['diagnostik']['memadai']);
        self::assertTrue($hasil['sumatif']['memadai']);
    }

    public function testTargetKurangDariMinimalTidakDianggapLayak(): void
    {
        $hasil = RingkasanBankSoalService::susun(['diag_tayang'=>9,'sum_tayang'=>4],
            ['diagnostik'=>30,'sumatif'=>30]);
        self::assertFalse($hasil['diagnostik']['memadai']);
        self::assertFalse($hasil['sumatif']['memadai']);
    }
}
