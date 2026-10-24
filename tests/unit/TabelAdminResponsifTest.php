<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TabelAdminResponsifTest extends TestCase
{
    private static function isi(string $nama): string
    {
        $path = dirname(__DIR__, 2) . '/' . $nama;
        $isi = file_get_contents($path);
        self::assertIsString($isi, 'Tidak dapat membaca ' . $nama);
        return $isi;
    }

    public function testDaftarPengelolaDanSiswaTetapMemakaiFormDanPagination(): void
    {
        $view = self::isi('app/Views/admin/akun_daftar.php');
        self::assertStringContainsString('a-tbl--cards a-admin-akun__tabel', $view);
        foreach (['Nama', 'Status', 'Aksi'] as $label) {
            self::assertStringContainsString('data-label="' . $label . '"', $view);
        }
        self::assertStringContainsString('$siswa ? \'NISN\' : \'Username\'', $view);
        self::assertStringContainsString('class="a-admin-akun__penempatan"', $view);
        self::assertStringContainsString("csrf_field()", $view);
        self::assertStringContainsString("a-pager", $view);
        self::assertStringContainsString("'halaman' => " . '$tujuan', $view);
        self::assertStringContainsString('reset-sandi', $view);
    }

    public function testTabelPengampuDanRiwayatTetapMemuatKontrol(): void
    {
        $view = self::isi('app/Views/admin/mapel_guru.php');
        self::assertStringContainsString('a-tbl--cards a-tbl--penugasan', $view);
        self::assertStringContainsString('a-tbl--cards a-tbl--riwayat-guru', $view);
        foreach (['Mata pelajaran', 'Guru pengampu', 'Ubah pengampu',
            'Gen', 'Mapel', 'Sebelum → Sesudah', 'Alasan', 'Waktu (UTC)'] as $label) {
            self::assertStringContainsString('data-label="' . $label . '"', $view);
        }
        self::assertStringContainsString('name="guru_id"', $view);
        self::assertStringContainsString('name="alasan"', $view);
        self::assertStringContainsString('Simpan pengampu', $view);
        self::assertStringContainsString("csrf_field()", $view);
    }

    public function testKomponenKartuDokumentasiDanCSSAda(): void
    {
        $css = self::isi('public/assets/css/app.css');
        $doc = self::isi('docs/assets/galeri-komponen.html');
        self::assertStringContainsString('max-width: 980px', $css);
        self::assertStringContainsString('a-tbl--penugasan', $css);
        self::assertStringContainsString('a-admin-akun__tabel.a-tbl--cards', $css);
        self::assertStringContainsString('a-tbl--cards', $doc);
        self::assertStringContainsString('data-label="Nama"', $doc);
    }
}
