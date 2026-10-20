<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class UiV23PrototipeTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHalamanMasukMemakaiPrototipeTanpaMengubahForm(): void
    {
        $hasil = $this->get('/masuk');

        $hasil->assertStatus(200);
        $hasil->assertSee('a-publik--prototipe');
        $hasil->assertSee('Masuk SLA-SKS');
        $hasil->assertSee('name="username"');
        $hasil->assertSee('name="sandi"');
        $hasil->assertSee('csrf_test_name');
    }

    public function testDashboardAdminTidakDapatDiaksesTanpaSesi(): void
    {
        $this->get('/admin')->assertStatus(302);
    }
}
