<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\KkmTpService;
use DomainException;
use PHPUnit\Framework\TestCase;

final class KkmTpServiceTest extends TestCase
{
    public function testValidasiBatasKKM(): void
    {
        self::assertSame(1, KkmTpService::validasi('1', false));
        self::assertSame(75, KkmTpService::validasi(' 75 ', false));
        self::assertSame(100, KkmTpService::validasi('100', false));
        self::assertNull(KkmTpService::validasi('', true));
        self::assertNull(KkmTpService::validasi(null, true));
    }

    public function testNilaiDiLuarSyaratDitolak(): void
    {
        foreach (['0', '101', '-1', '75.5', '70abc', ' ', '200', '1e2'] as $input) {
            try {
                KkmTpService::validasi($input, false);
                self::fail('KKM ilegal diterima: ' . $input);
            } catch (DomainException $e) {
                self::assertStringContainsString('KKM', $e->getMessage());
            }
        }
    }

    public function testDefaultAdminDanOverrideGuruMemakaiSatuKKM(): void
    {
        self::assertNull(KkmTpService::pilihEfektif(null, null));
        self::assertSame(80, KkmTpService::pilihEfektif(80, null));
        self::assertSame(76, KkmTpService::pilihEfektif(80, 76));
        self::assertSame(76, KkmTpService::pilihEfektif(null, 76));
    }

    public function testIsianBawaanAdminTidakMenjadiOverrideGuru(): void
    {
        self::assertNull(KkmTpService::normalisasiKhusus(80, 80));
        self::assertSame(85, KkmTpService::normalisasiKhusus(85, 80));
        self::assertNull(KkmTpService::normalisasiKhusus(null, 80));
        self::assertSame(80, KkmTpService::normalisasiKhusus(80, null));
    }

    public function testTpSiapSelaluMenampilkanKKMTerpinBukanDefaultAdminTerbaru(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/app/Controllers/GuruBankSoal.php');
        $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/guru/bank_soal.php');
        self::assertIsString($source);
        self::assertIsString($view);
        self::assertStringContainsString("['status'] ?? '') === 'siap'", $source);
        self::assertStringContainsString("KkmTpService::validasi((string) \\$tpKkm['kkm'], false)", $source);
        self::assertStringContainsString("'sumber'] = 'snapshot'", $source);
        self::assertStringContainsString('Snapshot TP Siap', $view);
    }

    public function testAdminBelumSetDefaultMenyatakanContohBelumBerlaku(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/kkm.php');
        self::assertIsString($view);
        self::assertStringContainsString('belum berlaku', $view);
        self::assertStringContainsString("old('kkm_default', \\$kkm_default ?? '80')", $view);
        self::assertStringContainsString('sebelum Admin menekan Simpan', $view);
    }

    public function testKeduaTabMemakaiFormYangSamaDenganAksiJelas(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/guru/bank_soal.php');
        self::assertIsString($view);
        self::assertStringContainsString("['diagnostik' => 'Diagnostik', 'sumatif' => 'Sumatif']", $view);
        self::assertStringContainsString('Pengaturan KKM TP', $view);
        self::assertStringContainsString("name=\"pilihan\" value=\"khusus\"", $view);
        self::assertStringContainsString("name=\"pilihan\" value=\"default\"", $view);
        self::assertStringContainsString("name=\"kkm_khusus\"", $view);
        self::assertStringContainsString('Nilai otomatis terisi dari default Admin', $view);
        self::assertStringContainsString('Simpan KKM', $view);
        self::assertStringContainsString("value=\"<?= \$kkm_info['efektif']", $view);
        self::assertStringContainsString('csrf_field()', $view);
    }

    public function testKesiapanMemakaiKKMAdminAtauGuruBukanNilaiHistoris(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/app/Services/KesiapanTpService.php');
        self::assertIsString($source);
        self::assertStringContainsString("->informasi(\$tp)['efektif']", $source);
        self::assertStringContainsString("'kkm' => (int) \$cek['tp']['kkm']", $source);
        self::assertStringContainsString("if (\$tp['status'] === 'draf'", $source);
    }

    public function testAdminMemilikiPengaturanTersendiri(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2) . '/app/Config/Routes.php');
        $navigation = file_get_contents(dirname(__DIR__, 2) . '/app/Services/NavigasiService.php');
        $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/kkm.php');
        self::assertIsString($routes);
        self::assertIsString($navigation);
        self::assertIsString($view);
        self::assertStringContainsString("'AdminKkm::index'", $routes);
        self::assertStringContainsString("'AdminKkm::simpan'", $routes);
        self::assertStringContainsString("'Pengaturan KKM'", $navigation);
        self::assertStringContainsString('csrf_field()', $view);
        self::assertStringContainsString('KKM Default', $view);
    }

    public function testIdentitasTPBerbentukKartuRingkasBukanTabelVertikal(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/guru/detail_tp.php');
        $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/emerald-glass.css');
        self::assertIsString($view);
        self::assertIsString($css);
        self::assertStringContainsString('a-tp-identitas-ringkas', $view);
        self::assertStringContainsString('<dl class="a-tp-identitas-ringkas">', $view);
        self::assertStringNotContainsString('<table class="a-tbl a-tp-identitas">', $view);
        self::assertStringContainsString('grid-template-columns:minmax(135px,1.5fr)', $css);
    }
}
