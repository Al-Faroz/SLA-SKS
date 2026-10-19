<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K1AksesAnonimTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testMenuTerlindungiMemerlukanLogin(): void
    {
        foreach (['/profil', '/ganti-sandi', '/siswa', '/guru', '/admin', '/pimpinan', '/admin/pengguna'] as $path) {
            $hasil = $this->get($path);
            $hasil->assertStatus(302);
        }
    }
}
