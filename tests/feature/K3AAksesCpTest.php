<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K3AAksesCpTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHalamanCpDanKonfirmasiTidakDapatDiaksesAnonim(): void
    {
        foreach ([
            '/admin/kurikulum-cp',
            '/admin/kurikulum-cp/1',
            '/admin/kurikulum-cp/1/versi-baru',
            '/admin/kurikulum-cp/versi/1/edit',
            '/admin/kurikulum-cp/versi/1/terbit',
        ] as $path) {
            $this->get($path)->assertStatus(302);
        }
    }
}
