<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PaketSemesterGuruService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class PaketSemesterGuruInputTest extends TestCase
{
    public function testSemesterDanReferensiValid(): void
    {
        self::assertSame([
            'gen_id' => 1, 'mapel_id' => 2, 'semester' => 4,
            'tahun_id' => 3, 'kurikulum_id' => 1,
        ], PaketSemesterGuruService::validasiInput(1, 2, 4, 3, 1));
    }

    public function testSemesterNolDitolak(): void
    {
        $this->expectException(DomainException::class);
        PaketSemesterGuruService::validasiInput(1, 2, 0, 3, 1);
    }

    public function testSemesterTujuhDitolak(): void
    {
        $this->expectException(DomainException::class);
        PaketSemesterGuruService::validasiInput(1, 2, 7, 3, 1);
    }

    public function testKurikulumKosongDitolak(): void
    {
        $this->expectException(DomainException::class);
        PaketSemesterGuruService::validasiInput(1, 2, 1, 3, 0);
    }
}
