<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\PetaTpService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * Menjalankan SERVICE CodeIgniter 4 sesungguhnya dengan MySQL sementara.
 * Data fixture sintetis, tidak memakai sesi maupun database pengguna.
 */
final class PetaTpDatabaseTest extends TestCase
{
    private static $db;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya boleh berjalan pada DB QA sintetis sla_sks_qa.');
        }

        self::$db = Database::connect([
            'DSN' => '',
            'hostname' => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'database' => 'sla_sks_qa',
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'pConnect' => false,
            'DBDebug' => true,
            'charset' => 'utf8mb4',
            'DBCollat' => 'utf8mb4_unicode_ci',
            'swapPre' => '',
            'encrypt' => false,
            'compress' => false,
            'strictOn' => false,
            'failover' => [],
            'port' => 3306,
        ], false);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db !== null) {
            self::$db->close();
        }
    }

    private function guru(int $id): PetaTpService
    {
        return new PetaTpService(self::$db, static fn (): array => [
            'id' => $id,
            'status' => 'aktif',
            'peran_aktif' => 'guru',
            'wajib_ganti_sandi' => 0,
        ]);
    }

    public function testHanyaMenampilkanPenugasanGuruSendiri(): void
    {
        $daftar = $this->guru(1)->genMapelDiampu();
        self::assertCount(1, $daftar);
        self::assertSame(1, (int) $daftar[0]['angkatan_id']);
    }

    public function testMembacaTpGuruSendiriPadaSemesterEmpat(): void
    {
        $peta = $this->guru(1)->petaSemester(1, 1, 4);
        self::assertCount(1, $peta['paket']);
        self::assertCount(1, $peta['tp']);
        self::assertSame('TP-01', $peta['tp'][0]['kode_tp']);
    }

    public function testSemesterBelumDibuatMenghasilkanDataKosong(): void
    {
        self::assertSame(
            ['paket' => [], 'bab' => [], 'tp' => [], 'pola_bab' => false],
            $this->guru(1)->petaSemester(1, 1, 1)
        );
    }

    public function testGuruLainDitolakSaatMembukaGenDiLuarPenugasan(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->petaSemester(2, 1, 4);
    }

    public function testPeranBukanGuruDitolakDiService(): void
    {
        $service = new PetaTpService(self::$db, static fn (): array => [
            'id' => 1,
            'status' => 'aktif',
            'peran_aktif' => 'admin',
            'wajib_ganti_sandi' => 0,
        ]);

        $this->expectException(DomainException::class);
        $service->genMapelDiampu();
    }

    public function testGuruWajibGantiSandiDitolakDiService(): void
    {
        $service = new PetaTpService(self::$db, static fn (): array => [
            'id' => 1,
            'status' => 'aktif',
            'peran_aktif' => 'guru',
            'wajib_ganti_sandi' => 1,
        ]);

        $this->expectException(DomainException::class);
        $service->petaSemester(1, 1, 4);
    }

    public function testSemesterDiLuarRentangDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->petaSemester(1, 1, 7);
    }
}
