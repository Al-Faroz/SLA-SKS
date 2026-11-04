<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * A14-1: kontrak fail-closed pada source, bukan klaim Apache sudah diuji.
 * Bukti penerapan .htaccess nyata diuji terpisah melalui HTTP smoke.
 */
final class A14RootHttpProtectionTest extends TestCase
{
    private static function baca(string $path): string
    {
        $data=file_get_contents(dirname(__DIR__,2).'/'.$path);
        self::assertIsString($data);
        return $data;
    }

    public function testRootIndexTetapPadaPosisiFinalDanTidakMemindahkanPublic(): void
    {
        $entry=self::baca('index.php');
        self::assertStringContainsString("require FCPATH . 'app/Config/Paths.php'", $entry);
        self::assertStringContainsString('RewriteRule',self::baca('.htaccess'));
    }

    public function testFolderPrivatDanFileKonfigurasiTidakDapatDisajikan(): void
    {
        $ht=self::baca('.htaccess');
        foreach(['app','vendor','writable','tests','database','docs',
            'scripts','tools','builds','uploads','system'] as $path) {
            self::assertMatchesRegularExpression(
                '/RewriteRule[^\n]*'.preg_quote($path,'/').'[^\n]*\[F,L,NC\]/',
                $ht,"Folder privat harus ditolak: ".$path
            );
        }
        foreach(['composer','AGENTS','MANIFEST','phpunit','spark','env'] as $name){
            self::assertStringContainsString($name,$ht);
        }
        self::assertStringContainsString('Require all denied',$ht);
    }

    public function testAsetPublikMasihDiizinkanNamunExecutableTidak(): void
    {
        $ht=self::baca('.htaccess');
        self::assertStringContainsString('public/(?!(?:assets/', $ht);
        self::assertStringContainsString('favicon', $ht);
        self::assertStringContainsString('robots', $ht);
        self::assertStringContainsString('assets/.*', $ht);
        self::assertMatchesRegularExpression('/RewriteRule[^\n]*assets\/.*[^\n]*php[^\n]*\[F,L,NC\]/',$ht);
        self::assertStringContainsString('RewriteCond %{REQUEST_FILENAME} !-f',$ht);
        self::assertStringContainsString('index.php/$1',$ht);
    }

    public function testScriptPerbaruiTemplateTidakBisaDipanggilLewatHttp(): void
    {
        foreach([
            'scripts/perbarui_template_atp_v25_2.php',
            'scripts/perbarui_template_atp_v25_3.php',
            'scripts/qa/http_security_smoke.php',
        ] as $path){
            $body=self::baca($path);
            self::assertStringContainsString("PHP_SAPI !== 'cli'",$body);
            self::assertStringContainsString('http_response_code(403)',$body);
            self::assertStringContainsString('exit;',$body);
            $guard=strpos($body,"PHP_SAPI !== 'cli'");
            $vendor=strpos($body,"vendor/autoload.php");
            if($vendor!==false){
                self::assertLessThan($vendor,$guard);
            }
        }
    }

    public function testHttpAuditTidakMemicuSkripPemeliharaanAtauMutasi(): void
    {
        $body=self::baca('scripts/qa/http_security_smoke.php');
        self::assertStringContainsString('CURLOPT_FOLLOWLOCATION=>false',$body);
        self::assertStringContainsString('CURLOPT_HTTPGET=>true',$body);
        self::assertStringContainsString('CURLOPT_WRITEFUNCTION',$body);
        self::assertStringNotContainsString("['privat','scripts/perbarui_", $body);
        self::assertStringNotContainsString('CURLOPT_POST=>true',$body);
        self::assertStringNotContainsString('CURLOPT_CUSTOMREQUEST',$body);
    }
}
