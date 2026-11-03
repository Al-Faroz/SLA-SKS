<?php

declare(strict_types=1);

use App\Services\KontakResmiMadrasahService;
use App\Services\NavigasiService;
use CodeIgniter\Database\BaseConnection;
use PHPUnit\Framework\TestCase;

final class KontakResmiMadrasahServiceTest extends TestCase
{
    private BaseConnection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect([
            'DSN' => '', 'database' => ':memory:', 'DBDriver' => 'SQLite3',
            'DBPrefix' => '', 'DBDebug' => true, 'pConnect' => false,
            'foreignKeys' => false, 'busyTimeout' => 1000, 'synchronous' => null,
            'failover' => [],
        ], false);
        $this->db->query('CREATE TABLE pengaturan (kunci TEXT PRIMARY KEY, nilai TEXT, diperbarui_oleh INTEGER)');
        foreach ([
            'nama_madrasah' => 'MTs Uji',
            'alamat_madrasah' => 'Jalan Contoh 1',
            'telepon' => '+62 (321) 123-456',
            'email' => 'sekolah@example.test',
            'website' => 'https://example.test/info',
            'kepala_madrasah_nip' => 'RAHASIA',
            'nama_pengelola' => 'PRIBADI',
        ] as $key => $val) {
            $this->db->table('pengaturan')->insert(['kunci' => $key, 'nilai' => $val]);
        }
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function svc(string $peran = 'siswa', string $status = 'aktif', int $ganti = 0): KontakResmiMadrasahService
    {
        return new KontakResmiMadrasahService($this->db, static fn (): array => [
            'id' => 17, 'nama' => 'Penguji', 'peran_aktif' => $peran,
            'status' => $status, 'wajib_ganti_sandi' => $ganti,
        ]);
    }

    public function testSemuaPeranDapatMembacaKontakResmiTanpaMutasi(): void
    {
        foreach (['admin', 'guru', 'siswa', 'pimpinan'] as $peran) {
            $hasil = $this->svc($peran)->ambil();
            self::assertSame($peran, $hasil['peran']);
            self::assertSame('MTs Uji', $hasil['nama_madrasah']);
            self::assertSame('tel:+62321123456', $hasil['tautan_telepon']);
            self::assertSame('mailto:sekolah@example.test', $hasil['tautan_email']);
            self::assertSame('https://example.test/info', $hasil['tautan_website']);
            self::assertArrayNotHasKey('kepala_madrasah_nip', $hasil);
            self::assertStringNotContainsString('PRIBADI', json_encode($hasil));
        }
        self::assertSame(7, $this->db->table('pengaturan')->countAllResults());
    }

    public function testAkunNonaktifDanSandiSementaraDitolak(): void
    {
        foreach ([
            ['siswa', 'nonaktif', 0],
            ['guru', 'aktif', 1],
            ['unknown', 'aktif', 0],
        ] as [$peran, $status, $ganti]) {
            try {
                $this->svc($peran, $status, $ganti)->ambil();
                self::fail('Harus ditolak');
            } catch (\DomainException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testTautanBukanSkemaBerbahayaDanTidakMembocorkanKolomPribadi(): void
    {
        $this->db->table('pengaturan')->where('kunci', 'website')
            ->update(['nilai' => 'javascript:alert(1)']);
        $this->db->table('pengaturan')->where('kunci', 'email')
            ->update(['nilai' => 'admin@example.test?subject=<script>']);
        $this->db->table('pengaturan')->where('kunci', 'telepon')
            ->update(['nilai' => '1234;alert(1)']);
        $hasil = $this->svc()->ambil();
        self::assertSame('', $hasil['tautan_website']);
        self::assertSame('', $hasil['tautan_email']);
        self::assertSame('', $hasil['tautan_telepon']);
        self::assertSame('javascript:alert(1)', $hasil['website']);
        self::assertArrayNotHasKey('nama_pengelola', $hasil);
    }

    public function testNilaiKosongTidakMenciptakanAlamatRekaan(): void
    {
        $this->db->table('pengaturan')->whereIn('kunci', [
            'alamat_madrasah','telepon','email','website',
        ])->update(['nilai' => null]);
        $hasil = $this->svc()->ambil();
        self::assertSame('', $hasil['alamat']);
        self::assertSame('', $hasil['tautan_telepon']);
        self::assertSame('', $hasil['tautan_website']);
    }

    public function testNavigasiDanRuteLoginOnlyDanEscapeView(): void
    {
        foreach (['admin', 'guru', 'siswa', 'pimpinan'] as $peran) {
            $menu = NavigasiService::untuk($peran, 'hubungi_admin');
            $kontak = array_values(array_filter($menu, static fn ($m): bool =>
                ($m['path'] ?? '') === 'hubungi-admin'));
            self::assertCount(1, $kontak);
            self::assertTrue($kontak[0]['aktif']);
        }

        $dasar = dirname(__DIR__, 2) . '/';
        $routes = file_get_contents($dasar . 'app/Config/Routes.php');
        $view = file_get_contents($dasar . 'app/Views/bantuan/hubungi_admin.php');
        $controller = file_get_contents($dasar . 'app/Controllers/HubungiAdmin.php');
        self::assertStringContainsString("'hubungi-admin', 'HubungiAdmin::index', ['filter' => 'auth']", $routes);
        self::assertStringContainsString("esc(\$kontak['alamat'])", $view);
        self::assertStringContainsString("esc(\$kontak['tautan_website'], 'attr')", $view);
        self::assertStringContainsString('private, no-store', $controller);
        self::assertStringNotContainsString('method="post"', $view);
        self::assertStringNotContainsString('nama_pengelola', $view);
    }
}
