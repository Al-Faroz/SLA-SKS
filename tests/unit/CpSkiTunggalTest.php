<?php

declare(strict_types=1);

use App\Services\CpOperasionalService;
use PHPUnit\Framework\TestCase;

final class CpSkiTunggalTest extends TestCase
{
    public function testHanyaKodeDanElemenSkiResmiBolehTerbit(): void
    {
        self::assertTrue(CpOperasionalService::bolehTerbit(
            'SKI','D','Kurikulum Berbasis Cinta (KBC)',
            'KBC25-SKI-D-PK','Pemahaman Konsep'
        ));
        self::assertFalse(CpOperasionalService::bolehTerbit(
            'SKI','D','Kurikulum Berbasis Cinta (KBC)',
            'FD-SKI-02','Keterampilan Proses'
        ));
        self::assertFalse(CpOperasionalService::bolehTerbit(
            'SKI','D','Kurikulum Berbasis Cinta (KBC)',
            'FD-SKI-01','Pemahaman Konsep'
        ));
        self::assertFalse(CpOperasionalService::bolehTerbit(
            'SKI','D','Kurikulum Berbasis Cinta (KBC)',
            'KBC25-SKI-D-PK','Keterampilan Proses'
        ));
    }

    public function testMapelLainTidakDibatasiHanyaSatuElemen(): void
    {
        self::assertTrue(CpOperasionalService::bolehTerbit(
            'BAR','D','Kurikulum Berbasis Cinta (KBC)',
            'KBC25-BAR-D-KP','Keterampilan Proses'
        ));
        self::assertTrue(CpOperasionalService::bolehTerbit(
            'SKI','E','Kurikulum Berbasis Cinta (KBC)',
            'KBC25-SKI-E','Elemen lain'
        ));
    }
}
