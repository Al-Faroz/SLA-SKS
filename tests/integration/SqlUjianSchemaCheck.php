<?php

declare(strict_types=1);

/**
 * QA MariaDB sintetis S06A. Hanya dijalankan pada basis data CI sementara,
 * BUKAN database SLA-SKS pengguna. Jangan jalankan di XAMPP produksi.
 */
$dsn = getenv('SLA_TEST_DB_DSN');
if (!$dsn || !str_starts_with($dsn, 'mysql:dbname=sla_s06_test;')) {
    fwrite(STDERR, "Dilarang menjalankan fixture kecuali SLA_TEST_DB_DSN menunjuk sla_s06_test.\n");
    exit(2);
}
$db = new PDO($dsn, (string) getenv('SLA_TEST_DB_USER'), (string) getenv('SLA_TEST_DB_PASS'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'sla_s06_test') {
    throw new RuntimeException('Nama basis data tes salah.');
}
foreach (['progres_tp','versi_konten_tp'] as $parent) {
    $db->exec('CREATE TABLE ' . $parent
        . ' (id BIGINT UNSIGNED NOT NULL PRIMARY KEY) ENGINE=InnoDB');
}
$db->exec('INSERT INTO progres_tp VALUES(11)');
$db->exec('INSERT INTO versi_konten_tp VALUES(21)');
$sql = file_get_contents(dirname(__DIR__, 2) . '/database/sql/updates/0015_mesin_ujian_fondasi.sql');
if (!is_string($sql)) {
    throw new RuntimeException('Patch SQL ujian tidak ditemukan.');
}
$sql = preg_replace('/^--.*$/m', '', $sql);
foreach (explode(';', $sql) as $statement) {
    if (trim($statement) !== '') {
        $db->exec($statement);
    }
}
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ([
    'percobaan_ujian', 'butir_percobaan', 'jawaban_percobaan',
    'riwayat_status_ujian', 'segmen_jeda_ujian', 'pelanggaran_ujian',
] as $expected) {
    if (!in_array($expected, $tables, true)) {
        throw new RuntimeException('Tabel belum tersedia: ' . $expected);
    }
}
$now = '2026-10-24 12:00:00';
$db->prepare(
    'INSERT INTO percobaan_ujian(token_publik,progres_tp_id,versi_konten_tp_id,jenis,nomor_percobaan,'
    . 'status,jumlah_soal,kkm,durasi_detik,sisa_detik,dibuat_pada,diubah_pada) VALUES(?,?,?, ?,1,?,10,75,1800,1800,?,?)'
)->execute([str_repeat('a', 48), 11, 21, 'diagnostik', 'baru', $now, $now]);
$attempt = (int) $db->lastInsertId();
$db->prepare('INSERT INTO butir_percobaan(percobaan_ujian_id,soal_asal_id,posisi,pertanyaan,opsi_acak_json,kunci_acak) VALUES(?,?,?,?,?,?)')
    ->execute([$attempt, 1, 1, 'Contoh soal', '{"A":"opsi1","B":"opsi2","C":"opsi3","D":"opsi4","E":"opsi5"}','C']);
$butir = (int) $db->lastInsertId();
$db->prepare('INSERT INTO jawaban_percobaan(percobaan_ujian_id,butir_percobaan_id,opsi_dipilih,waktu_disimpan) VALUES(?,?,?,?)')
    ->execute([$attempt, $butir, 'B', $now]);
$db->prepare('INSERT INTO riwayat_status_ujian(percobaan_ujian_id,status_lama,status_baru,alasan,terjadi_pada) VALUES(?,?,?,?,?)')
    ->execute([$attempt, null, 'baru', 'Fixture', $now]);
$db->prepare('INSERT INTO segmen_jeda_ujian(percobaan_ujian_id,jenis,dimulai_pada,alasan) VALUES(?,?,?,?)')
    ->execute([$attempt, 'fullscreen', $now, 'Fixture']);
$db->prepare('INSERT INTO pelanggaran_ujian(percobaan_ujian_id,kunci_insiden,kategori,dihitung,alasan,terjadi_pada) VALUES(?,?,?,?,?,?)')
    ->execute([$attempt, str_repeat('b', 32), 'fullscreen', 1, 'Fixture', $now]);

// Unique key: satu jawaban per butir.
try {
    $db->prepare('INSERT INTO jawaban_percobaan(percobaan_ujian_id,butir_percobaan_id,opsi_dipilih,waktu_disimpan) VALUES(?,?,?,?)')
        ->execute([$attempt, $butir, 'D', $now]);
    throw new RuntimeException('UNIQUE jawaban gagal melindungi duplikasi.');
} catch (PDOException $e) {
    if ((string) $e->getCode() !== '23000') {
        throw $e;
    }
}
// FK: percobaan tidak boleh menunjuk progres yang tidak dikenal.
try {
    $db->prepare('INSERT INTO percobaan_ujian(token_publik,progres_tp_id,versi_konten_tp_id,jenis,nomor_percobaan,status,jumlah_soal,kkm,durasi_detik,sisa_detik,dibuat_pada,diubah_pada) VALUES(?,?,?, ?,2,?,10,75,1800,1800,?,?)')
        ->execute([str_repeat('c', 48), 999, 21, 'diagnostik', 'baru', $now, $now]);
    throw new RuntimeException('FOREIGN KEY percobaan/progres tidak berjalan.');
} catch (PDOException $e) {
    if ((string) $e->getCode() !== '23000') {
        throw $e;
    }
}
echo "S06A MariaDB: 6 tabel, FK, unique answer PASS\n";
