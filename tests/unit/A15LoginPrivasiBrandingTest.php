<?php

declare(strict_types=1);

use App\Services\KebijakanPrivasiContohService;
use App\Services\KebijakanPrivasiValidasiService;
use PHPUnit\Framework\TestCase;

/**
 * A15: pengamanan identitas MTsN 4 Jombang, banner user dan naskah contoh.
 * Kontrak sumber saja; bukan klaim browser XAMPP atau pengesahan madrasah.
 */
final class A15LoginPrivasiBrandingTest extends TestCase
{
    private static function root(string $path): string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }

    private static function source(string $path): string
    {
        $s=file_get_contents(self::root($path));
        self::assertIsString($s);
        return $s;
    }

    public function testLoginMenggunakanCopySlaSksDanBannerAsli(): void
    {
        $s=self::source('app/Views/akun/masuk.php');
        self::assertStringContainsString('Pembelajaran terarah,', $s);
        self::assertStringContainsString('capaian terukur.', $s);
        self::assertStringContainsString('asesmen bertahap', $s);
        self::assertStringContainsString('enam semester', $s);
        self::assertStringContainsString('MTsN 4 Jombang', $s);
        self::assertStringContainsString('login-banner-mtsn4-jombang.webp', $s);
        self::assertStringContainsString('alt="Identitas MTsN 4 Jombang', $s);
        self::assertStringNotContainsString('a-proto-orbit--one', $s);
        self::assertStringNotContainsString('Belajar dengan arah yang', $s);
        self::assertStringContainsString('name="username"', $s);
        self::assertStringContainsString('name="sandi"', $s);
        self::assertStringContainsString('csrf_field()', $s);
        self::assertStringContainsString("site_url('masuk')", $s);
    }

    public function testLogoMadrasahDanFaviconAdaPadaPublikDanPanel(): void
    {
        foreach(['app/Views/layouts/publik.php',
                 'app/Views/layouts/panel.php'] as $path){
            $s=self::source($path);
            self::assertStringContainsString('logo-mtsn4-jombang.webp', $s);
            self::assertStringContainsString('<link rel="icon" type="image/webp"', $s);
            self::assertStringContainsString('a-identitas__mark--logo', $s);
            self::assertStringNotContainsString('aria-hidden="true">S</span>', $s);
        }
        $s=self::source('app/Views/akun/masuk.php');
        self::assertStringContainsString('a-proto-login-icon--madrasah', $s);
    }

    public function testAssetAsliTersimpanDiPublicDanDapatDikenaliDecoder(): void
    {
        $cases=[
            'logo-mtsn4-jombang.webp'=>[96,95],
            'login-banner-mtsn4-jombang.webp'=>[560,209],
        ];
        foreach($cases as $file=>[$lebar,$tinggi]){
            $path=self::root('public/assets/img/branding/'.$file);
            self::assertFileExists($path);
            self::assertGreaterThan(1500,filesize($path));
            self::assertLessThan(200000,filesize($path));
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
            self::assertSame('image/webp',$mime);
            $dim=getimagesize($path);
            self::assertIsArray($dim);
            self::assertSame($lebar,$dim[0]);
            self::assertSame($tinggi,$dim[1]);
        }
    }

    public function testNaskahPrivasiAdalahContohBelumTerbitDanTetapMelaluiAdmin(): void
    {
        $teks=KebijakanPrivasiContohService::naskah();
        self::assertGreaterThan(1800,mb_strlen($teks,'UTF-8'));
        foreach([
            'Jenis informasi yang diproses',
            'Tujuan penggunaan data',
            'Akses berdasarkan peran',
            'Penyimpanan dan perlindungan',
            'Hak pengguna dan permintaan perbaikan',
            'Perubahan kebijakan dan kontak',
            'setelah diperiksa, disahkan, dan diterbitkan',
        ] as $label) {
            self::assertStringContainsString($label,$teks);
        }
        $valid=KebijakanPrivasiValidasiService::validasiNaskah([
            'versi'=>'A15-2026.11','tanggal_berlaku'=>'2026-11-04',
            'teks'=>$teks,
        ]);
        // Git di Windows dapat melakukan checkout CRLF; Service secara sengaja
        // menormalkan naskah menjadi LF sebelum disimpan dan dihitung checksum.
        $teksNormal=trim(str_replace(["\r\n", "\r"], "\n", $teks));
        self::assertSame($teksNormal,$valid['teks']);
        self::assertSame(hash('sha256',$teksNormal),$valid['checksum_sha256']);
        $versiCrLf=KebijakanPrivasiValidasiService::validasiNaskah([
            'versi'=>'A15-2026.11','tanggal_berlaku'=>'2026-11-04',
            'teks'=>str_replace("\n", "\r\n", $teksNormal),
        ]);
        self::assertSame($valid['teks'],$versiCrLf['teks']);
        self::assertSame($valid['checksum_sha256'],$versiCrLf['checksum_sha256']);
        $editor=self::source('app/Views/admin/kebijakan_privasi_editor.php');
        self::assertStringContainsString('KebijakanPrivasiContohService::naskah()', $editor);
        self::assertStringContainsString('Draf contoh — belum menjadi Kebijakan Privasi resmi.', $editor);
        $publik=self::source('app/Controllers/Publik.php');
        self::assertStringContainsString('publikAktif()', $publik);
        self::assertStringNotContainsString('KebijakanPrivasiContohService', $publik);
        self::assertStringContainsString("pastikanKonfirmasiPengesahan",self::source(
            'app/Services/KebijakanPrivasiService.php'));
    }

    public function testResponsifDarkLightDanTanpaDependensiCdn(): void
    {
        $css=self::source('public/assets/css/emerald-glass.css');
        foreach([
            'a-proto-login-illustration--madrasah',
            'a-proto-login-icon--madrasah',
            'a-identitas__mark--logo',
            'object-fit:cover',
            '@media (max-width:767px)',
        ] as $selector){
            self::assertStringContainsString($selector,$css);
        }
        // Batasi kontrak ke blok A15: aturan shell/tombol dan batch UI sesudahnya
        // boleh punya kebutuhan CSS berbeda tanpa mengubah branding login.
        $start=strpos($css,'/* A15 — identitas MTsN');
        $end=strpos($css,'/* Vibrant Glass Shell', $start === false ? 0 : $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $section=substr($css,$start,$end-$start);
        self::assertStringNotContainsString('https://',$section);
        self::assertStringNotContainsString('!important',$section);
    }
}
