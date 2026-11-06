<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Gerbang penerimaan statis rute v2.5: memeriksa semua definisi rute,
 * controller/method, filter peran, dan keamanan dasar.
 *
 * Tidak mengakses DB, tidak melakukan request HTTP, dan TIDAK menggantikan
 * E2E browser dengan data simulasi di XAMPP.
 */
final class FinalisasiKontrakIntegrasiTest extends TestCase
{
    private static function berkas(string $path): string
    {
        $data = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
        self::assertIsString($data, $path . ' harus ada');
        return $data;
    }

    /** @return array<string,array{verb:string,path:string,handler:string,filter:string}> */
    private static function rute(): array
    {
        $source = self::berkas('app/Config/Routes.php');
        $pattern = <<<'REGEX'
~\$routes->(?P<verb>get|post)\(\s*'(?P<path>[^']+)'\s*,\s*'(?P<handler>[^']+)'(?:\s*,\s*\[\s*'filter'\s*=>\s*'(?P<filter>[^']+)'\s*\])?\s*\);~m
REGEX;
        $count = preg_match_all($pattern, $source, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
        self::assertNotFalse($count);
        self::assertGreaterThan(160, $count, 'Periksa parser bila kontrak sintaks rute berubah');

        $routes = [];
        foreach ($matches as $match) {
            $key = $match['verb'] . ' ' . $match['path'];
            self::assertArrayNotHasKey($key, $routes, 'Rute duplikat: ' . $key);
            $routes[$key] = [
                'verb' => $match['verb'],
                'path' => $match['path'],
                'handler' => $match['handler'],
                'filter' => (string) ($match['filter'] ?? ''),
            ];
        }
        return $routes;
    }

    public function testSemuaRuteAkademikMemilikiFilterPeranYangTepat(): void
    {
        $public = [
            'get /', 'get masuk', 'post masuk', 'get kebijakan-privasi',
            'get verifikasi/(:segment)',
        ];

        foreach (self::rute() as $key => $route) {
            $prefix = strtok($route['path'], '/');
            if (in_array($key, $public, true)) {
                self::assertSame('', $route['filter'], $key . ' publik');
                continue;
            }
            if (in_array($prefix, ['admin', 'guru', 'siswa', 'pimpinan'], true)) {
                self::assertSame('auth:' . $prefix, $route['filter'], $key);
            } else {
                self::assertSame('auth', $route['filter'], $key . ' harus login');
            }
        }
    }

    public function testSeluruhHandlerRuteTerdefinisiSebagaiMethodController(): void
    {
        foreach (self::rute() as $key => $route) {
            $parts = explode('::', $route['handler'], 2);
            self::assertCount(2, $parts, 'Target rute tidak valid: ' . $key);
            $controller = $parts[0];
            $method = explode('/', $parts[1], 2)[0];
            $file = dirname(__DIR__, 2) . '/app/Controllers/' . $controller . '.php';
            self::assertFileExists($file, 'Controller tidak ditemukan: ' . $key);
            $class = 'App\\Controllers\\' . $controller;
            self::assertTrue(class_exists($class), 'Controller tidak dapat dimuat: ' . $key);
            self::assertTrue(method_exists($class, $method), 'Method tidak ada: ' . $key);
        }
    }

    public function testRangkaianAlurTpSumatifPenilaianSertifikatDanPraSemesterTetapAda(): void
    {
        $routes = self::rute();
        foreach ([
            'post guru/peta-atp/konfirmasi/(:num)' => 'GuruAtpKonfirmasi::simpan',
            'post guru/tp/(:num)/kesiapan' => 'GuruKesiapanTp::siapkan',
            'post siswa/tp/(:num)/mulai' => 'SiswaPembelajaran::mulaiTp',
            'post siswa/tp/(:num)/diagnostik/mulai' => 'SiswaUjian::mulai',
            'post siswa/tp/(:num)/materi/(:num)/kegiatan/(:segment)/(:segment)' => 'SiswaKegiatanMateri::simpan',
            'post siswa/tp/(:num)/sumatif/mulai' => 'SiswaSumatif::mulai',
            'post siswa/tp/(:num)/akses-ulang/(:segment)' => 'SiswaAksesUlang::minta',
            'post guru/akses-ulang/(:num)' => 'GuruUjianBatal::simpan',
            'post guru/penilaian/(:segment)/(:num)' => 'GuruPenilaian::simpan',
            'post guru/koreksi-nilai/(:segment)/(:num)' => 'GuruKoreksiNilai::proses',
            'get siswa/sertifikat' => 'SiswaSertifikat::index',
            'get siswa/sertifikat/(:num)/unduh' => 'SiswaSertifikat::unduh',
            'get verifikasi/(:segment)' => 'VerifikasiSertifikat::index',
            'post guru/pra-semester/(:num)/word/review/(:segment)/konfirmasi' => 'GuruPraSemester::konfirmasiWord',
            'post admin/pra-semester/(:num)/sesi' => 'AdminPraSemester::simpanSesi',
            'post admin/pra-semester/(:num)/sesi/(:num)/peserta' => 'AdminPraSemester::tambahPeserta',
            'post siswa/pra-semester/(:num)/mulai' => 'SiswaPraSemester::mulai',
            'get pimpinan/laporan' => 'PimpinanLaporan::index',
        ] as $key => $target) {
            self::assertArrayHasKey($key, $routes);
            self::assertStringStartsWith($target, $routes[$key]['handler']);
        }
    }

    public function testMutasiPentingTidakTerdaftarSebagaiGetDanApprovalLamaTidakAktif(): void
    {
        $routes = self::rute();
        foreach ([
            'siswa/tp/(:num)/diagnostik/mulai',
            'siswa/tp/(:num)/sumatif/mulai',
            'siswa/pra-semester/(:num)/mulai',
            'guru/koreksi-nilai/(:segment)/(:num)/proses',
            'guru/tp/(:num)/kesiapan/terbit',
            'admin/pra-semester/(:num)/sesi',
            'admin/sertifikat/aset/(:num)/aktifkan',
        ] as $path) {
            self::assertArrayNotHasKey('get ' . $path, $routes, 'Mutasi tidak boleh menggunakan GET: ' . $path);
        }
        $source = self::berkas('app/Config/Routes.php');
        self::assertStringNotContainsString('AdminKoreksiNilai::', $source);
        self::assertStringNotContainsString('GuruKoreksiNilai::ajukan', $source);
        self::assertArrayNotHasKey('post admin/sertifikat/(:num)/terbit', $routes);
    }

    public function testCsrfGlobalAutoRouteDitutupDanRootTetapTerlindungi(): void
    {
        $filters = self::berkas('app/Config/Filters.php');
        $routing = self::berkas('app/Config/Routing.php');
        $apache = self::berkas('.htaccess');

        self::assertMatchesRegularExpression(
            '~\\$globals\s*=\s*\[\s*["\x27]before["\x27]\s*=>\s*\[(?:(?!\],).)*["\x27]csrf["\x27]~s',
            $filters
        );
        self::assertStringContainsString('public bool $autoRoute = false;', $routing);
        foreach (['app', 'vendor', 'writable', 'database', 'docs'] as $private) {
            self::assertStringContainsString($private, $apache);
        }
        self::assertStringContainsString('index.php/$1', $apache);
    }
}
