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

    public function testGenDimulaiSemesterEmpatDapatMembuatBabSatu(): void
    {
        self::assertSame(1, AturanBabService::nomorBerikutnya(4, [], 4));
        self::assertSame(2, AturanBabService::nomorBerikutnya(4, [
            ['semester' => 4, 'nomor_bab' => 1],
        ], 4));
    }

    public function testSemesterSebelumMulaiGenBolehDibuatGuru(): void
    {
        self::assertSame(1, AturanBabService::nomorBerikutnya(1, [], 4));
        self::assertSame(1, AturanBabService::nomorBerikutnya(3, [], 4));
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
    public function testPetaKosongSaatBelumAdaPenugasan(): void
    {
        self::assertSame([], AturanBabService::denganStatusForm([], 1));
    }

    public function testFormGenMulaiSemesterEmpatTidakMengharuskanSemesterTiga(): void
    {
        $semester = [];
        for ($n = 1; $n <= 6; $n++) {
            $semester[$n] = ['bab' => [], 'paket' => []];
        }
        $hasil = AturanBabService::denganStatusForm($semester, 4);
        self::assertTrue($hasil[3]['boleh_tambah_bab']);
        self::assertTrue($hasil[4]['boleh_tambah_bab']);
        self::assertTrue($hasil[5]['boleh_tambah_bab']);
    }

    public function testFormGenMulaiSemesterSatuHarusMenyusunBABGanjilDulu(): void
    {
        $semester = [];
        for ($n = 1; $n <= 6; $n++) {
            $semester[$n] = ['bab' => [], 'paket' => []];
        }
        $hasil = AturanBabService::denganStatusForm($semester, 1);
        self::assertTrue($hasil[1]['boleh_tambah_bab']);
        self::assertFalse($hasil[2]['boleh_tambah_bab']);
        $semester[1]['bab'][] = ['nomor_bab' => 1];
        $hasil = AturanBabService::denganStatusForm($semester, 1);
        self::assertTrue($hasil[2]['boleh_tambah_bab']);
        $semester[2]['bab'][] = ['nomor_bab' => 2];
        $hasil = AturanBabService::denganStatusForm($semester, 1);
        self::assertFalse($hasil[1]['boleh_tambah_bab']);
    }

    public function testPetaSemesterTidakLengkapDitolak(): void
    {
        $this->expectException(DomainException::class);
        AturanBabService::denganStatusForm([1 => ['bab' => []]], 1);
    }

}
