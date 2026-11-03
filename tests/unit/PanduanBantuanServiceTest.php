<?php

declare(strict_types=1);

use App\Services\PanduanBantuanService;
use PHPUnit\Framework\TestCase;

/** A13-1 — konten panduan dan pemisahan empat peran, tanpa database. */
final class PanduanBantuanServiceTest extends TestCase
{
    /** @return list<array{tanya:string,jawab:string,path:string,aksi:string}> */
    private static function semuaPertanyaan(string $peran): array
    {
        $r = PanduanBantuanService::untukPeran($peran);
        $hasil = [];
        foreach ($r['kelompok'] as $bagian) {
            array_push($hasil, ...$bagian['pertanyaan']);
        }
        return $hasil;
    }

    private static function sumber(string $file): string
    {
        $s = file_get_contents(dirname(__DIR__,2).'/'.$file);
        self::assertIsString($s);
        return $s;
    }

    public function testSemuaPeranMemperolehPanduanDenganKelompokDanAksiNyata(): void
    {
        foreach (['admin','guru','siswa','pimpinan'] as $peran) {
            $data = PanduanBantuanService::untukPeran($peran);
            self::assertSame($peran,$data['peran']);
            self::assertNotEmpty($data['kelompok']);
            self::assertGreaterThanOrEqual(3,count(self::semuaPertanyaan($peran)));
            foreach ($data['kelompok'] as $bagian) {
                self::assertNotSame('',$bagian['judul']);
                self::assertNotEmpty($bagian['pertanyaan']);
                foreach ($bagian['pertanyaan'] as $q) {
                    self::assertNotSame('',$q['tanya']);
                    self::assertNotSame('',$q['jawab']);
                    self::assertNotSame('',$q['aksi']);
                    self::assertNotSame('',$q['path']);
                }
            }
        }
    }

    public function testSiswaTidakMemperolehTautanPengelolaAtauGuru(): void
    {
        foreach (self::semuaPertanyaan('siswa') as $q) {
            self::assertTrue(str_starts_with($q['path'],'siswa/')
                || $q['path']==='profil');
            self::assertStringNotContainsString('admin/',$q['path']);
            self::assertStringNotContainsString('guru/',$q['path']);
        }
        self::assertCount(1,array_filter(
            self::semuaPertanyaan('siswa'),
            static fn(array $q):bool => $q['path']==='profil'
        ));
    }

    public function testGuruTidakMemperolehTautanAdminAtauDataSiswaLain(): void
    {
        foreach (self::semuaPertanyaan('guru') as $q) {
            self::assertTrue(str_starts_with($q['path'],'guru/')
                || $q['path']==='profil');
            self::assertStringNotContainsString('admin/',$q['path']);
        }
    }

    public function testPimpinanHanyaDiarahkanKeMonitoringDanProfil(): void
    {
        foreach (self::semuaPertanyaan('pimpinan') as $q) {
            self::assertContains($q['path'],['pimpinan/laporan','profil']);
        }
    }

    public function testSemuaTautanPanduanPunyaEndpointGetYangSudahAda(): void
    {
        $routes = self::sumber('app/Config/Routes.php');
        foreach (['admin','guru','siswa','pimpinan'] as $peran) {
            foreach (self::semuaPertanyaan($peran) as $q) {
                $expected="'".$q['path']."'";
                self::assertStringContainsString(
                    '$routes->get('.$expected,$routes,
                    'Tautan '.$q['path'].' belum menjadi endpoint GET.'
                );
            }
        }
    }

    public function testIsiTidakMengarangPraSemesterSebagaiSyaratSertifikat(): void
    {
        $isi = implode(' ',array_column(self::semuaPertanyaan('siswa'),'jawab'));
        self::assertStringContainsString(
            'Pra-Semester bukan syarat penerbitan sertifikat',$isi);
        self::assertStringContainsString(
            'Sumatif tetap wajib',$isi);
        self::assertStringContainsString(
            'permintaan akses ulang bukan kesempatan remedial nilai',$isi);
    }

    public function testPeranTidakDikenalDitolakTanpaFallbackKeAdmin(): void
    {
        $this->expectException(\DomainException::class);
        PanduanBantuanService::untukPeran('pengunjung');
    }

    public function testRuteBantuanHanyaLoginDanMenuUntukEmpatPeran(): void
    {
        $routes=self::sumber('app/Config/Routes.php');
        $nav=self::sumber('app/Services/NavigasiService.php');
        $ctrl=self::sumber('app/Controllers/Bantuan.php');
        $panel=self::sumber('app/Views/layouts/panel.php');
        self::assertStringContainsString("str_contains(\$jalurIkon, 'bantuan') => 'book-open'",$panel);
        self::assertStringContainsString(
            "\$routes->get('bantuan', 'Bantuan::index', ['filter' => 'auth']);",$routes);
        self::assertStringContainsString("'label' => 'Bantuan'",$nav);
        self::assertStringContainsString("'aktif' => \$aktif === 'bantuan'",$nav);
        self::assertStringContainsString("wajib_ganti_sandi",$ctrl);
        self::assertStringContainsString("'private, no-store'",$ctrl);
        self::assertStringContainsString("PanduanBantuanService::untukPeran(\$peran)",$ctrl);
    }

    public function testTampilanMemakaiDetailsNativeDanEscapePadaJawabanSertaTautan(): void
    {
        $view=self::sumber('app/Views/bantuan/index.php');
        self::assertStringContainsString('<details',$view);
        self::assertStringContainsString('<summary>',$view);
        self::assertStringContainsString("esc(\$item['tanya'])",$view);
        self::assertStringContainsString("esc(\$item['jawab'])",$view);
        self::assertStringContainsString("esc(site_url(\$item['path']), 'attr')",$view);
        self::assertStringNotContainsString('<form',$view);
        self::assertStringNotContainsString('target="_blank"',$view);
    }
}
