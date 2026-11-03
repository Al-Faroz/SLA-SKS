<?php

declare(strict_types=1);

/**
 * A8: Uji DDL V4-0009 pada DB MariaDB sintetis ISOLATED.
 * Guard tidak pernah menerima database milik pengguna/XAMPP.
 */
$dsn=getenv('SLA_TEST_DB_DSN');
if (!$dsn || !str_starts_with($dsn,'mysql:dbname=sla_s06_test;')) {
    fwrite(STDERR,"Hanya untuk MariaDB CI sla_s06_test.\n");
    exit(2);
}
$user=(string)getenv('SLA_TEST_DB_USER');
$pass=(string)getenv('SLA_TEST_DB_PASS');
$db=new PDO($dsn,$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'sla_s06_test') {
    throw new RuntimeException('Bukan database tes resmi.');
}
$db->exec('CREATE DATABASE IF NOT EXISTS sla_a8_synthetic CHARACTER SET utf8mb4');
$test=new PDO('mysql:dbname=sla_a8_synthetic;host=127.0.0.1;port=3306;charset=utf8mb4',
    $user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if ($test->query('SELECT DATABASE()')->fetchColumn() !== 'sla_a8_synthetic') {
    throw new RuntimeException('Target fixture A8 tidak aman.');
}
$setup=[
    'CREATE TABLE pengguna(id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB',
    'CREATE TABLE siswa(id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB',
    'CREATE TABLE paket_semester(id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB',
    'CREATE TABLE ujian_pra_semester(id BIGINT UNSIGNED NOT NULL PRIMARY KEY,paket_semester_id BIGINT UNSIGNED NOT NULL,status VARCHAR(10) NOT NULL) ENGINE=InnoDB',
    'CREATE TABLE schema_version(versi VARCHAR(40) NOT NULL PRIMARY KEY,diterapkan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB',
    'CREATE TABLE percobaan_ujian(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,ujian_pra_semester_id BIGINT UNSIGNED NULL,siswa_id BIGINT UNSIGNED NULL,nomor_percobaan SMALLINT UNSIGNED NOT NULL,token_publik CHAR(48) NULL) ENGINE=InnoDB',
];
foreach ($setup as $sql) $test->exec($sql);
$test->exec("INSERT INTO pengguna VALUES(1)");
$test->exec("INSERT INTO siswa VALUES(2)");
$test->exec("INSERT INTO paket_semester VALUES(3)");
$test->exec("INSERT INTO ujian_pra_semester VALUES(4,3,'siap')");
$test->exec("INSERT INTO schema_version VALUES('V4-0008-KEPUTUSAN-UJIAN-BATAL',CURRENT_TIMESTAMP())");
$patch=file_get_contents(dirname(__DIR__,2).'/database/sql/updates/V4-0009_PRA_SESI_TERJADWAL.sql');
if (!is_string($patch)) throw new RuntimeException('SQL V4-0009 tidak ditemukan.');
$patch=preg_replace('/^--.*$/m','',$patch);
foreach (explode(';',$patch) as $statement) {
    if (trim($statement)!=='') $test->exec($statement);
}
if ((int)$test->query("SELECT COUNT(*) FROM schema_version WHERE versi='V4-0009-PRA-SESI-TERJADWAL'")->fetchColumn()!==1) {
    throw new RuntimeException('schema_version V4-0009 belum dicatat.');
}
$test->exec("INSERT INTO jadwal_pra_sesi(ujian_pra_semester_id,jenis,tahap,mulai_pada,selesai_pada,durasi_menit,dibuat_oleh,dibuat_pada)
    VALUES(4,'susulan','normal','2026-11-10 08:00:00','2026-11-10 09:00:00',60,1,'2026-11-03 10:00:00')");
$slot=(int)$test->lastInsertId();
$test->exec("INSERT INTO peserta_pra_susulan(jadwal_pra_sesi_id,siswa_id,ditetapkan_oleh,ditetapkan_pada)
    VALUES($slot,2,1,'2026-11-03 10:00:00')");
$test->exec("INSERT INTO percobaan_ujian(ujian_pra_semester_id,jadwal_pra_sesi_id,siswa_id,nomor_percobaan,token_publik)
    VALUES(4,$slot,2,1,'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')");
foreach ([
    "INSERT INTO peserta_pra_susulan(jadwal_pra_sesi_id,siswa_id,ditetapkan_oleh,ditetapkan_pada) VALUES($slot,2,1,NOW())",
    "INSERT INTO percobaan_ujian(ujian_pra_semester_id,jadwal_pra_sesi_id,siswa_id,nomor_percobaan) VALUES(4,$slot,2,1)",
    "INSERT INTO percobaan_ujian(ujian_pra_semester_id,jadwal_pra_sesi_id,siswa_id,nomor_percobaan) VALUES(4,999,2,2)",
    "INSERT INTO peserta_pra_susulan(jadwal_pra_sesi_id,siswa_id,ditetapkan_oleh,ditetapkan_pada) VALUES($slot,999,1,NOW())",
    "INSERT INTO jadwal_pra_sesi(ujian_pra_semester_id,jenis,tahap,mulai_pada,selesai_pada,durasi_menit,dibuat_oleh,dibuat_pada) VALUES(4,'remedial','normal','2026-11-11 08:00:00','2026-11-11 09:00:00',60,1,NOW())",
] as $bad) {
    try {
        $test->exec($bad);
        throw new RuntimeException('Constraint MariaDB V4-0009 gagal memblokir data tidak sah.');
    } catch (PDOException $e) {
        if ((string)$e->getCode()!=='23000') throw $e;
    }
}
echo "A8 MariaDB sintetis: DDL V4-0009, FK, CHECK, UNIQUE, schema_version PASS\n";

$wordPatch=file_get_contents(dirname(__DIR__,2).'/database/sql/updates/V4-0010_BANK_WORD_PRA_SEMESTER.sql');
if (!is_string($wordPatch)) throw new RuntimeException('Patch staging Word Pra-Semester tidak ditemukan.');
$wordPatch=preg_replace('/^--.*$/m','',$wordPatch);
foreach (explode(';',$wordPatch) as $statement) {
    if(trim($statement)!=='')$test->exec($statement);
}
$version=(int)$test->query("SELECT COUNT(*) FROM schema_version WHERE versi='V4-0010-BANK-WORD-PRA'")->fetchColumn();
if($version!==1)throw new RuntimeException('Versi staging Word tidak dicatat.');
$id=str_repeat('a',32);
$test->exec("INSERT INTO impor_soal_pra_docx(id,paket_semester_id,guru_id,checksum,status,data_json,dibuat_pada,kedaluwarsa_pada)
 VALUES('$id',3,1,'".str_repeat('b',64)."','pratinjau','{\"jumlah\":30}',NOW(),DATE_ADD(NOW(),INTERVAL 2 HOUR))");
foreach([
    "INSERT INTO impor_soal_pra_docx(id,paket_semester_id,guru_id,checksum,data_json,dibuat_pada,kedaluwarsa_pada) VALUES('$id',3,1,'".str_repeat('b',64)."','{}',NOW(),NOW())",
    "INSERT INTO impor_soal_pra_docx(id,paket_semester_id,guru_id,checksum,data_json,dibuat_pada,kedaluwarsa_pada) VALUES('".str_repeat('c',32)."',999,1,'".str_repeat('b',64)."','{}',NOW(),NOW())",
    "INSERT INTO impor_soal_pra_docx(id,paket_semester_id,guru_id,checksum,data_json,dibuat_pada,kedaluwarsa_pada) VALUES('".str_repeat('d',32)."',3,999,'".str_repeat('b',64)."','{}',NOW(),NOW())",
    "INSERT INTO impor_soal_pra_docx(id,paket_semester_id,guru_id,checksum,data_json,dibuat_pada,kedaluwarsa_pada) VALUES('".str_repeat('e',32)."',3,1,'".str_repeat('b',64)."','bukan-json',NOW(),NOW())",
] as $invalid){
    try {
        $test->exec($invalid);
        throw new RuntimeException('SQL V4-0010 gagal menolak staging tidak sah.');
    } catch(PDOException $e){
        if(!in_array((string)$e->getCode(),['23000'],true))throw $e;
    }
}
echo "A8 MariaDB sintetis: staging V4-0010, FK, JSON, primary key dan schema_version PASS\n";

