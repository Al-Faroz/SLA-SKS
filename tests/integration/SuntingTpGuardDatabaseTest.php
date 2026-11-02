<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\SuntingTpService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

/** Uji gerbang server terhadap DB sintetis, tanpa migration/seeder. */
final class SuntingTpGuardDatabaseTest extends TestCase
{
    private static $db;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa_sunting') {
            self::markTestSkipped('Uji ini hanya boleh berjalan pada database QA sintetis.');
        }
        self::$db = Database::connect([
            'DSN' => '', 'hostname' => '127.0.0.1', 'username' => 'root',
            'password' => '', 'database' => 'sla_sks_qa_sunting',
            'DBDriver' => 'MySQLi', 'DBPrefix' => '', 'pConnect' => false,
            'DBDebug' => true, 'charset' => 'utf8mb4',
            'DBCollat' => 'utf8mb4_unicode_ci', 'swapPre' => '',
            'encrypt' => false, 'compress' => false, 'strictOn' => false,
            'failover' => [], 'port' => 3306,
        ], false);
    }

    public function testTpTanpaProgresSiswaBolehDibuka(): void
    {
        (new SuntingTpService(self::$db))->pastikanBelumDipakai(1);
        $this->addToAssertionCount(1);
    }

    public function testSatuProgresSiswaLangsungMengunciTp(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('digunakan siswa');
        (new SuntingTpService(self::$db))->pastikanBelumDipakai(2);
    }

    public function testAktivitasMateriHistorisJugaMengunciTp(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('aktivitas siswa');
        (new SuntingTpService(self::$db))->pastikanBelumDipakai(3);
    }

    public function testResumeHistorisJugaMengunciTp(): void
    {
        $this->expectException(DomainException::class);
        (new SuntingTpService(self::$db))->pastikanBelumDipakai(4);
    }
}
