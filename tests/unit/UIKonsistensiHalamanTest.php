<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/** Regression visual v2.3: halaman lama dan baru memakai satu layout nyata. */
final class UIKonsistensiHalamanTest extends CIUnitTestCase
{
    private function berkas(string $path): string
    {
        $isi = file_get_contents(ROOTPATH . $path);
        $this->assertIsString($isi, $path);
        return $isi;
    }

    public function testLayoutPublikDanPanelSelaluMemakaiDesainV23(): void
    {
        $panel = $this->berkas('app/Views/layouts/panel.php');
        $publik = $this->berkas('app/Views/layouts/publik.php');
        $this->assertStringContainsString('class="a-app a-app--prototipe"', $panel);
        $this->assertStringContainsString('class="a-publik a-publik--prototipe"', $publik);
        $this->assertStringNotContainsString('! empty($uiPrototipe)', $panel);
        $this->assertStringNotContainsString('! empty($uiPrototipe)', $publik);
        $this->assertStringContainsString('base_url(\'public/assets/css/app.css\')', $panel);
        $this->assertStringContainsString('base_url(\'public/assets/css/app.css\')', $publik);
    }

    public function testSemuaHalamanPanelMemakaiPageContainerDanSatuJudul(): void
    {
        $daftar = [
            'admin/akun_daftar.php', 'admin/akun_edit.php',
            'admin/akun_impor.php', 'admin/akun_impor_pratinjau.php',
            'admin/akun_reset_hasil.php', 'admin/akun_reset_konfirmasi.php',
            'admin/akun_status_konfirmasi.php', 'admin/beranda_prototipe.php',
            'admin/cp_detail.php', 'admin/cp_form_versi.php',
            'admin/cp_terbit_konfirmasi.php', 'admin/foto_profil_tinjau.php',
            'admin/identitas.php', 'admin/kurikulum_cp.php',
            'admin/mapel_guru.php', 'admin/referensi.php',
            'akun/beranda.php', 'akun/daftar_pengguna.php', 'akun/profil.php',
        ];
        foreach ($daftar as $file) {
            $view = $this->berkas('app/Views/' . $file);
            $this->assertSame(1, preg_match_all('/<h1\b/', $view), $file);
            $this->assertMatchesRegularExpression('/class="[^"]*\ba-page\b/', $view, $file);
            $this->assertSame(substr_count($view, '<header'), substr_count($view, '</header>'), $file);
        }
    }

    public function testFormLamaDanBaruTetapMemilikiProteksiCsrf(): void
    {
        foreach ([
            'admin/referensi.php', 'admin/mapel_guru.php', 'admin/identitas.php',
            'admin/akun_daftar.php', 'admin/akun_edit.php', 'admin/akun_impor.php',
            'admin/kurikulum_cp.php', 'admin/cp_form_versi.php',
            'admin/cp_terbit_konfirmasi.php', 'akun/daftar_pengguna.php',
        ] as $file) {
            $sumber = $this->berkas('app/Views/' . $file);
            $forms = preg_match_all('/<form\b[^>]*method="post"/', $sumber);
            $this->assertGreaterThan(0, $forms, $file);
            $this->assertSame($forms, substr_count($sumber, 'csrf_field()'), $file);
        }
    }

    public function testCpPanjangMemilikiNavigasiKeIdNyataDanFormTerbuka(): void
    {
        $sumber = $this->berkas('app/Views/admin/kurikulum_cp.php');
        foreach (['kurikulum-tambah', 'kurikulum-daftar', 'cp-tambah', 'cp-daftar'] as $id) {
            $this->assertStringContainsString('href="#' . $id . '"', $sumber);
            $this->assertStringContainsString('id="' . $id . '"', $sumber);
        }
        $this->assertStringContainsString('<details class="a-cp__details" open>', $sumber);
        $this->assertStringContainsString('name="rumusan"', $sumber);
        $this->assertStringContainsString('name="sumber_url"', $sumber);
        $this->assertStringContainsString('name="tanggal_berlaku"', $sumber);
    }

    public function testSatuCssDanTokenDipakaiKonsisten(): void
    {
        $sumber = $this->berkas('public/assets/css/app.css');
        foreach ([
            '--a-font-isi', '--a-font-judul-kartu', '--a-tinggi-input',
            '--a-padding-kartu', '.a-page__heading', '.a-page__jumps',
            '.a-cp__details', '@media(max-width:390px)',
        ] as $teks) {
            $this->assertStringContainsString($teks, $sumber);
        }
        $this->assertSame(substr_count($sumber, '{'), substr_count($sumber, '}'));
    }
}
