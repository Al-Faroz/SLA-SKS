<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * A14-3: regression kontrak layout Guru dari review 12 screenshot XAMPP.
 * Pemeriksaan statis, bukan bukti render browser atau jalannya POST.
 */
final class A14GuruLayoutSourceTest extends TestCase
{
    private static function sumber(string $path): string
    {
        $s=file_get_contents(dirname(__DIR__,2).'/'.$path);
        self::assertIsString($s,$path.' harus tersedia.');
        return $s;
    }

    public function testSemuaHalamanGuruPunyaScopeStyleA143(): void
    {
        $views=[
            'guru/peta_tp.php'=>'a-guru-peta-atp',
            'guru/impor_atp.php'=>'a-guru-impor-atp',
            'guru/semester_atp.php'=>'a-guru-semester-atp',
            'guru/detail_tp.php'=>'a-guru-detail-tp',
            'guru/bank_soal.php'=>'a-guru-bank-soal',
            'guru/review_bank_soal.php'=>'a-guru-review-word',
        ];
        foreach($views as $file=>$selector){
            self::assertStringContainsString($selector,self::sumber('app/Views/'.$file));
        }
    }

    public function testFormAsliMasihMenjagaNamaDanCSRF(): void
    {
        $bank=self::sumber('app/Views/guru/bank_soal.php');
        self::assertStringContainsString('name="kkm_khusus"',$bank);
        self::assertStringContainsString('name="word_soal"',$bank);
        self::assertStringContainsString('name="jumlah"',$bank);
        self::assertStringContainsString('csrf_field()',$bank);
        self::assertStringContainsString('a-guru-bank-soal__word-form',$bank);
        self::assertStringContainsString('a-guru-bank-soal__upload-form',$bank);
        $impor=self::sumber('app/Views/guru/impor_atp.php');
        self::assertStringContainsString('name="atp_excel"',$impor);
        self::assertStringContainsString('csrf_field()',$impor);
        $detail=self::sumber('app/Views/guru/detail_tp.php');
        self::assertStringContainsString("view('guru/_materi_fields'",$detail);
        self::assertStringContainsString('data-materi-form',$detail);
        self::assertStringContainsString('csrf_field()',$detail);
    }

    public function testMateriWaktuSertaResumeDanHotsTerpasangSebagaiPasangan(): void
    {
        $f=self::sumber('app/Views/guru/_materi_fields.php');
        foreach(['a-materi-waktu','a-materi-resume','a-materi-hots',
            'data-materi-youtube','data-materi-berkas'] as $selector){
            self::assertStringContainsString($selector,$f);
        }
        self::assertStringContainsString('name="min_waktu_menit"',$f);
        self::assertStringContainsString('name="petunjuk_resume"',$f);
        self::assertStringContainsString('name="petunjuk_hots"',$f);
    }

    public function testCssTidakMengubahPaletAtauAturanBisnisDanPunyaFallbackResponsive(): void
    {
        $css=self::sumber('public/assets/css/emerald-glass.css');
        self::assertStringContainsString('A14-3 / Guru',$css);
        foreach(['a-atp-pilihan .a-filter','a-bank-soal__kkm-form',
            'a-guru-bank-soal__word-form','a-guru-bank-soal__upload-form',
            '.a-materi-waktu','file-selector-button',
            '@media(max-width:900px)','@media(max-width:640px)'] as $s){
            self::assertStringContainsString($s,$css);
        }
        // Periksa hanya blok A14-3; tombol kembali ke atas di shell baru
        // memang harus fixed, namun sidebar dan halaman Guru tidak demikian.
        $start=strpos($css,'A14-3 / Guru');
        $end=strpos($css,'/* A15 — identitas MTsN', $start === false ? 0 : $start);
        self::assertNotFalse($start);
        self::assertNotFalse($end);
        $a143=substr($css,$start,$end-$start);
        self::assertStringNotContainsString('!important',$a143);
        self::assertStringNotContainsString('position:fixed',$a143);
        self::assertStringNotContainsString('background:#fff',$a143);
        self::assertStringContainsString('html[data-theme] .a-app--prototipe .a-side {', $css);
        self::assertStringContainsString('position:static;', $css);
    }

    public function testQaMariaDbMenghindariTlsWajibHanyaPadaRunnerLoopback(): void
    {
        $workflow=self::sumber('.github/workflows/qa-edit-cp-bab-tp-v25.yml');
        self::assertStringContainsString('mariadb --skip-ssl -h 127.0.0.1',$workflow);
        self::assertStringNotContainsString('mariadb -h 127.0.0.1 -u root',$workflow);
    }

    public function testAlurApprovalKoreksiLamaTidakMempunyaiRouteAktif(): void
    {
        $routes=self::sumber('app/Config/Routes.php');
        $menu=self::sumber('app/Services/NavigasiService.php');
        $routing=self::sumber('app/Config/Routing.php');
        self::assertStringContainsString('GuruKoreksiNilai::proses',$routes);
        self::assertStringNotContainsString('AdminKoreksiNilai::',$routes);
        self::assertStringNotContainsString('GuruKoreksiNilai::ajukan',$routes);
        self::assertStringNotContainsString('Persetujuan Koreksi Nilai',$menu);
        self::assertStringContainsString('public bool $autoRoute = false;',$routing);
    }

}
