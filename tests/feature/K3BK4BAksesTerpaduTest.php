<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class K3BK4BAksesTerpaduTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testSemuaEndpointGuruDanAdminHarusLogin():void
    {
        foreach([
            '/guru/usulan-cp','/guru/paket','/guru/paket/1','/guru/bab/1',
            '/guru/bab/1/checklist','/guru/bab/1/template',
            '/guru/bab/1/impor/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            '/guru/berkas/1','/admin/usulan-cp','/admin/usulan-cp/1'
        ] as $path){
            $this->get($path)->assertStatus(302);
        }
    }
}
