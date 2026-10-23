<?php

declare(strict_types=1);

/**
 * Smoke test query MySQL/MariaDB terhadap fixture anonim.
 * Tidak membutuhkan sesi nyata; pengujian HTTP/CI4 tetap terpisah.
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = new mysqli('127.0.0.1', 'root', '', 'sla_sks_qa', 3306);
$db->set_charset('utf8mb4');

function assertCount(int $expected, array $rows, string $description): void
{
    if (count($rows) !== $expected) {
        throw new RuntimeException($description . ': mengharapkan ' . $expected . ' hasil, mendapat ' . count($rows));
    }
}

// Sesuai jalur query yang digunakan PetaTpService::genMapelDiampu().
$daftar = $db->query(
    'SELECT g.angkatan_id, g.mapel_id, a.kode AS kode_gen, a.nama AS nama_gen,
            m.kode AS kode_mapel, m.nama AS nama_mapel
     FROM penugasan_guru_gen g
     JOIN ref_angkatan a ON a.id = g.angkatan_id
     JOIN ref_mapel m ON m.id = g.mapel_id
     WHERE g.guru_id = 1 ORDER BY a.kode ASC, m.nama ASC'
)->fetch_all(MYSQLI_ASSOC);
assertCount(1, $daftar, 'G1 Guru A');
if ((int) $daftar[0]['angkatan_id'] !== 1) {
    throw new RuntimeException('G1 Guru A menampilkan Gen yang salah');
}

// Guru A tidak boleh mengakses Gen B.
$jumlahIzin = $db->query(
    'SELECT COUNT(*) AS total FROM penugasan_guru_gen
     WHERE angkatan_id = 2 AND mapel_id = 1 AND guru_id = 1'
)->fetch_assoc();
if ((int) $jumlahIzin['total'] !== 0) {
    throw new RuntimeException('Kontrol akses silang G1 gagal');
}

// Paket hanya untuk pasangan Gen–Mapel–Semester yang dipilih.
$paket = $db->query(
    'SELECT p.id, p.angkatan_id, p.mapel_id, p.semester, p.tahun_ajaran_id,
            p.kurikulum_id, p.nomor_versi, p.status
     FROM paket_semester p
     WHERE p.angkatan_id = 1 AND p.mapel_id = 1 AND p.semester = 4
     ORDER BY p.nomor_versi DESC, p.id DESC'
)->fetch_all(MYSQLI_ASSOC);
assertCount(1, $paket, 'Paket semester Guru A');

$tp = $db->query(
    'SELECT t.id,t.paket_semester_id,t.kode_tp,t.judul,t.rumusan,t.indikator,
            t.urutan_atp,t.status,t.struktur_versi
     FROM tp t WHERE t.paket_semester_id IN (1)
     ORDER BY t.paket_semester_id ASC, t.urutan_atp ASC'
)->fetch_all(MYSQLI_ASSOC);
assertCount(1, $tp, 'TP milik Guru A');
if ($tp[0]['kode_tp'] !== 'TP-01') {
    throw new RuntimeException('TP lintas paket tidak terisolasi');
}

$kosong = $db->query(
    'SELECT p.id FROM paket_semester p
     WHERE p.angkatan_id = 1 AND p.mapel_id = 1 AND p.semester = 1'
)->fetch_all(MYSQLI_ASSOC);
assertCount(0, $kosong, 'Semester belum memiliki paket');

echo "PASS: G1, pembatasan Guru, pemisahan Gen, dan semester kosong.\n";
