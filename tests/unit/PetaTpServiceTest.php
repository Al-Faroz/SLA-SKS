<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PetaTpService;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * Validasi pilihan G1 harus bisa diuji tanpa database dan tanpa autentikasi.
 * Integrasi SQL/akses peran diuji terpisah di XAMPP.
 */
final class PetaTpServiceTest extends TestCase
{
    public function testPenugasanKosongDapatDitampilkanTanpaPilihan(): void
    {
        self::assertNull(PetaTpService::pilihPenugasan([], null));
    }

    public function testPilihanDefaultMengambilPenugasanPertama(): void
    {
        $daftar = [
            ['angkatan_id' => 1, 'mapel_id' => 4],
            ['angkatan_id' => 2, 'mapel_id' => 1],
        ];

        self::assertSame($daftar[0], PetaTpService::pilihPenugasan($daftar, null));
        self::assertSame($daftar[1], PetaTpService::pilihPenugasan($daftar, 1));
    }

    public function testIndeksDiLuarPenugasanDitolak(): void
    {
        $this->expectException(DomainException::class);
        PetaTpService::pilihPenugasan([['angkatan_id' => 1, 'mapel_id' => 4]], 999);
    }

    public function testPilihanNegatifDitolak(): void
    {
        $this->expectException(DomainException::class);
        PetaTpService::pilihPenugasan([], -1);
    }

    public function testPilihanKosongSelainDefaultDitolak(): void
    {
        $this->expectException(DomainException::class);
        PetaTpService::pilihPenugasan([], 1);
    }
}
