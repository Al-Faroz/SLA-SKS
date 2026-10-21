<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K2EAksesProfilTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testFotoPrivatDanPersetujuanAdminMembutuhkanLogin(): void
    {
        foreach ([
            '/profil',
            '/profil/foto/1',
            '/admin/foto-profil',
        ] as $uri) {
            $this->get($uri)->assertStatus(302);
        }
    }
}
