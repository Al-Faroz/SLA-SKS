<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\DetailTpService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

/** Uji Service rincian TP terhadap MySQL anonim; tanpa migration/seeder. */
final class DetailTpDatabaseTest extends TestCase
{
    private static $db;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Pengujian ini hanya untuk sla_sks_qa.');
        }
        self::$db = Database::connect([
            'DSN' => '', 'hostname' => '127.0.0.1', 'username' => 'root',
            'password' => '', 'database' => 'sla_sks_qa', 'DBDriver' => 'MySQLi',
            'DBPrefix' => '', 'pConnect' => false, 'DBDebug' => true,
            'charset' => 'utf8mb4', 'DBCollat' => 'utf8mb4_unicode_ci',
            'swapPre' => '', 'encrypt' => false, 'compress' => false,
            'strictOn' => false, 'failover' => [], 'port' => 3306,
        ], false);
    }

    private function guru(int $id, string $peran = 'guru'): DetailTpService
    {
        return new DetailTpService(self::$db, static fn (): array => [
            'id' => $id, 'status' => 'aktif',
            'peran_aktif' => $peran, 'wajib_ganti_sandi' => 0,
        ]);
    }

    public function testGuruHanyaMendapatRincianTpSendiri(): void
    {
        $data = $this->guru(1)->lihat(1);
        self::assertSame('TP-01', $data['tp']['kode_tp']);
        self::assertSame(4, (int) $data['paket']['semester']);
        self::assertSame(1, $data['jumlah_materi']);
    }

    public function testGuruLainTidakBolehMembacaTpLewatId(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->lihat(2);
    }

    public function testAdminTidakBolehMembacaRincianTp(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(3, 'admin')->lihat(1);
    }

    public function testTpTidakDitemukanDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->lihat(999);
    }

    public function testSimpanDrafDanKonflikVersi(): void
    {
        $service = $this->guru(1);
        $before = self::$db->table('tp')->where('id', 1)->get()->getRowArray();
        try {
            $service->simpanDraf(1, 'TP disunting', 'Rumusan valid', 'Indikator valid', (int) $before['struktur_versi']);
            $after = self::$db->table('tp')->where('id', 1)->get()->getRowArray();
            self::assertSame('TP disunting', $after['judul']);
            self::assertSame((int) $before['struktur_versi'] + 1, (int) $after['struktur_versi']);

            try {
                $service->simpanDraf(1, 'Percobaan menimpa', 'Rumusan lain', 'Indikator lain', (int) $before['struktur_versi']);
                self::fail('Versi lama seharusnya ditolak.');
            } catch (DomainException $e) {
                self::assertStringContainsString('telah berubah', $e->getMessage());
            }
        } finally {
            self::$db->table('tp')->where('id', 1)->update([
                'judul' => $before['judul'],
                'rumusan' => $before['rumusan'],
                'indikator' => $before['indikator'],
                'struktur_versi' => $before['struktur_versi'],
            ]);
        }
    }

    public function testTidakBolehMengeditTpGenGuruLain(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->simpanDraf(2, 'Judul asing', 'Rumusan', 'Indikator', 1);
    }

    public function testTpSiapTidakBisaDisunting(): void
    {
        $awal = self::$db->table('tp')->where('id', 2)->get()->getRowArray();
        try {
            self::$db->table('tp')->where('id', 2)->update(['status' => 'siap']);
            $this->expectException(DomainException::class);
            $this->guru(2)->simpanDraf(2, 'Ubah TP Siap', 'Rumusan', 'Indikator', 1);
        } finally {
            self::$db->table('tp')->where('id', 2)->update(['status' => $awal['status']]);
        }
    }

    public function testJudulKosongDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->simpanDraf(1, '', 'Rumusan', 'Indikator', 1);
    }

    public function testIdNolDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->guru(1)->lihat(0);
    }
}
