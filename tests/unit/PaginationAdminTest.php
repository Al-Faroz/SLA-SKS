<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PaginationAdminTest extends TestCase
{
    private static function source(string $file): string
    {
        $content = file_get_contents(dirname(__DIR__, 2) . '/' . $file);
        self::assertIsString($content);
        return $content;
    }

    public function testKomponenPaginasiMempertahankanGenDanDuaHalaman(): void
    {
        helper('url');
        $html = html_entity_decode(view('components/pager', [
            'urlPath' => 'admin/mapel-guru',
            'pageParam' => 'halaman_mapel',
            'halaman' => 3,
            'perHalaman' => 25,
            'total' => 160,
            'filter' => ['gen' => 7, 'halaman_riwayat' => 2],
            'label' => 'penetapan Guru',
            'anchor' => 'judul-pengampu',
        ], ['saveData' => false]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        self::assertStringContainsString('51–75 dari 160 data', $html);
        self::assertStringContainsString('Halaman 3 dari 7', $html);
        self::assertStringContainsString('gen=7&halaman_riwayat=2&halaman_mapel=4', $html);
        self::assertStringContainsString('#judul-pengampu', $html);
        self::assertStringContainsString('Pertama', $html);
        self::assertStringContainsString('Terakhir', $html);
        self::assertStringContainsString('aria-current="page">3', $html);
    }

    public function testHalamanDiLuarRentangDibatasiDanTombolNonaktif(): void
    {
        helper('url');
        $html = view('components/pager', [
            'urlPath' => 'admin/pengelola',
            'pageParam' => 'halaman',
            'halaman' => 999,
            'perHalaman' => 25,
            'total' => 27,
            'filter' => ['cari' => 'uji ruang', 'status' => 'aktif'],
            'label' => 'akun Pengelola',
            'anchor' => 'judul-daftar',
        ], ['saveData' => false]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        self::assertStringContainsString('Halaman 2 dari 2', $html);
        self::assertStringContainsString('26–27 dari 27 data', $html);
        self::assertStringContainsString('cari=uji+ruang&status=aktif&halaman=1', $html);
        self::assertStringContainsString('aria-disabled="true">Terakhir', $html);
    }

    public function testServerMemotongDataMapelDanRiwayatSecaraTerpisah(): void
    {
        $service = self::source('app/Services/ReferensiService.php');
        self::assertStringContainsString('LIMIT ? OFFSET ?', $service);
        self::assertStringContainsString('mapelTotal', $service);
        self::assertStringContainsString('riwayatTotal', $service);
        self::assertStringContainsString('->limit($riwayatPerHalaman, ($riwayatHalaman - 1) * $riwayatPerHalaman)', $service);
        $controller = self::source('app/Controllers/AdminReferensi.php');
        self::assertStringContainsString("'halaman_mapel' => ", $controller);
        self::assertStringContainsString("'halaman_riwayat' => ", $controller);
        self::assertStringContainsString("getGet('gen')", $controller);
    }

    public function testBreadcrumbLinkDanHalamanAktifDibedakan(): void
    {
        $view = self::source('app/Views/admin/mapel_guru.php');
        self::assertStringContainsString('site_url(\'admin\')', $view);
        self::assertStringContainsString('<span aria-current="page">Mapel', $view);
        $css = self::source('public/assets/css/app.css');
        self::assertStringContainsString('.a-page .a-breadcrumb a:nth-of-type(2)', $css);
        self::assertStringContainsString('.a-page .a-breadcrumb [aria-current="page"]', $css);
    }
}
