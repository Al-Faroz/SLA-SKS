<?php

declare(strict_types=1);

use App\Services\AtpStagingService;
use PHPUnit\Framework\TestCase;

final class AtpStagingServiceTest extends TestCase
{
    private string $dir;
    private string $xls;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/atp_stage_test_' . bin2hex(random_bytes(8));
        mkdir($this->dir, 0700, true);
        $this->xls = $this->dir . '/original.xlsx';
        file_put_contents($this->xls, 'PK' . str_repeat('synthetic', 10));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $p) {
            @unlink($p);
        }
        @unlink($this->dir . '/.htaccess');
        @rmdir($this->dir);
        parent::tearDown();
    }

    private function service(): AtpStagingService
    {
        return new AtpStagingService($this->dir);
    }

    public function testTokenDiikatAkunPaketDanSesi(): void
    {
        $s = $this->service();
        $stage = $s->buat($this->xls, 10, 33, 'sesi-guru-1', str_repeat('a', 64));
        self::assertTrue(AtpStagingService::validasiToken($stage['token']));
        $ref = $s->ambil($stage['token'], 10, 33, 'sesi-guru-1');
        self::assertSame(str_repeat('a', 64), $ref['fingerprint']);
        self::assertSame(hash_file('sha256', $this->xls), $ref['sha256']);
        foreach ([[11,33,'sesi-guru-1'], [10,34,'sesi-guru-1'], [10,33,'sesi-lain']] as $other) {
            try {
                $s->ambil($stage['token'], ...$other);
                self::fail('Staging boleh diakses bukan akun/paket/sesi pemilik.');
            } catch (\DomainException $e) {
                self::assertStringContainsString('Pratinjau', $e->getMessage());
            }
        }
        $s->hapus($stage['token']);
        $this->expectException(\DomainException::class);
        $s->ambil($stage['token'], 10, 33, 'sesi-guru-1');
    }

    public function testBerkasDiubahSetelahPreviewDitolak(): void
    {
        $s = $this->service();
        $stage = $s->buat($this->xls, 1, 2, 'sesi', str_repeat('b', 64));
        $ref = $s->ambil($stage['token'], 1, 2, 'sesi');
        file_put_contents($ref['file'], 'PK file berubah');
        $this->expectExceptionMessage('File pratinjau berubah');
        $s->ambil($stage['token'], 1, 2, 'sesi');
    }

    public function testKedaluwarsaDitolak(): void
    {
        $s = $this->service();
        $stage = $s->buat($this->xls, 1, 2, 'sesi', str_repeat('c', 64));
        $metaFile = $this->dir . '/' . $stage['token'] . '.json';
        $data = json_decode((string) file_get_contents($metaFile), true);
        $data['kedaluwarsa'] = time() - 1;
        file_put_contents($metaFile, json_encode($data));
        $this->expectExceptionMessage('kadaluarsa');
        $s->ambil($stage['token'], 1, 2, 'sesi');
    }

    public function testTokenTraversalDitolak(): void
    {
        self::assertFalse(AtpStagingService::validasiToken('../file'));
        self::assertFalse(AtpStagingService::validasiToken(str_repeat('a', 63)));
        self::assertFalse(AtpStagingService::validasiToken(str_repeat('X', 64)));
    }

    public function testFingerprintTidakValidDitolak(): void
    {
        $this->expectException(\DomainException::class);
        $this->service()->buat($this->xls, 1, 2, 'sesi', 'fingerprint-tidak-valid');
    }
}
