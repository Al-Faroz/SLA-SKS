<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K2AksesAnonimTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHalamanAdminBaruMemerlukanSesi(): void
    {
        foreach (['/admin/referensi', '/admin/mapel-guru', '/admin/mapel-guru?gen=1', '/admin/identitas'] as $uri) {
            $this->get($uri)->assertStatus(302);
        }
    }
}
