<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K2CAksesEditStatusTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testEditDanKonfirmasiStatusTidakBisaDiaksesAnonim(): void
    {
        foreach ([
            '/admin/pengelola/1/edit',
            '/admin/siswa/1/edit',
            '/admin/pengelola/1/status',
            '/admin/siswa/1/status',
        ] as $uri) {
            $this->get($uri)->assertStatus(302);
        }
    }
}
