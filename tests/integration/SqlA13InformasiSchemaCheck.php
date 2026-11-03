<?php

declare(strict_types=1);

/**
 * Verifikasi patch V4-0012 pada MariaDB disposable CI (bukan dump privat).
 * Jalankan dengan SLA_TEST_DB_DSN / USER / PASS.
 */
$dsn = getenv('SLA_TEST_DB_DSN');
$user = getenv('SLA_TEST_DB_USER');
$pass = getenv('SLA_TEST_DB_PASS');

if (!$dsn || $user === false || $pass === false) {
    fwrite(STDERR, "Konfigurasi DB sintetis A13 belum tersedia.\n");
    exit(1);
}

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE pengguna (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('CREATE TABLE schema_version (versi VARCHAR(40) NOT NULL PRIMARY KEY,
    diterapkan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec('INSERT INTO pengguna (id) VALUES (1001), (1002)');

$sql = file_get_contents(dirname(__DIR__, 2) .
    '/database/sql/updates/V4-0012_PENGUMUMAN_FAQ.sql');
if (!is_string($sql)) {
    throw new RuntimeException('Berkas patch SQL tidak dapat dibaca.');
}
foreach (preg_split('/;\s*\n/', $sql) as $statement) {
    $statement = trim($statement);
    if ($statement !== '' && preg_match('/^(?:--[^\n]*\n|\s)*([A-Z]+)\b/m', $statement)) {
        // Hilangkan baris komentar agar PDO menerima tepat satu statement.
        $statement = trim(preg_replace('/^--.*$/m', '', $statement));
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

$versi = $pdo->query("SELECT COUNT(*) FROM schema_version
    WHERE versi='V4-0012-PENGUMUMAN-FAQ'")->fetchColumn();
if ((int)$versi !== 1) {
    throw new RuntimeException('Penanda patch V4-0012 tidak tercatat.');
}
foreach (['pengumuman','faq','riwayat_informasi'] as $table) {
    if ((int) $pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn() !== 0) {
        throw new RuntimeException('Tabel '.$table.' harus kosong sesudah patch.');
    }
    $fk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?
        AND REFERENCED_TABLE_NAME='pengguna'");
    $fk->execute([$table]);
    if ((int) $fk->fetchColumn() < 1) {
        throw new RuntimeException('FK pengguna tidak tersedia: '.$table);
    }
}
$pdo->exec("INSERT INTO pengumuman
    (judul,isi,status,dibuat_oleh,diubah_oleh,versi,dibuat_pada,diubah_pada)
    VALUES ('Tes CI','Isi sintetis','draf',1001,1001,1,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
$pdo->exec("INSERT INTO faq
    (pertanyaan,jawaban,urutan,status,dibuat_oleh,diubah_oleh,versi,dibuat_pada,diubah_pada)
    VALUES ('Pertanyaan sintetis','Jawaban sintetis',1,'terbit',1002,1002,1,
    UTC_TIMESTAMP(),UTC_TIMESTAMP())");
$pdo->exec("INSERT INTO riwayat_informasi
    (jenis,objek_id,aksi,pengguna_id,peran_aktif,sebelum_json,sesudah_json,terjadi_pada)
    VALUES ('faq',1,'terbit',1002,'admin',NULL,'{}',UTC_TIMESTAMP())");
foreach (['pengumuman','faq','riwayat_informasi'] as $table) {
    if ((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn() !== 1) {
        throw new RuntimeException('INSERT sintetis gagal pada '.$table);
    }
}
echo "PASS V4-0012 MariaDB: 3 tabel, FK pengguna, schema_version, data sintetis.\n";
