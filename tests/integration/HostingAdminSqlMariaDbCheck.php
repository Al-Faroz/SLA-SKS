<?php

declare(strict_types=1);

// Test integrasi sintetis. TIDAK mengakses DB XAMPP atau data pengguna.
// Dijalankan di GitHub Actions dengan MariaDB ephemeral.
use App\Services\AdminHostingSqlService;

require dirname(__DIR__, 2) . '/app/Services/AdminHostingSqlService.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = getenv('QA_DB_HOST');
$user = getenv('QA_DB_USER');
$pass = getenv('QA_DB_PASSWORD');
$name = getenv('QA_DB_NAME');
if ($host === false || $user === false || $pass === false || $name === false
    || ! preg_match('/^[a-zA-Z0-9_]+$/D', $name)) {
    fwrite(STDERR, "Konfigurasi QA database sintetis tidak tersedia.\n");
    exit(1);
}

/** @return mysqli */
function sambung(): mysqli
{
    global $host, $user, $pass, $name;
    $db = new mysqli($host, $user, $pass, $name, 3306);
    $db->set_charset('utf8mb4');
    return $db;
}

/** @return int */
function hitung(mysqli $db, string $sql): int
{
    $r = $db->query($sql);
    if ($r === false) {
        throw new RuntimeException('Kueri pemeriksaan gagal.');
    }
    $row = $r->fetch_row();
    $r->free();
    return (int) $row[0];
}

function impor(mysqli $db, string $sql): void
{
    if (! $db->multi_query($sql)) {
        throw new RuntimeException('SQL generator gagal dijalankan.');
    }
    do {
        $result = $db->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());
}

$db = sambung();
$db->query("CREATE TABLE schema_version (
    versi VARCHAR(40) NOT NULL PRIMARY KEY,
    diterapkan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
$db->query("CREATE TABLE cp_mapel (
    id BIGINT UNSIGNED NOT NULL PRIMARY KEY
) ENGINE=InnoDB");
$db->query("CREATE TABLE pengguna (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    sandi_hash VARCHAR(255) NOT NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    wajib_ganti_sandi TINYINT NOT NULL DEFAULT 1,
    jumlah_gagal TINYINT UNSIGNED NOT NULL DEFAULT 0,
    terkunci_sampai DATETIME DEFAULT NULL,
    versi_sesi INT UNSIGNED NOT NULL DEFAULT 1,
    login_terakhir DATETIME DEFAULT NULL
) ENGINE=InnoDB");
$db->query("CREATE TABLE pengguna_peran (
    pengguna_id BIGINT UNSIGNED NOT NULL,
    peran ENUM('admin','guru','siswa','pimpinan') NOT NULL,
    PRIMARY KEY (pengguna_id,peran),
    FOREIGN KEY (pengguna_id) REFERENCES pengguna(id)
) ENGINE=InnoDB");
$db->query("CREATE TABLE log_aktivitas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED DEFAULT NULL,
    peran_aktif ENUM('admin','guru','siswa','pimpinan') DEFAULT NULL,
    peristiwa VARCHAR(60) NOT NULL,
    catatan VARCHAR(255) DEFAULT NULL,
    waktu DATETIME NOT NULL,
    FOREIGN KEY (pengguna_id) REFERENCES pengguna(id)
) ENGINE=InnoDB");
$db->query("INSERT INTO schema_version (versi) VALUES
    ('V5-0001-DATA-GEN2025'), ('V5-0002-FAQ-DRAF')");
for ($i = 1; $i <= 15; $i++) {
    $db->query('INSERT INTO cp_mapel (id) VALUES (' . $i . ')');
}
$dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT INTO pengguna
    (id,username,nama,sandi_hash,status,wajib_ganti_sandi)
    VALUES (1,?,?,?,"nonaktif",1)');
$legacyUsername = 'admin_historis_uji';
$legacyName = 'Admin Historis Sintetis';
$stmt->bind_param('sss', $legacyUsername, $legacyName, $dummyHash);
$stmt->execute();
$db->query("INSERT INTO pengguna_peran (pengguna_id,peran) VALUES (1,'admin')");

$sandi = bin2hex(random_bytes(16));
$sql = AdminHostingSqlService::buat(
    'admin_baru_qa', "Admin O'Brian — QA",
    password_hash($sandi, PASSWORD_DEFAULT)
);
if (str_contains($sql, $sandi)) {
    throw new RuntimeException('Generator membocorkan sandi plaintext.');
}

impor($db, $sql);
if (hitung($db, "SELECT COUNT(*) FROM pengguna p JOIN pengguna_peran r
    ON r.pengguna_id=p.id WHERE r.peran='admin' AND p.status='aktif'") !== 1
    || hitung($db, "SELECT COUNT(*) FROM pengguna
    WHERE username='admin_baru_qa' AND wajib_ganti_sandi=1") !== 1
    || hitung($db, "SELECT COUNT(*) FROM pengguna
    WHERE id=1 AND status='nonaktif'") !== 1
    || hitung($db, "SELECT COUNT(*) FROM schema_version
    WHERE versi='V5-0003-ADMIN-PRODUKSI'") !== 1
    || hitung($db, "SELECT COUNT(*) FROM log_aktivitas
    WHERE peristiwa='admin_produksi_dibuat'") !== 1) {
    throw new RuntimeException('Hasil bootstrap tidak memenuhi seluruh kontrak.');
}
$db->close();

// Impor dua kali HARUS ditolak pada preflight (CHECK gagal) tanpa duplikasi.
$ditolak = false;
try {
    $replay = sambung();
    impor($replay, $sql);
    $replay->close();
} catch (mysqli_sql_exception $e) {
    $ditolak = true;
}
if (! $ditolak) {
    throw new RuntimeException('Impor ulang SQL Admin tidak ditolak.');
}
$periksa = sambung();
if (hitung($periksa, "SELECT COUNT(*) FROM pengguna p JOIN pengguna_peran r
    ON r.pengguna_id=p.id WHERE r.peran='admin' AND p.status='aktif'") !== 1) {
    throw new RuntimeException('Impor ulang mengubah jumlah Admin.');
}
$periksa->close();
echo "PASS: SQL Admin v5 berjalan di MariaDB sintetis, legacy aman, replay ditolak.\n";
