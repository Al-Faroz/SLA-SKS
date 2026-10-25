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
$sql = file_get_contents(dirname(__DIR__,2) .
    '/database/sql/updates/0017_kegiatan_resume_hots_materi.sql');
if (!is_string($sql)) throw new RuntimeException('SQL 0017 tidak ditemukan.');
$sql = preg_replace('/^--.*$/m', '', $sql);
foreach (explode(';', $sql) as $statement) {
    if (trim($statement) !== '') $db->exec($statement);
}
foreach (['resume_materi', 'tugas_hots_materi'] as $table) {
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
echo "S09/S10 MariaDB 11.4: FK, UNIQUE, CHECK nilai dan kedua tabel PASS\n";
