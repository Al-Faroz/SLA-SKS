<?php

declare(strict_types=1);

use App\Services\AdminHostingSqlService;
use PHPUnit\Framework\TestCase;

final class AdminHostingSqlServiceTest extends TestCase
{
    public function testSqlMenciptakanSatuAdminDenganGuardDanTanpaPasswordPlaintext(): void
    {
        $sandi = 'IniSandiSementaraKhususPengujianJanganDipakai';
        $sql = AdminHostingSqlService::buat(
            'admin_produksi_baru',
            "Nama Admin Produksi",
            password_hash($sandi, PASSWORD_DEFAULT)
        );

        self::assertStringContainsString('V5-0001-DATA-GEN2025', $sql);
        self::assertStringContainsString('V5-0002-FAQ-DRAF', $sql);
        self::assertStringContainsString('V5-0003-ADMIN-PRODUKSI', $sql);
        self::assertStringContainsString('CREATE TEMPORARY TABLE _sla_guard_admin_hosting', $sql);
        self::assertStringContainsString('CHECK (ok = 1)', $sql);
        self::assertStringContainsString("r.peran = 'admin' AND p.status = 'aktif'", $sql);
        self::assertStringContainsString("p.id = 1 AND r.peran = 'admin' AND p.status = 'nonaktif'", $sql);
        self::assertStringContainsString('START TRANSACTION;', $sql);
        self::assertStringContainsString("VALUES (@sla_admin_id_baru, 'admin');", $sql);
        self::assertStringContainsString("'aktif', 1, 0, NULL, 1, NULL", $sql);
        self::assertStringContainsString('COMMIT;', $sql);

        self::assertStringNotContainsString($sandi, $sql);
        self::assertStringNotContainsString('FOREIGN_KEY_CHECKS=0', $sql);
        self::assertStringNotContainsString('TRUNCATE ', $sql);
        self::assertStringNotContainsString('DROP TABLE pengguna', $sql);
        self::assertStringNotContainsString('USE sla_sks_v4', $sql);
        self::assertStringNotContainsString('USE sla_sks_v5', $sql);
    }

    public function testNamaUnicodeDanApostrofTidakMenjadiInjeksiSql(): void
    {
        $nama = "Admin O'Brian — Sekolah";
        $sql = AdminHostingSqlService::buat(
            'admin_baru',
            $nama,
            password_hash('SandiKhususUji12345', PASSWORD_DEFAULT)
        );

        self::assertStringContainsString('CONVERT(0x' . bin2hex($nama) . ' USING utf8mb4)', $sql);
        self::assertStringNotContainsString($nama, $sql);
    }

    public function testMenolakUsernameNamaDanHashTakSah(): void
    {
        $hash = password_hash('SandiKhususUji12345', PASSWORD_DEFAULT);
        foreach (['a', 'nama admin', "a'; DROP TABLE pengguna; --"] as $username) {
            try {
                AdminHostingSqlService::buat($username, 'Admin Produksi', $hash);
                self::fail('Username berisiko tidak ditolak.');
            } catch (DomainException $e) {
                self::assertStringContainsString('Username', $e->getMessage());
            }
        }
        $this->expectException(DomainException::class);
        AdminHostingSqlService::buat('admin_produksi', "Nama\nTidak Aman", $hash);
    }

    public function testMenolakHashBukanDariPasswordHash(): void
    {
        $this->expectException(DomainException::class);
        AdminHostingSqlService::buat('admin_produksi', 'Admin Produksi', 'plaintext-password');
    }

    public function testGeneratorTidakMemilikiEndpointWebAtauMengubahDatabase(): void
    {
        $path = dirname(__DIR__, 2) . '/app/Commands/BuatAdminHostingSql.php';
        $source = file_get_contents($path);
        self::assertIsString($source);
        self::assertStringContainsString('is_cli()', $source);
        self::assertStringContainsString("fopen(\$hasil, 'x')", $source);
        self::assertStringContainsString('WRITEPATH', $source);
        self::assertStringContainsString('private', $source);
        self::assertStringContainsString('password_hash($sandiSementara, PASSWORD_DEFAULT)', $source);
        self::assertStringNotContainsString('new AuthService()', $source);
        self::assertStringNotContainsString('Database::connect(', $source);
    }
}
