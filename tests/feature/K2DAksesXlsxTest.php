<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K2DAksesXlsxTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testTemplateDanEksporPengelolaSiswaHanyaUntukPenggunaLogin(): void
    {
        foreach ([
            '/admin/pengelola/template',
            '/admin/siswa/template',
            '/admin/pengelola/ekspor',
            '/admin/siswa/ekspor',
        ] as $path) {
            $this->get($path)->assertStatus(302);
        }
    }
}
