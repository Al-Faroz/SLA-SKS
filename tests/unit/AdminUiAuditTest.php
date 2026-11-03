<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Regresi statis layout Admin; verifikasi visual tetap di XAMPP. */
final class AdminUiAuditTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $isi=file_get_contents(dirname(__DIR__,2).DIRECTORY_SEPARATOR.$path);
        self::assertIsString($isi,'Tidak bisa membaca '.$path);
        return $isi;
    }

    public function testResetSandiMemilikiKontrasTemaDarkDanLight(): void
    {
        $css=self::sumber('public/assets/css/emerald-glass.css');
        self::assertStringContainsString(
            '.a-akun-reset .a-rincian {',$css);
        self::assertStringContainsString(
            'background:var(--eg-pane-soft);',$css);
        self::assertStringContainsString(
            '.a-akun-reset .a-rincian dd {',$css);
        self::assertStringContainsString('color:var(--e-text);',$css);
        $view=self::sumber('app/Views/admin/akun_reset_konfirmasi.php');
        self::assertStringContainsString('class="a-rincian"',$view);
        self::assertStringContainsString('csrf_field()',$view);
        self::assertStringContainsString('a-btn--bahaya',$view);
    }

    public function testDaftarPengelolaDanSiswaMendapatRuangTabelPenuh(): void
    {
        $view=self::sumber('app/Views/admin/akun_daftar.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('a-admin-akun__grid',$view);
        self::assertStringContainsString('a-admin-akun__tabel',$view);
        self::assertStringContainsString(
            '.a-admin-akun .a-admin-akun__grid {',$css);
        self::assertStringContainsString('grid-template-columns:minmax(0,1fr);',$css);
        self::assertStringContainsString(
            '.a-admin-akun .a-admin-akun__form .a-form {',$css);
        self::assertStringContainsString('a-admin-akun__xlsx-aksi',$view);
        self::assertStringContainsString('a-admin-akun__aksi',$view);
    }

    public function testFormMapelLebarDanBannerK2UsangDihapus(): void
    {
        $view=self::sumber('app/Views/admin/mapel_guru.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('a-mapel-guru__tambah',$view);
        self::assertStringContainsString('a-mapel-guru__form',$view);
        self::assertStringContainsString(
            '.a-mapel-guru__tambah.a-page__editor {',$css);
        self::assertStringNotContainsString(
            'Penugasan Guru dari K2 lama tidak disalin otomatis',$view);
        self::assertStringContainsString('csrf_field()',$view);
    }

    public function testLabelWebsiteTidakDigandakan(): void
    {
        $view=self::sumber('app/Views/admin/identitas.php');
        self::assertStringContainsString(
            "stripos(\$label, 'opsional') === false",$view);
        self::assertStringContainsString('<?= esc($labelTampil) ?>',$view);
        self::assertStringContainsString('data-dirty-form',$view);
    }

    public function testFilterCpTersusunTanpaMengubahProsesSimpan(): void
    {
        $view=self::sumber('app/Views/admin/kurikulum_cp.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('a-cp__filter-aksi',$view);
        self::assertStringContainsString(
            '.a-cp #cp-daftar .a-cp__filter {',$css);
        self::assertStringContainsString(
            '.a-cp #cp-daftar .a-tabel-gulir {',$css);
        self::assertStringContainsString('Terapkan filter',$view);
        self::assertStringContainsString('csrf_field()',$view);
    }
    public function testPilihanPeranPengelolaRingkasDanSubmitTidakTertukar(): void
    {
        $view=self::sumber('app/Views/admin/akun_daftar.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('name="peran[]"',$view);
        self::assertStringContainsString('Maksimal dua peran',$view);
        self::assertStringContainsString(
            '.a-admin-akun .a-admin-akun__form .a-fieldset {',$css);
        self::assertStringContainsString(
            'grid-template-columns:repeat(3,minmax(0,1fr));',$css);
        self::assertStringContainsString(
            '.a-admin-akun .a-admin-akun__form .a-form > button {',$css);
        self::assertStringContainsString('csrf_field()',$view);
    }

    public function testFormDanDaftarReferensiBeradaDalamKolomSendiri(): void
    {
        $view=self::sumber('app/Views/admin/referensi.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('a-referensi-grid__kolom',$view);
        self::assertSame(2,substr_count($view,'class="a-referensi-grid__kolom"'));
        $first=strpos($view,'judul-gen');
        $listGen=strpos($view,'judul-daftar-gen');
        $year=strpos($view,'judul-tahun');
        $listYear=strpos($view,'judul-daftar-tahun');
        self::assertTrue($first<$listGen && $listGen<$year && $year<$listYear);
        self::assertStringContainsString('.a-referensi-grid__kolom {',$css);
        self::assertSame(2,substr_count($view,'csrf_field()'));
    }

    public function testIdentitasMadrasahMemakaiGridDenganAlamatLebarDanDataDirty(): void
    {
        $view=self::sumber('app/Views/admin/identitas.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('a-identitas-editor__form',$view);
        self::assertStringContainsString('a-identitas-editor__lebar',$view);
        self::assertStringContainsString(
            '.a-identitas-editor .a-identitas-editor__form {',$css);
        self::assertStringContainsString('data-dirty-submit',$view);
        self::assertStringContainsString('csrf_field()',$view);
        self::assertStringContainsString('labelTampil',$view);
    }

    public function testProfilFotoKonsistenDanTetapButuhPersetujuanAdmin(): void
    {
        $view=self::sumber('app/Views/akun/profil.php');
        $css=self::sumber('public/assets/css/app.css');
        self::assertStringContainsString('a-profil__foto',$view);
        self::assertStringContainsString('.a-profil__foto > .a-profil__galeri {',$css);
        self::assertStringContainsString('grid-column:2;',$css);
        self::assertStringContainsString(
            'Unggah foto untuk persetujuan',$view);
        self::assertStringContainsString('csrf_field()',$view);
        self::assertStringContainsString('foto_menunggu_id',$view);
        self::assertStringContainsString('Ganti sandi',$view);
    }

}
