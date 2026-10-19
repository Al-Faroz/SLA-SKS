<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AuthService;
use CodeIgniter\Test\CIUnitTestCase;

final class K1PeranTest extends CIUnitTestCase
{
    public function testEmpatPeranTunggalValid(): void
    {
        foreach (['admin', 'guru', 'siswa', 'pimpinan'] as $peran) {
            $this->assertTrue(AuthService::peranValid([$peran]));
        }
    }

    public function testTigaKombinasiStafValid(): void
    {
        $this->assertTrue(AuthService::peranValid(['admin', 'guru']));
        $this->assertTrue(AuthService::peranValid(['admin', 'pimpinan']));
        $this->assertTrue(AuthService::peranValid(['guru', 'pimpinan']));
    }

    public function testSiswaTidakBolehMerangkapDanTidakAdaRoleKelima(): void
    {
        $this->assertFalse(AuthService::peranValid(['siswa', 'guru']));
        $this->assertFalse(AuthService::peranValid(['admin', 'guru', 'pimpinan']));
        $this->assertFalse(AuthService::peranValid(['operator']));
        $this->assertFalse(AuthService::peranValid([]));
    }
}
