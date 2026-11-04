<?php

declare(strict_types=1);

use App\Services\AuthService;
use App\Services\NavigasiService;
use App\Services\PanduanBantuanService;
use App\Services\PenggunaAdminService;
use PHPUnit\Framework\TestCase;

/** Revisi 04-11-2026: tidak memakai data XAMPP/credential sebenarnya. */
final class A13RevisiAkunNavigasiTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $content = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($content);
        return $content;
    }

    public function testKebijakanSandiAwalMenurutRoleTermasukAkunRangkap(): void
    {
        foreach (['guru', 'pimpinan', 'siswa'] as $peran) {
            self::assertSame(['wajib' => false, 'disarankan' => true],
                AuthService::kebijakanSandi([$peran], 1));
            self::assertSame(['wajib' => false, 'disarankan' => false],
                AuthService::kebijakanSandi([$peran], 0));
        }
        foreach ([['admin'], ['admin', 'guru'], ['admin', 'pimpinan']] as $roles) {
            self::assertSame(['wajib' => true, 'disarankan' => false],
                AuthService::kebijakanSandi($roles, 1));
            self::assertSame(['wajib' => false, 'disarankan' => false],
                AuthService::kebijakanSandi($roles, 0));
        }
    }

    public function testResetAdminAcakDanNonAdminDefault(): void
    {
        foreach ([['admin'], ['admin', 'guru'], ['admin', 'pimpinan']] as $roles) {
            self::assertSame('acak', PenggunaAdminService::tipeResetSandi($roles));
        }
        foreach ([['guru'], ['pimpinan'], ['guru', 'pimpinan'], ['siswa']] as $roles) {
            self::assertSame('default', PenggunaAdminService::tipeResetSandi($roles));
        }
        $a = PenggunaAdminService::buatSandiSementara();
        $b = PenggunaAdminService::buatSandiSementara();
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/D', $a);
        self::assertNotSame($a, $b);

        $service = self::sumber('app/Services/PenggunaAdminService.php');
        self::assertStringContainsString('password_hash($baru, PASSWORD_DEFAULT)', $service);
        self::assertStringContainsString("'versi_sesi' => (int) $target['versi_sesi'] + 1", $service);
        self::assertStringContainsString("sandiDefault === null || strlen", $service);
        self::assertStringContainsString("['mode' => $isAdmin ? 'acak' : 'default'", $service);
        self::assertStringNotContainsString('INSERT INTO pengguna', $service);
    }

    public function testGrupMenuBerisiLimaItemDenganHakEmpatPeran(): void
    {
        $semua = ['pengumuman', 'bantuan', 'hubungi-admin'];
        foreach (['admin', 'guru', 'siswa', 'pimpinan'] as $role) {
            $menu = NavigasiService::untuk($role, 'bantuan');
            $groups = array_values(array_filter($menu, static fn (array $i): bool =>
                ($i['label'] ?? '') === 'Informasi & Bantuan'));
            self::assertCount(1, $groups);
            self::assertSame('book-open', $groups[0]['ikon']);
            self::assertTrue($groups[0]['aktif']);
            $paths = array_column($groups[0]['submenu'], 'path');
            foreach ($semua as $p) {
                self::assertContains($p, $paths);
            }
            if ($role === 'admin') {
                self::assertContains('admin/faq', $paths);
                self::assertContains('admin/pengumuman', $paths);
                self::assertCount(5, $paths);
            } elseif ($role === 'guru') {
                self::assertContains('guru/pengumuman', $paths);
                self::assertNotContains('admin/faq', $paths);
                self::assertCount(4, $paths);
            } else {
                self::assertCount(3, $paths);
                self::assertNotContains('admin/faq', $paths);
                self::assertNotContains('guru/pengumuman', $paths);
            }
            self::assertCount(0, array_filter($menu, static fn (array $i): bool =>
                isset($i['path']) && in_array($i['path'],
                    ['pengumuman', 'bantuan', 'hubungi-admin', 'admin/faq', 'guru/pengumuman'], true)));
        }
        $group = array_values(array_filter(NavigasiService::untuk('guru', 'pengumuman_kelola'),
            static fn (array $i): bool => ($i['label'] ?? '') === 'Informasi & Bantuan'));
        self::assertTrue($group[0]['aktif']);
        $panel = self::sumber('app/Views/layouts/panel.php');
        self::assertStringContainsString('a-side__group', $panel);
        self::assertStringContainsString("$tautan['ikon']", $panel);
    }

    public function testResetDanProfilTerlindungiCSRFDanTidakMengulangSandiDefault(): void
    {
        $confirm = self::sumber('app/Views/admin/akun_reset_konfirmasi.php');
        $result = self::sumber('app/Views/admin/akun_reset_hasil.php');
        $profile = self::sumber('app/Views/akun/profil.php');
        $controller = self::sumber('app/Controllers/AdminPengguna.php');
        $authController = self::sumber('app/Controllers/Akun.php');
        self::assertStringContainsString('csrf_field()', $confirm);
        self::assertStringContainsString('name="sandi_default"', $confirm);
        self::assertStringContainsString('name="sandi_default_konfirmasi"', $confirm);
        self::assertStringContainsString('!$akunAdmin', $confirm);
        self::assertStringContainsString('sandi_default_konfirmasi', $controller);
        self::assertStringContainsString("modeReset === 'acak'", $result);
        self::assertStringContainsString('<?= esc((string)$sandiSementara', $result);
        self::assertStringContainsString('csrf_field()', $profile);
        self::assertStringContainsString('name="sandi_lama"', $profile);
        self::assertStringContainsString('name="sandi_konfirmasi"', $profile);
        self::assertStringContainsString("site_url('profil')", $authController);
        self::assertStringContainsString('sandiDisarankan', $profile);
    }

    public function testPanduanDanDrafFaqMengikutiAturanBaru(): void
    {
        $panduan = PanduanBantuanService::untukPeran('siswa');
        $jawaban = [];
        foreach ($panduan['kelompok'] as $kelompok) {
            foreach ($kelompok['pertanyaan'] as $p) {
                $jawaban[] = $p['jawab'];
            }
        }
        self::assertStringContainsString('disarankan mengganti sandi awal', implode(' ', $jawaban));
        self::assertStringContainsString('Admin tetap wajib', implode(' ', $jawaban));
        $faq = self::sumber('docs/DRAF-FAQ-RESMI-SLA-SKS-2026-11-04.md');
        self::assertSame(28, preg_match_all('/^## [0-9]{2}\\./m', $faq));
        self::assertStringContainsString('Status: DRAF', $faq);
        self::assertStringContainsString('Sumatif tetap wajib', $faq);
        self::assertStringContainsString('Pra-Semester bukan syarat', $faq);
    }
}
