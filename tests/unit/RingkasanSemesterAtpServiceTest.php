<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RingkasanSemesterAtpService;
use PHPUnit\Framework\TestCase;

final class RingkasanSemesterAtpServiceTest extends TestCase
{
    public function testSemesterTanpaPaketTetapMemilikiAngkaNol(): void
    {
        self::assertSame([
            'paket'=>0,'bab'=>0,'tp'=>0,'esensial'=>0,'non_esensial'=>0,'status'=>'belum_ada'
        ], RingkasanSemesterAtpService::dariPeta(['paket'=>[],'bab'=>[],'tp'=>[]]));
    }

    public function testHanyaMenghitungTpBerdasarkanJenis(): void
    {
        $r = RingkasanSemesterAtpService::dariPeta([
            'paket' => [['status'=>'aktif']],
            'bab' => [['id'=>1], ['id'=>2]],
            'tp' => [
                ['jenis_tp'=>'esensial'],
                ['jenis_tp'=>'non_esensial'],
                ['jenis_tp'=>'non_esensial'],
            ],
        ]);
        self::assertSame(2, $r['bab']);
        self::assertSame(3, $r['tp']);
        self::assertSame(1, $r['esensial']);
        self::assertSame(2, $r['non_esensial']);
        self::assertSame('aktif', $r['status']);
    }

    public function testNamaKelasSetiapPasanganSemester(): void
    {
        self::assertSame('VII', RingkasanSemesterAtpService::namaKelas(2));
        self::assertSame('VIII', RingkasanSemesterAtpService::namaKelas(4));
        self::assertSame('IX', RingkasanSemesterAtpService::namaKelas(6));
    }
}
