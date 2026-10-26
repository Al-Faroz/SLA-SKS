<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\BerkasMateriService;
use Config\Database;
use DomainException;
use PHPUnit\Framework\TestCase;

final class BerkasMateriAksesTest extends TestCase
{
    private static $db;
    private string $folder;
    private ?int $mediaId = null;

    public static function setUpBeforeClass(): void
    {
        if (getenv('SLA_QA_DB') !== 'sla_sks_qa') {
            self::markTestSkipped('Hanya fixture database QA sementara.');
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
        $this->folder = sys_get_temp_dir() . '/sla-media-qa-' . bin2hex(random_bytes(8));
        mkdir($this->folder, 0700);
        $nama = str_repeat('a', 48) . '.jpg';
        file_put_contents($this->folder . '/' . $nama, str_repeat('X', 32));
        self::$db->table('berkas')->insert([
            'tp_id' => 1, 'nama_acak' => $nama, 'mime' => 'image/jpeg',
            'ukuran_byte' => 32, 'checksum' => hash('sha256', str_repeat('X', 32)),
            'halaman' => null, 'durasi_detik' => null, 'dibuat_pada' => gmdate('Y-m-d H:i:s'),
        ]);
        $this->mediaId = (int) self::$db->insertID();
        self::$db->table('materi')->where('id', 1)->update([
            'jenis' => 'gambar', 'berkas_id' => $this->mediaId,
        ]);
    }

    protected function tearDown(): void
    {
        self::$db->table('materi')->where('id', 1)->update([
            'jenis' => 'pdf', 'berkas_id' => null,
        ]);
        if ($this->mediaId !== null) {
            self::$db->table('berkas')->where('id', $this->mediaId)->delete();
        }
        @unlink($this->folder . '/' . str_repeat('a', 48) . '.jpg');
        @rmdir($this->folder);
    }

    private function service(int $user): BerkasMateriService
    {
        return new BerkasMateriService(self::$db, static fn (): array => [
            'id' => $user, 'status' => 'aktif',
            'peran_aktif' => 'guru', 'wajib_ganti_sandi' => 0,
        ], $this->folder);
    }

    public function testGuruDitugaskanBolehMengambilMedia(): void
    {
        $media = $this->service(1)->media(1, 1);
        self::assertSame('image/jpeg', $media['mime']);
        self::assertSame(32, $media['bytes']);
        self::assertFileExists($media['path']);
    }

    public function testGuruGenLainDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->service(2)->media(1, 1);
    }

    public function testMateriMilikTpLainDitolak(): void
    {
        $this->expectException(DomainException::class);
        $this->service(1)->media(1, 2);
    }

    public function testNamaMediaFisikHilangDitolak(): void
    {
        @unlink($this->folder . '/' . str_repeat('a', 48) . '.jpg');
        $this->expectException(DomainException::class);
        $this->service(1)->media(1, 1);
    }
}
