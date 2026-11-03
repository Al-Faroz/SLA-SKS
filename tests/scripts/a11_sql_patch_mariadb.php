<?php

declare(strict_types=1);

/**
 * Dijalankan HANYA pada database sintetis di CI, BUKAN database pengguna.
 * Membuktikan SQL V4-0011 dapat diimpor MariaDB 10.4 dan menjaga UNIQUE/FK.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','a11-fixture-only','a11_test');
$db->set_charset('utf8mb4');
foreach ([
    "CREATE TABLE pengguna (id bigint(20) UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB",
    "CREATE TABLE siswa (id bigint(20) UNSIGNED NOT NULL PRIMARY KEY, pengguna_id bigint(20) UNSIGNED NOT NULL) ENGINE=InnoDB",
    "CREATE TABLE paket_semester (id bigint(20) UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB",
    "CREATE TABLE schema_version (versi varchar(40) NOT NULL PRIMARY KEY, diterapkan_pada timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
    "INSERT INTO schema_version(versi) VALUES ('V4-0010-BANK-WORD-PRA')",
] as $sql) $db->query($sql);

$file=dirname(__DIR__,2).'/database/sql/updates/V4-0011-SERTIFIKAT-ASSET-DAN-VERSI.sql';
$patch=file_get_contents($file);
if (!is_string($patch) || str_contains(strtoupper($patch),'TRUNCATE TABLE ')
    || str_contains(strtoupper($patch),'DROP TABLE ')) {
    throw new RuntimeException('Patch SQL tidak lolos pemeriksaan non-destruktif.');
}
$db->multi_query($patch);
do {
    $result=$db->store_result();
    if ($result instanceof mysqli_result) $result->free();
    if (!$db->more_results()) break;
} while ($db->next_result());

$found=[];
$res=$db->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()
    AND table_name IN ('sertifikat_aset','sertifikat_aset_aktif','sertifikat','sertifikat_aktif')");
while ($r=$res->fetch_assoc()) $found[]=$r['table_name'];
sort($found);
$expected=['sertifikat','sertifikat_aktif','sertifikat_aset','sertifikat_aset_aktif'];
sort($expected);
if ($found!==$expected) throw new RuntimeException('Empat tabel sertifikat tidak lengkap.');
if ((int)$db->query("SELECT COUNT(*) AS n FROM schema_version WHERE versi='V4-0011-SERTIFIKAT'")->fetch_assoc()['n']!==1) {
    throw new RuntimeException('Marker schema_version hilang.');
}
$db->query("INSERT INTO pengguna(id) VALUES (1),(2)");
$db->query("INSERT INTO siswa(id,pengguna_id) VALUES (1,2)");
$db->query("INSERT INTO paket_semester(id) VALUES (1)");
$db->query("INSERT INTO sertifikat_aset (id,jenis,nama_acak,mime,lebar,tinggi,ukuran_byte,sha256,dibuat_oleh,dibuat_pada)
    VALUES (1,'ttd','aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        'image/png',300,150,200,'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',1,UTC_TIMESTAMP())");
$db->query("INSERT INTO sertifikat_aset_aktif(jenis,aset_id,diaktifkan_oleh,diaktifkan_pada)
    VALUES ('ttd',1,1,UTC_TIMESTAMP())");
$db->query("INSERT INTO sertifikat
    (id,siswa_id,paket_semester_id,versi,nomor,kode_verifikasi,status,ttd_aset_id,
    konten_sha256,snapshot_json,snapshot_sha256,pdf_nama_acak,pdf_sha256,
    diterbitkan_oleh,diterbitkan_pada)
    VALUES (1,1,1,1,'SKS/TEST/V1','cccccccccccccccccccccccccccccccccccccccccccccccc',
    'berlaku',1,
    'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd',
    '{}','eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',
    'ffffffffffffffffffffffffffffffffffffffffffffffff',
    'abababababababababababababababababababababababababababababababab',
    1,UTC_TIMESTAMP())");
$db->query("INSERT INTO sertifikat_aktif(siswa_id,paket_semester_id,sertifikat_id,diubah_pada)
    VALUES (1,1,1,UTC_TIMESTAMP())");
try {
    $db->query("INSERT INTO sertifikat_aktif(siswa_id,paket_semester_id,sertifikat_id,diubah_pada)
        VALUES (1,1,1,UTC_TIMESTAMP())");
    throw new RuntimeException('UNIQUE aktif tidak diterapkan.');
} catch (mysqli_sql_exception $e) {
    if ($e->getCode()!==1062) throw $e;
}
if ((int)$db->query('SELECT COUNT(*) n FROM sertifikat_aktif')->fetch_assoc()['n']!==1) {
    throw new RuntimeException('Duplikasi pointer aktif.');
}
echo "PASS MariaDB 10.4: V4-0011, 4 tables, FK, pointer UNIQUE, contoh terisolasi\n";
$db->close();
