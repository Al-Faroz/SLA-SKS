<?php

declare(strict_types=1);

/** Impor patch SQL asli V4-0013 di MariaDB sementara CI; tidak memakai dump privat. */
$dsn=getenv('SLA_TEST_DB_DSN');
$user=getenv('SLA_TEST_DB_USER');
$pass=getenv('SLA_TEST_DB_PASS');
if (!is_string($dsn)||!str_contains($dsn,'dbname=sla_a13_67_test')
    || !is_string($user)||!is_string($pass)) {
    throw new RuntimeException('Tes hanya di database sintetis sla_a13_67_test.');
}
$pdo=new PDO($dsn,$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE pengguna (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    status ENUM(\'aktif\',\'nonaktif\') NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE schema_version (versi VARCHAR(40) NOT NULL PRIMARY KEY,
    diterapkan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE log_aktivitas(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED,peran_aktif VARCHAR(20),
    peristiwa VARCHAR(60),catatan VARCHAR(255),waktu DATETIME)');
$pdo->exec("INSERT INTO pengguna VALUES (11,'aktif'),(12,'aktif'),(20,'aktif'),(30,'aktif')");
$patch=dirname(__DIR__,2).'/database/sql/updates/V4-0013_NOTIFIKASI_PRIVASI.sql';
$sql=file_get_contents($patch);
if(!is_string($sql)) throw new RuntimeException('Patch V4-0013 hilang.');
$clean=preg_replace('/^--.*$/m','',$sql);
if(!is_string($clean))throw new RuntimeException('Komentar SQL tidak dapat dibersihkan.');
foreach(preg_split('/;\s*(?:\n|$)/',$clean) as $stmt){
    $stmt=trim($stmt);
    if($stmt!=='')$pdo->exec($stmt);
}
foreach(['notifikasi','kebijakan_privasi','riwayat_kebijakan_privasi']as $t){
    $n=(int)$pdo->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
    if($n!==0)throw new RuntimeException('Tabel '.$t.' tak kosong');
}
$versi=$pdo->query("SELECT COUNT(*) FROM schema_version
    WHERE versi='V4-0013-NOTIFIKASI-PRIVASI'")->fetchColumn();
if((int)$versi!==1)throw new RuntimeException('schema_version V4-0013 salah');
$pdo->exec("INSERT INTO notifikasi(penerima_id,peran_penerima,jenis,sumber_id,
    versi_sumber,judul,pesan,tujuan,kunci_idempoten,dibuat_pada)
    VALUES (11,'siswa','nilai_resume_final',9,1,'Nilai','Periksa hasil','siswa/laporan',
    REPEAT('a',64),UTC_TIMESTAMP())");
try{
    $pdo->exec("INSERT INTO notifikasi(penerima_id,peran_penerima,jenis,sumber_id,
        versi_sumber,judul,pesan,tujuan,kunci_idempoten,dibuat_pada)
        VALUES (11,'siswa','nilai_resume_final',9,1,'Nilai','Periksa hasil','siswa/laporan',
        REPEAT('a',64),UTC_TIMESTAMP())");
    throw new RuntimeException('Unique idempotency tidak ditegakkan');
}catch(PDOException $e){
    if($e->getCode()!=='23000')throw $e;
}
try{
    $pdo->exec("INSERT INTO notifikasi(penerima_id,peran_penerima,jenis,sumber_id,
        versi_sumber,judul,pesan,tujuan,kunci_idempoten,dibuat_pada)
        VALUES (999,'siswa','nilai_resume_final',9,1,'Nilai','Periksa hasil','siswa/laporan',
        REPEAT('b',64),UTC_TIMESTAMP())");
    throw new RuntimeException('FK pengguna tidak ditegakkan');
}catch(PDOException $e){
    if($e->getCode()!=='23000')throw $e;
}
$pdo->exec("INSERT INTO kebijakan_privasi
    (versi,tanggal_berlaku,teks,checksum_sha256,status,revisi_draf,dibuat_oleh,
    diubah_oleh,dibuat_pada,diubah_pada)
    VALUES ('2026.1','2020-01-01','Naskah sintetis',REPEAT('c',64),'draf',1,
    30,30,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
$pdo->exec("INSERT INTO riwayat_kebijakan_privasi
    (kebijakan_privasi_id,aksi,admin_id,revisi_lama,revisi_baru,
    checksum_lama,checksum_baru,terjadi_pada)
    VALUES (1,'buat_draf',30,NULL,1,NULL,REPEAT('c',64),UTC_TIMESTAMP())");
try{
    $pdo->exec("INSERT INTO kebijakan_privasi
    (versi,tanggal_berlaku,teks,checksum_sha256,status,revisi_draf,dibuat_oleh,
    diubah_oleh,dibuat_pada,diubah_pada)
    VALUES ('2026.1','2020-01-01','Duplikat',REPEAT('d',64),'draf',1,
    30,30,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
    throw new RuntimeException('Versi duplikat tidak ditolak');
}catch(PDOException $e){
    if($e->getCode()!=='23000')throw $e;
}
echo "PASS V4-0013 MariaDB: 3 tabel, FK, unique key, data anonim, schema_version.\n";
