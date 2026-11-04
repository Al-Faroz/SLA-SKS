<?php

declare(strict_types=1);

/**
 * A14-1: smoke test HTTP ANONIM / baca-saja untuk server Apache XAMPP nyata.
 *
 * php scripts/qa/http_security_smoke.php http://localhost/sla-sks/
 *
 * Tidak ada login, password, POST, DELETE, file hasil, atau perubahan database.
 * Body HTTP langsung dibuang, tidak pernah dicetak (termasuk saat URL bocor).
 * TIDAK memanggil URL skrip PHP pemeliharaan: itu berisiko menjalankannya
 * bila konfigurasi Apache belum aman. Akses folder/berkas inert yang diuji.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

if (!extension_loaded('curl')) {
    fwrite(STDERR, "GALAT: ekstensi PHP curl belum aktif.\n");
    exit(2);
}

$base = $argv[1] ?? 'http://localhost/sla-sks/';
if (!is_string($base)) {
    fwrite(STDERR, "GALAT: URL dasar tidak valid.\n");
    exit(2);
}
$parts=parse_url($base);
$host=strtolower((string)($parts['host']??''));
if (!is_array($parts) || !in_array($parts['scheme']??'', ['http','https'],true)
    || !in_array($host,['localhost','127.0.0.1','::1'],true)
    || isset($parts['user'],$parts['pass'])
    || isset($parts['query']) || isset($parts['fragment'])) {
    fwrite(STDERR,"GALAT: hanya URL localhost HTTP(S) tanpa user/password/query.\n");
    exit(2);
}
$base=rtrim($base,'/').'/';

$tests = [
    // Jalur publik harus tetap berfungsi setelah perketatan .htaccess.
    ['publik','masuk',[200]],
    ['publik','public/assets/css/app.css',[200]],
    ['publik','public/assets/js/app.js',[200]],
    ['publik','public/assets/icons/lucide-sla.svg',[200]],
    ['publik','public/favicon.ico',[200]],
    ['publik','kebijakan-privasi',[200,503]], // Naskah sekolah mungkin belum terbit.

    // File yang memang ada tidak boleh diunduh atau dieksekusi langsung.
    ['privat','.env',[403,404]],
    ['privat','env',[403,404]],
    ['privat','.gitignore',[403,404]],
    ['privat','AGENTS.md',[403,404]],
    ['privat','README.md',[403,404]],
    ['privat','MANIFEST.sha256',[403,404]],
    ['privat','composer.json',[403,404]],
    ['privat','composer.lock',[403,404]],
    ['privat','phpunit.dist.xml',[403,404]],
    ['privat','spark',[403,404]],
    ['privat','preload.php',[403,404]],
    ['privat','app/Config/Database.php',[403,404]],
    ['privat','APP/Config/Database.php',[403,404]],
    ['privat','vendor/autoload.php',[403,404]],
    ['privat','database/sql/README.md',[403,404]],
    ['privat','docs/README.md',[403,404]],
    ['privat','tests/feature/K0PublikTest.php',[403,404]],
    ['privat','scripts/',[403,404]], // Jangan eksekusi file scripts/*.php.
    ['privat','tools/install-suneditor.ps1',[403,404]],
    ['privat','writable/private/sertifikat/',[403,404]],
    ['privat','.github/workflows/qa-a13-6-a13-7-implementasi.yml',[403,404]],
    ['privat','public/index.php',[403,404]],

    // Anonim tidak boleh menerima halaman panel yang bersangkutan.
    ['auth','admin',[302,303,401,403]],
    ['auth','guru',[302,303,401,403]],
    ['auth','siswa',[302,303,401,403]],
    ['auth','pimpinan',[302,303,401,403]],
    ['auth','profil',[302,303,401,403]],
    ['auth','notifikasi',[302,303,401,403]],
    ['auth','pengumuman',[302,303,401,403]],
    ['auth','bantuan',[302,303,401,403]],
    ['auth','guru/peta-atp',[302,303,401,403]],
    ['auth','siswa/laporan',[302,303,401,403]],
    ['auth','pimpinan/laporan',[302,303,401,403]],
    ['auth','admin/sertifikat',[302,303,401,403]],
    ['auth','admin/kebijakan-privasi',[302,303,401,403]],
];

function periksa(string $url): array
{
    $headers=[];
    $curl=curl_init($url);
    if ($curl===false) throw new RuntimeException('Tidak dapat membuat sesi curl.');
    curl_setopt_array($curl,[
        CURLOPT_RETURNTRANSFER=>false,
        CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_CONNECTTIMEOUT=>3,
        CURLOPT_TIMEOUT=>8,
        CURLOPT_HTTPGET=>true,
        CURLOPT_USERAGENT=>'SLA-SKS-A14-HTTP-ReadOnly/1.0',
        CURLOPT_HTTPHEADER=>['Accept: text/html, */*','Cache-Control: no-cache'],
        CURLOPT_WRITEFUNCTION=>static fn($ch,$bytes):int=>strlen($bytes),
        CURLOPT_HEADERFUNCTION=>static function($ch,string $line)use(&$headers):int {
            if (preg_match('/^Location:\s*(.*?)\s*$/i',$line,$m)) $headers['location']=$m[1];
            return strlen($line);
        },
    ]);
    try {
        $hasil=curl_exec($curl);
        $code=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
        $error=$hasil===false?curl_error($curl):'';
    } finally {
        curl_close($curl);
    }
    if($hasil===false || $code<100){
        throw new RuntimeException('Permintaan HTTP gagal: '.($error?:'status tidak tersedia'));
    }
    return ['code'=>$code,'location'=>$headers['location']??''];
}

$ok=0;$fail=0;
echo "Audit HTTP anonim A14-1 (read-only), target: ".$base."\n";
foreach($tests as [$jenis,$path,$expected]){
    $url=$base.$path;
    try {
        $hasil=periksa($url);
        $code=$hasil['code'];
        $pass=in_array($code,$expected,true);
        if($pass && $jenis==='auth' && in_array($code,[302,303],true)){
            // Tidak menerima redirect ke lokasi lain, misalnya redirect
            // eksternal yang menyembunyikan akses tidak sah.
            $dest=(string)$hasil['location'];
            $destPath=(string)(parse_url($dest,PHP_URL_PATH)??'');
            $destHost=parse_url($dest,PHP_URL_HOST);
            $pass=($destHost===null || strcasecmp((string)$destHost,
                    (string)(parse_url($base,PHP_URL_HOST)??''))===0)
                && preg_match('~/(?:index\.php/)?masuk/?$~i',$destPath)===1;
        }
        if($pass)++$ok;else ++$fail;
        printf("[%s] %-39s HTTP %d (%s)\n",$pass?'PASS':'FAIL',
            '/'.$path,$code,$jenis);
    }catch(Throwable $e){
        ++$fail;
        printf("[FAIL] %-39s GALAT HTTP: %s\n",'/'.$path,$e->getMessage());
    }
}
printf("HASIL: %d PASS, %d FAIL. %s\n",$ok,$fail,
    $fail===0?'Tidak ditemukan kegagalan pada sampel URL yang diuji.':
    'JANGAN DEPLOY sebelum kegagalan diperbaiki.');
exit($fail===0?0:1);
