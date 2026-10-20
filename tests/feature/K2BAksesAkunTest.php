<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K2BAksesAkunTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testSemuaHalamanAkunBaruDanKonfirmasiResetButuhLogin(): void
    {
        foreach ([
            '/admin/pengelola',
            '/admin/siswa',
            '/admin/pengelola/1/reset-sandi',
            '/admin/siswa/1/reset-sandi',
            '/admin/pengguna',
        ] as $path) {
            $this->get($path)->assertStatus(302);
        }
    }
}
