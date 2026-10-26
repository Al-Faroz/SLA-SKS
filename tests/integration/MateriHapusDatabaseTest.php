<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\MateriDrafService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

final class MateriHapusDatabaseTest extends TestCase
{
    private static $db;
    private int $versi;
    private string $status;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya dijalankan dengan fixture MySQL sementara.');
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

    protected function setUp(): void
    {
        $tp = self::$db->table('tp')->where('id', 1)->get()->getRowArray();
        $this->versi = (int) $tp['struktur_versi'];
        $this->status = (string) $tp['status'];
    }

    protected function tearDown(): void
    {
        self::$db->table('materi')->where('tp_id', 1)->where('id !=', 1)->delete();
        self::$db->table('materi')->where('id', 1)->update(['urutan' => 1]);
        self::$db->table('tp')->where('id', 1)->update([
            'struktur_versi' => $this->versi, 'status' => $this->status,
        ]);
    }

    private function service(int $userId = 1): MateriDrafService
    {
        return new MateriDrafService(self::$db, static fn (): array => [
            'id' => $userId, 'status' => 'aktif',
            'peran_aktif' => 'guru', 'wajib_ganti_sandi' => 0,
        ]);
    }

    private function tambahFixture(string $judul, int $urutan): int
    {
        self::$db->table('materi')->insert([
            'tp_id' => 1, 'row_uuid' => bin2hex(random_bytes(16)),
            'urutan' => $urutan, 'judul' => $judul, 'jenis' => 'pdf',
            'petunjuk_resume' => 'Resume QA', 'petunjuk_hots' => 'HOTS QA',
            'min_waktu_detik' => 60, 'url_youtube' => null,
            'berkas_id' => null, 'aktif' => 0,
        ]);
        return (int) self::$db->insertID();
    }

    public function testHapusMateriTengahMerapatkanUrutan(): void
    {
        $tengah = $this->tambahFixture('Dihapus', 2);
        $akhir = $this->tambahFixture('Bertahan', 3);
        $this->service()->hapus(1, $tengah, $this->versi);
        self::assertNull(self::$db->table('materi')->where('id', $tengah)->get()->getRowArray());
        self::assertSame(2, (int) self::$db->table('materi')->where('id', $akhir)->get()->getRow('urutan'));
        self::assertSame($this->versi + 1, (int) self::$db->table('tp')->where('id', 1)->get()->getRow('struktur_versi'));
    }

    public function testGuruGenLainDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->service(2)->hapus(1, 1, $this->versi);
    }

    public function testTpSiapDitolak(): void
    {
        self::$db->table('tp')->where('id', 1)->update(['status' => 'siap']);
        $this->expectException(DomainException::class);
        $this->service()->hapus(1, 1, $this->versi);
    }

    public function testVersiLamaDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->service()->hapus(1, 1, 9999);
    }

    public function testIdMateriMilikTpLainDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->service()->hapus(1, 2, $this->versi);
    }
}
