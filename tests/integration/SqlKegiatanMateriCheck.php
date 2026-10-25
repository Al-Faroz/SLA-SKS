<?php

declare(strict_types=1);

/**
 * DDL S09/S10 di MariaDB sementara. NEVER run against real data.
 * DSN guard sengaja spesifik dan tidak ada operasi destruktif.
 */
$dsn = (string) getenv('SLA_TEST_DB_DSN');
if (!str_starts_with($dsn, 'mysql:dbname=sla_s09_test;')) {
    fwrite(STDERR, "Hanya dapat digunakan untuk database tes sla_s09_test.\n");
    exit(2);
}
$db = new PDO($dsn, (string) getenv('SLA_TEST_DB_USER'),
    (string) getenv('SLA_TEST_DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'sla_s09_test') {
    throw new RuntimeException('Database tes tidak sesuai.');
}
foreach (['progres_tp', 'materi', 'versi_konten_tp', 'pengguna'] as $table) {
    $db->exec('CREATE TABLE ' . $table .
        ' (id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB');
    $db->exec('INSERT INTO ' . $table . ' VALUES(1)');
}
// Reproduksi struktur database lama: pertanyaan HOTS Guru sudah memiliki tabel.
$db->exec('CREATE TABLE tugas_hots_materi (id BIGINT UNSIGNED NOT NULL PRIMARY KEY,'
    . ' materi_id BIGINT UNSIGNED NOT NULL, row_uuid CHAR(32) NOT NULL,'
    . ' pertanyaan TEXT NOT NULL, petunjuk TEXT NOT NULL) ENGINE=InnoDB');
$db->exec("INSERT INTO tugas_hots_materi VALUES (5,1,REPEAT('a',32),'Pertanyaan Guru','Petunjuk Guru')");
$sql = file_get_contents(dirname(__DIR__,2) .
    '/database/sql/updates/0017_kegiatan_resume_hots_materi.sql');
if (!is_string($sql)) throw new RuntimeException('SQL 0017 tidak ditemukan.');
$sql = preg_replace('/^--.*$/m', '', $sql);
foreach (explode(';', $sql) as $statement) {
    if (trim($statement) !== '') $db->exec($statement);
}
foreach (['resume_materi', 'jawaban_hots_materi'] as $table) {
    if ($db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== 0) {
        throw new RuntimeException('Tabel baru harus kosong.');
    }
    $insert = 'INSERT INTO ' . $table .
        ' (progres_tp_id,materi_id,versi_konten_tp_id,isi,status,revisi,dibuat_pada,diubah_pada) ' .
        "VALUES (1,1,1,?,'final',1,'2026-10-25 00:00:00','2026-10-25 00:00:00')";
    $db->prepare($insert)->execute(['Contoh sintetis']);
    if ((int) $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== 1) {
        throw new RuntimeException('Simpan kegiatan gagal.');
    }
    try {
        $db->prepare($insert)->execute(['Duplikat']);
        throw new RuntimeException('UNIQUE tidak menjaga ' . $table);
    } catch (PDOException $e) {
        if ((string) $e->getCode() !== '23000') throw $e;
    }
    try {
        $db->exec('INSERT INTO ' . $table .
            " (progres_tp_id,materi_id,versi_konten_tp_id,isi,status,revisi,dibuat_pada,diubah_pada)" .
            " VALUES(9999,1,1,'FK invalid','draf',1,'2026-10-25 00:00:00','2026-10-25 00:00:00')");
        throw new RuntimeException('FK progres gagal pada ' . $table);
    } catch (PDOException $e) {
        if ((string) $e->getCode() !== '23000') throw $e;
    }
    try {
        $db->exec('UPDATE ' . $table . ' SET nilai=101 WHERE id=1');
        throw new RuntimeException('Skor >100 harus ditolak: ' . $table);
    } catch (PDOException $e) {
        // MariaDB CHECK constraint harus menolak nilai 101.
        if (!in_array((string) $e->getCode(), ['23000', 'HY000'], true)) throw $e;
    }
}
if ((int)$db->query('SELECT COUNT(*) FROM tugas_hots_materi')->fetchColumn() !== 1
    || $db->query('SELECT pertanyaan FROM tugas_hots_materi WHERE id=5')
        ->fetchColumn() !== 'Pertanyaan Guru') {
    throw new RuntimeException('Bank pertanyaan Guru berubah akibat SQL 0017.');
}
echo "S09/S10 MariaDB 11.4: FK, UNIQUE, CHECK, dan bank Guru tetap utuh PASS\n";

// Simulasi impor parsial di database sementara TERPISAH, tanpa DROP/RENAME.
// Guard utama pada awal skrip memastikan ini hanya berjalan di CI test.
$db->exec('CREATE DATABASE sla_s09_test_partial CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$db->exec('USE sla_s09_test_partial');
foreach (['progres_tp', 'materi', 'versi_konten_tp', 'pengguna'] as $table) {
    $db->exec('CREATE TABLE ' . $table
        . ' (id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB');
    $db->exec('INSERT INTO ' . $table . ' VALUES(1)');
}
// Buat kondisi yang sama seperti dump pengguna: resume_materi sudah ada,
// tugas_hots_materi adalah bank Guru, jawaban_hots_materi belum ada.
$db->exec('CREATE TABLE resume_materi LIKE sla_s09_test.resume_materi');
$db->exec('CREATE TABLE tugas_hots_materi LIKE sla_s09_test.tugas_hots_materi');
$db->exec('INSERT INTO tugas_hots_materi SELECT * FROM sla_s09_test.tugas_hots_materi');
if ((int)$db->query('SELECT COUNT(*) FROM tugas_hots_materi')->fetchColumn() !== 1) {
    throw new RuntimeException('Fixture bank HOTS Guru tidak utuh.');
}
$fix = file_get_contents(dirname(__DIR__,2)
    . '/database/sql/updates/0018_pemulihan_jawaban_hots_materi.sql');
if (!is_string($fix)) throw new RuntimeException('SQL 0018 tidak ditemukan.');
$fix = preg_replace('/^--.*$/m', '', $fix);
foreach (explode(';', $fix) as $statement) {
    if (trim($statement) !== '') $db->exec($statement);
}
if (count($db->query('SHOW COLUMNS FROM jawaban_hots_materi')->fetchAll(PDO::FETCH_ASSOC)) !== 14) {
    throw new RuntimeException('Pemulihan 0018 tidak membentuk kolom lengkap.');
}
if ((int)$db->query('SELECT COUNT(*) FROM tugas_hots_materi')->fetchColumn() !== 1) {
    throw new RuntimeException('SQL 0018 mengubah bank pertanyaan Guru.');
}
echo "SQL 0018 pemulihan database parsial terisolasi PASS\n";


$qa0018 = file_get_contents(dirname(__DIR__,2)
    . '/database/sql/qa/0018_verifikasi_jawaban_hots_readonly.sql');
if (!is_string($qa0018)) throw new RuntimeException('QA 0018 tidak ada.');
$qa0018 = preg_replace('/^--.*$/m', '', $qa0018);
if (preg_match('/\b(?:CREATE|DROP|ALTER|TRUNCATE|UPDATE|DELETE|INSERT|REPLACE)\b/i',
    $qa0018)) throw new RuntimeException('QA 0018 harus hanya baca saja.');
foreach (explode(';', $qa0018) as $statement) {
    if (trim($statement) !== '') $db->query($statement);
}
echo "QA 0018 tanpa mutasi database PASS\n";

$diagnostikSql = file_get_contents(dirname(__DIR__,2)
    . '/database/sql/qa/0017_verifikasi_kegiatan_readonly.sql');
if (!is_string($diagnostikSql)) {
    throw new RuntimeException('SQL diagnostik 0017 tidak ditemukan.');
}
$bersih = preg_replace('/^--.*$/m', '', $diagnostikSql);
if (!is_string($bersih) || preg_match('/\b(?:CREATE|DROP|ALTER|TRUNCATE|UPDATE|DELETE|INSERT|REPLACE)\b/i',
    $bersih)) {
    throw new RuntimeException('Diagnostik 0017 harus selalu baca-saja.');
}
$jumlah = 0;
foreach (explode(';', $bersih) as $statement) {
    if (trim($statement) === '') continue;
    if (preg_match('/^\s*SELECT\b/i', $statement) !== 1) {
        throw new RuntimeException('Diagnostik 0017 hanya boleh SELECT.');
    }
    $hasil = $db->query($statement)->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($hasil)) throw new RuntimeException('Hasil diagnostik tidak sah.');
    $jumlah++;
}
if ($jumlah !== 5) {
    throw new RuntimeException('Semua lima pemeriksaan diagnostik harus dijalankan.');
}
echo "S09/S10 diagnostik read-only 0017: 5 kueri PASS\n";
