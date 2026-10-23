<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Uji HTTP tamu dan route filter sebenarnya (tanpa akun atau data pribadi).
 * Pengujian login Guru dan UI lokal tetap perlu dijalankan di XAMPP.
 */
final class GuruPetaAtpHttpTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (! defined('WRITEPATH')) {
            define('WRITEPATH', dirname(__DIR__, 2) . '/writable/');
        }
    }

    public function testPengunjungTanpaSesiDialihkanDariHalamanPeta(): void
    {
        $hasil = $this->get('guru/peta-atp');

        $hasil->assertRedirectTo('masuk');
    }

    public function testPostKeHalamanBacaSajaTidakMenjalankanController(): void
    {
        $hasil = $this->post('guru/peta-atp');

        self::assertNotSame(200, $hasil->getStatusCode());
    }
}
