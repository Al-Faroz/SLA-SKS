<?php

declare(strict_types=1);

use App\Services\KegiatanMateriSiswaService;
use PHPUnit\Framework\TestCase;

final class KegiatanMateriSiswaTest extends TestCase
{
    public function testBatasMinimalBerdasarkanSnapshotDanNormalisasiUnicode(): void
    {
        $snapshot = ['resume_min' => '75', 'tugas_min' => '350'];
        self::assertSame(75,KegiatanMateriSiswaService::batasMinimal('resume',$snapshot));
        self::assertSame(350,KegiatanMateriSiswaService::batasMinimal('hots',$snapshot));
        self::assertSame("مرحبا\nHalo", KegiatanMateriSiswaService::normalisasi("  مرحبا\r\nHalo  "));
        self::assertSame(str_repeat('A',75),
            KegiatanMateriSiswaService::validasi('resume',str_repeat('A',75),true,75));
        self::assertSame('draf',
            KegiatanMateriSiswaService::validasi('resume','draf',false,75));
    }

    public function testJawabanFinalDiBawahSnapshotDitolak(): void
    {
        $this->expectException(\DomainException::class);
        KegiatanMateriSiswaService::validasi('hots',str_repeat('X',249),true,250);
    }

    public function testBatasSnapshotHilanganAtauDiLuarMasterTidakBolehFallback(): void
    {
        foreach ([[],['resume_min'=>0],['resume_min'=>1001],
            ['resume_min'=>'50.5'],['resume_min'=>'invalid']] as $config) {
            try {
                KegiatanMateriSiswaService::batasMinimal('resume',$config);
                self::fail('Tidak boleh memakai batas default jika snapshot tidak sah.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('snapshot TP', $e->getMessage());
            }
        }
    }

    public function testJenisTidakDikenalDitolak(): void
    {
        $this->expectException(\DomainException::class);
        KegiatanMateriSiswaService::tabel('bypass');
    }

    public function testJawabanTerlaluPanjangDitolak(): void
    {
        $this->expectException(\DomainException::class);
        KegiatanMateriSiswaService::validasi('resume',str_repeat('Q',12001),false,50);
    }
}
