<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Fitur HTTP sebenarnya dengan sesi sintetis, filter auth, controller,
 * PetaTpService, dan render view. Hanya pada database QA terpisah.
 */
final class GuruPetaAtpLoginHttpTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Test hanya boleh memakai database sla_sks_qa.');
        }

        if (! defined('WRITEPATH')) {
            define('WRITEPATH', dirname(__DIR__, 2) . '/writable/');
        }
        parent::setUp();

        $db = config('Database');
        $db->defaultGroup = 'tests';
        $db->tests = array_merge($db->default, [
            'hostname' => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'database' => 'sla_sks_qa',
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'pConnect' => false,
            'DBDebug' => true,
            'port' => 3306,
        ]);
    }

    /** @return array<string,int|string> */
    private function sesi(int $penggunaId, string $peran = 'guru'): array
    {
        return [
            'auth_id' => $penggunaId,
            'auth_aktivitas' => time(),
            'auth_versi' => 1,
            'peran_aktif' => $peran,
        ];
    }

    public function testGuruDapatMelihatPetaEnamSemester(): void
    {
        $hasil = $this->withSession($this->sesi(1))->get('guru/peta-atp');
        $hasil->assertOK();
        $html = $hasil->getJSON() ?? $hasil->getBody();
        self::assertStringContainsString('Peta ATP enam semester', $html);
        self::assertStringContainsString('TP untuk Guru A', $html);
        self::assertStringNotContainsString('TP untuk Guru B', $html);
        foreach (range(1, 6) as $semester) {
            self::assertStringContainsString('Semester ' . $semester, $html);
        }
    }

    public function testGuruLainHanyaMelihatTpMiliknya(): void
    {
        $hasil = $this->withSession($this->sesi(2))->get('guru/peta-atp');
        $hasil->assertOK();
        $html = $hasil->getBody();
        self::assertStringContainsString('TP untuk Guru B', $html);
        self::assertStringNotContainsString('TP untuk Guru A', $html);
    }

    public function testIndeksPenugasanTidakSahDialihkan(): void
    {
        $hasil = $this->withSession($this->sesi(1))->get('guru/peta-atp?pilihan=999');
        $hasil->assertRedirectTo('guru');
    }

    public function testAdminTidakBolehMasukHalamanGuru(): void
    {
        $hasil = $this->withSession($this->sesi(3, 'admin'))->get('guru/peta-atp');
        self::assertSame(403, $hasil->getStatusCode());
    }

    public function testGuruWajibGantiSandiDialihkan(): void
    {
        $hasil = $this->withSession($this->sesi(4))->get('guru/peta-atp');
        $hasil->assertRedirectTo('ganti-sandi');
    }
}
