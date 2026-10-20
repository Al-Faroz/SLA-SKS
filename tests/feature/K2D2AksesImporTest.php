<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K2D2AksesImporTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testSeluruhHalamanDanLaporanImporPerluLogin(): void
    {
        foreach ([
            '/admin/pengelola/impor',
            '/admin/siswa/impor',
            '/admin/pengelola/impor/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            '/admin/siswa/impor/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            '/admin/pengelola/impor/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/galat',
            '/admin/siswa/impor/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb/galat',
        ] as $route) {
            $this->get($route)->assertStatus(302);
        }
    }
}
