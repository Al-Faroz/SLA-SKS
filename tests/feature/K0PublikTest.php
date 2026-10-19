<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K0PublikTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testBerandaMengarahkanKeHalamanAkses(): void
    {
        $hasil = $this->get('/');

        $hasil->assertStatus(302);
    }

    public function testHalamanAksesTidakMenawarkanFormLoginPalsu(): void
    {
        $hasil = $this->get('/masuk');

        $hasil->assertStatus(200);
        $hasil->assertSee('Akses akun belum dibuka');
        $hasil->assertDontSee('<form', false);
    }

    public function testKebijakanPrivasiBelumDisahkanTidakDianggapTerbit(): void
    {
        $hasil = $this->get('/kebijakan-privasi');

        $hasil->assertStatus(503);
        $hasil->assertSee('Kebijakan resmi madrasah belum tersedia.');
    }

    public function testAlamatTidakDikenalMenghasilkan404(): void
    {
        $hasil = $this->get('/halaman-yang-tidak-ada-k0');

        $hasil->assertStatus(404);
        $hasil->assertSee('Halaman tidak ditemukan');
    }
}
