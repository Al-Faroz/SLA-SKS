<?php

declare(strict_types=1);

use App\Services\StepProgressTpService;
use PHPUnit\Framework\TestCase;

final class StepProgressTpServiceTest extends TestCase
{
    private static function cek(array $codes): array
    {
        return array_map(static fn (string $kode): array =>
            ['kode' => $kode, 'terpenuhi' => true], $codes);
    }

    public function testEsensialHanyaTigaLangkahTanpaBankSoal(): void
    {
        $cek = self::cek([
            'kode_tp','judul','rumusan','jenis-tp','paket','cp',
            'materi','materi-1','media-1',
        ]);
        $steps = StepProgressTpService::susun($cek, 'esensial', 'draf', 23);
        self::assertCount(3, $steps);
        self::assertSame(['TP dan CP','Satu Materi','Publikasi'], array_column($steps, 'label'));
        self::assertTrue($steps[0]['terpenuhi']);
        self::assertTrue($steps[1]['terpenuhi']);
        self::assertFalse($steps[2]['terpenuhi']);
        self::assertSame('guru/tp/23#materi-tp', $steps[1]['tautan']);
    }

    public function testNonEsensialPerluLimaLangkahDanSumatifSah(): void
    {
        $cek = self::cek([
            'kode_tp','judul','rumusan','jenis-tp','paket','cp',
            'materi','materi-1','media-1','kkm','resume_min','tugas_min',
            'diag_durasi','diag_tayang','bank-diagnostik','valid-diagnostik',
            'sum_durasi','sum_tayang','sum_maks_percobaan','bobot',
            'bank-sumatif','valid-sumatif','aset-soal',
        ]);
        $steps = StepProgressTpService::susun($cek, 'non_esensial', 'draf', 23);
        self::assertSame(['TP dan CP','Satu Materi','Diagnostik','Sumatif','Publikasi'],
            array_column($steps, 'label'));
        self::assertCount(5, $steps);
        self::assertSame('Minimal 10 soal sah dan pengaturan kegiatan', $steps[2]['keterangan']);
        self::assertSame('Minimal 30 soal sah, KKM terset', $steps[3]['keterangan']);
        self::assertTrue($steps[3]['terpenuhi']);
        self::assertFalse($steps[4]['terpenuhi']);
        self::assertSame('guru/tp/23/bank-soal?jenis=sumatif', $steps[3]['tautan']);

        $cek[] = ['kode' => 'media-1', 'terpenuhi' => false];
        self::assertFalse(StepProgressTpService::susun($cek, 'non_esensial', 'draf', 23)[1]['terpenuhi']);
    }

    public function testKodePemeriksaanHilangTidakMenjadiSelesaiPalsu(): void
    {
        $steps = StepProgressTpService::susun([], 'non_esensial', 'draf', 23);
        self::assertSame([false,false,false,false,false], array_column($steps,'terpenuhi'));
    }

    public function testSiapMenampilkanStatusTerbitNamunValidasiTersimpan(): void
    {
        $steps = StepProgressTpService::susun([], 'esensial', 'siap', 23);
        self::assertSame('TP Siap', $steps[2]['label']);
        self::assertTrue($steps[2]['terpenuhi']);
    }
    public function testSumatifMenungguKKMWalauSoalSudahLengkap(): void
    {
        $kode = [
            'kode_tp','judul','rumusan','jenis-tp','paket','cp',
            'materi','materi-1','media-1','resume_min','tugas_min',
            'diag_durasi','diag_tayang','bank-diagnostik','valid-diagnostik',
            'sum_durasi','sum_tayang','sum_maks_percobaan','bobot',
            'bank-sumatif','valid-sumatif','aset-soal',
        ];
        $steps = StepProgressTpService::susun(self::cek($kode), 'non_esensial', 'draf', 23);
        self::assertFalse($steps[2]['terpenuhi']);
        self::assertFalse($steps[3]['terpenuhi']);
        $kode[] = 'kkm';
        $lengkap = StepProgressTpService::susun(self::cek($kode), 'non_esensial', 'draf', 23);
        self::assertTrue($lengkap[2]['terpenuhi']);
        self::assertTrue($lengkap[3]['terpenuhi']);
    }

    public function testKartuProgresDanLabelStatusMengikutiTemaDarkLight(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/guru/_step_progress_tp.php');
        $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/emerald-glass.css');
        self::assertIsString($view);
        self::assertIsString($css);
        self::assertStringContainsString('is-selesai', $view);
        self::assertStringContainsString('is-belum', $view);
        self::assertStringContainsString('a-tp-langkah__status', $view);
        self::assertStringContainsString('✓ Selesai', $view);
        self::assertStringContainsString('○ Perlu dilengkapi', $view);
        self::assertStringContainsString('--eg-step-done-bg:', $css);
        self::assertStringContainsString('--eg-step-pending-bg:', $css);
        self::assertStringContainsString('--eg-step-done-text:#FF9BA6;', $css);
        self::assertStringContainsString('--eg-step-done-text:#A72C47;', $css);
        self::assertStringContainsString('.a-tp-langkah__item.is-selesai', $css);
        self::assertStringContainsString('.a-tp-langkah__item.is-belum', $css);
    }

}
