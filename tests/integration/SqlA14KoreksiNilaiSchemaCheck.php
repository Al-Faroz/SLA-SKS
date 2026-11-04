<?php

declare(strict_types=1);

/**
 * Patch V4-0014 dibaca dari repo dan diimpor ke database MariaDB sementara.
 * Tidak memakai dump privat, nama pengguna nyata, atau kredensial XAMPP.
 */
$dsn=getenv('SLA_TEST_DB_DSN');
$user=getenv('SLA_TEST_DB_USER');
$pass=getenv('SLA_TEST_DB_PASS');
if (!is_string($dsn) || !str_contains($dsn,'dbname=sla_a14_koreksi_test')
    || !is_string($user) || !is_string($pass)) {
    throw new RuntimeException('Tes hanya boleh berjalan di sla_a14_koreksi_test.');
}
$pdo=new PDO($dsn,$user,$pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
foreach([
    'CREATE TABLE pengguna(id BIGINT UNSIGNED NOT NULL PRIMARY KEY)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    'CREATE TABLE progres_tp(id BIGINT UNSIGNED NOT NULL PRIMARY KEY)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    'CREATE TABLE versi_konten_tp(id BIGINT UNSIGNED NOT NULL PRIMARY KEY)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    'CREATE TABLE schema_version(versi VARCHAR(40) NOT NULL PRIMARY KEY,
        diterapkan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
] as $sql)$pdo->exec($sql);
$pdo->exec("INSERT INTO schema_version(versi)
    VALUES('V4-0013-NOTIFIKASI-PRIVASI')");
$pdo->exec('INSERT INTO pengguna VALUES(11),(20),(30)');
$pdo->exec('INSERT INTO progres_tp VALUES(8)');
$pdo->exec('INSERT INTO versi_konten_tp VALUES(4)');
$path=dirname(__DIR__,2).'/database/sql/updates/V4-0014_KOREKSI_NILAI_SISWA.sql';
$sql=file_get_contents($path);
if (!is_string($sql))throw new RuntimeException('SQL V4-0014 tidak ditemukan.');
$clean=preg_replace('/^--.*$/m','',$sql);
if (!is_string($clean))throw new RuntimeException('Komentar SQL tidak dapat diproses.');
foreach(preg_split('/;\s*(?:\n|$)/',$clean) as $stmt){
    $stmt=trim($stmt);
    if($stmt!=='')$pdo->exec($stmt);
}
foreach(['permohonan_koreksi_nilai','riwayat_koreksi_nilai']as $name){
    if((int)$pdo->query('SELECT COUNT(*) FROM '.$name)->fetchColumn()!==0){
        throw new RuntimeException('Tabel baru seharusnya kosong.');
    }
}
if((int)$pdo->query("SELECT COUNT(*) FROM schema_version
    WHERE versi='V4-0014-KOREKSI-NILAI-SISWA'")->fetchColumn()!==1){
    throw new RuntimeException('Marker V4-0014 tidak tersimpan.');
}
$pdo->exec("INSERT INTO permohonan_koreksi_nilai
    (jenis,sumber_id,progres_tp_id,versi_konten_tp_id,versi_koreksi,
    nilai_awal,nilai_usulan,nilai_tp_awal,nilai_tp_usulan,status_nilai_awal,
    alasan,guru_id,status,dibuat_pada)
    VALUES('resume',10,8,4,1,60,90,74,80,'final','Catatan sintetis',20,'diajukan',
    UTC_TIMESTAMP())");
try{
    $pdo->exec("INSERT INTO permohonan_koreksi_nilai
        (jenis,sumber_id,progres_tp_id,versi_konten_tp_id,versi_koreksi,
        nilai_awal,nilai_usulan,nilai_tp_awal,nilai_tp_usulan,status_nilai_awal,
        alasan,guru_id,status,dibuat_pada)
        VALUES('resume',10,8,4,2,60,95,74,81,'final',
        'Permintaan terbuka duplikat',20,'diajukan',UTC_TIMESTAMP())");
    throw new RuntimeException('Pengajuan terbuka duplikat tidak ditolak.');
}catch(PDOException $e){
    if($e->getCode()!=='23000')throw $e;
}
$pdo->exec("UPDATE permohonan_koreksi_nilai SET status='ditolak',
    admin_id=30,catatan_keputusan='Ditolak dalam pengujian',
    diputus_pada=UTC_TIMESTAMP() WHERE id=1");
$pdo->exec("INSERT INTO permohonan_koreksi_nilai
    (jenis,sumber_id,progres_tp_id,versi_konten_tp_id,versi_koreksi,
    nilai_awal,nilai_usulan,nilai_tp_awal,nilai_tp_usulan,status_nilai_awal,
    alasan,guru_id,status,dibuat_pada)
    VALUES('resume',10,8,4,2,60,95,74,81,'final',
    'Pengajuan baru diizinkan',20,'diajukan',UTC_TIMESTAMP())");
$pdo->exec("INSERT INTO riwayat_koreksi_nilai
    (permohonan_id,aksi,pelaku_id,peran_aktif,nilai_awal,nilai_usulan,
    nilai_tp_awal,nilai_tp_usulan,versi_koreksi,waktu)
    VALUES(1,'diajukan',20,'guru',60,90,74,80,1,UTC_TIMESTAMP())");
try{
    $pdo->exec("INSERT INTO riwayat_koreksi_nilai
        (permohonan_id,aksi,pelaku_id,peran_aktif,nilai_awal,nilai_usulan,
        nilai_tp_awal,nilai_tp_usulan,versi_koreksi,waktu)
        VALUES(1,'diajukan',20,'guru',60,90,74,80,1,UTC_TIMESTAMP())");
    throw new RuntimeException('Riwayat ganda seharusnya ditolak.');
}catch(PDOException $e){
    if($e->getCode()!=='23000')throw $e;
}
try {
    $pdo->exec("INSERT INTO riwayat_koreksi_nilai
        (permohonan_id,aksi,pelaku_id,peran_aktif,nilai_awal,nilai_usulan,
        nilai_tp_awal,nilai_tp_usulan,versi_koreksi,waktu)
        VALUES(999,'diajukan',20,'guru',60,90,74,80,1,UTC_TIMESTAMP())");
    throw new RuntimeException('FK riwayat tidak ditegakkan.');
}catch(PDOException $e){
    if($e->getCode()!=='23000')throw $e;
}
echo "PASS V4-0014 MariaDB: schema, generated unique key, versi, FK, audit anonim.\n";
