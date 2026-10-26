<?php

declare(strict_types=1);

/** Tes DDL hanya untuk MariaDB CI bernama sla_s08_test. Tidak boleh data nyata. */
$dsn = (string) getenv('SLA_TEST_DB_DSN');
if (!str_starts_with($dsn, 'mysql:dbname=sla_s08_test;')) {
    fwrite(STDERR, "Fixture ini hanya boleh dijalankan untuk sla_s08_test.\n");
    exit(2);
}
$db = new PDO($dsn, (string) getenv('SLA_TEST_DB_USER'),
    (string) getenv('SLA_TEST_DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'sla_s08_test') {
    throw new RuntimeException('Database tes salah.');
}
foreach (['progres_tp','materi','versi_konten_tp'] as $table) {
    $db->exec('CREATE TABLE ' . $table
        . ' (id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB');
    $db->exec('INSERT INTO ' . $table . ' VALUES(1)');
}
$sql = file_get_contents(dirname(__DIR__,2)
    . '/database/sql/updates/0016_progres_aktivitas_materi.sql');
if (!is_string($sql)) throw new RuntimeException('SQL 0016 tidak tersedia.');
$sql = preg_replace('/^--.*$/m', '', $sql);
foreach (explode(';', $sql) as $statement) {
    if (trim($statement) !== '') $db->exec($statement);
}
$db->prepare(
    'INSERT INTO progres_materi(progres_tp_id,materi_id,versi_konten_tp_id,jenis,'
    . 'status,waktu_aktif_detik,heartbeat_terakhir_pada,dibuat_pada,diubah_pada)'
    . ' VALUES(1,1,1,?, ?,0,?,?,?)'
)->execute(['gambar','berjalan','2026-10-24 00:00:00',
    '2026-10-24 00:00:00','2026-10-24 00:00:00']);
$id = (int) $db->lastInsertId();
if ($id < 1) throw new RuntimeException('Insert progres media gagal.');
$db->prepare('INSERT INTO halaman_progres_materi(progres_materi_id,nomor_halaman,waktu_aktif_detik)'
    . ' VALUES(?,1,15)')->execute([$id]);
try {
    $db->prepare('INSERT INTO halaman_progres_materi(progres_materi_id,nomor_halaman)'
        . ' VALUES(?,1)')->execute([$id]);
    throw new RuntimeException('UNIQUE halaman tidak aktif.');
} catch (PDOException $e) {
    if ((string) $e->getCode() !== '23000') throw $e;
}
try {
    $db->prepare('INSERT INTO progres_materi(progres_tp_id,materi_id,versi_konten_tp_id,jenis,'
        . 'status,dibuat_pada,diubah_pada) VALUES(1,1,1,?,?,?,?)')
        ->execute(['gambar','berjalan','2026-10-24 00:00:00','2026-10-24 00:00:00']);
    throw new RuntimeException('UNIQUE progres gagal.');
} catch (PDOException $e) {
    if ((string) $e->getCode() !== '23000') throw $e;
}
try {
    $db->prepare('INSERT INTO halaman_progres_materi(progres_materi_id,nomor_halaman)'
        . ' VALUES(9999,1)')->execute();
    throw new RuntimeException('FK halaman/progres gagal.');
} catch (PDOException $e) {
    if ((string) $e->getCode() !== '23000') throw $e;
}
echo "S08B MariaDB: 2 tabel, FK, unique progres dan halaman PASS\n";
