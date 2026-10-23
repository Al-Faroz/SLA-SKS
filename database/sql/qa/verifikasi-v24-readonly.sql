-- SLA-SKS v2.4 — pemeriksaan baca-saja setelah impor ke database uji.
-- Pilih database target dari panel phpMyAdmin SEBELUM menjalankan SQL.
-- Tidak ada USE hardcoded, perubahan data, reset, atau pembacaan data pribadi.
-- Database uji terverifikasi pemilik: sla_sks_v3 (MariaDB 10.4.32).

SELECT DATABASE() AS database_diperiksa, VERSION() AS versi_mesin;

SELECT
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bab') AS tabel_bab_lama,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tp'
        AND COLUMN_NAME = 'paket_semester_id') AS relasi_tp_ke_paket,
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tugas_hots_materi'
        AND COLUMN_NAME = 'materi_id') AS relasi_hots_ke_materi,
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY') AS jumlah_foreign_key;

SELECT 'TP tanpa paket semester' AS pemeriksaan, COUNT(*) AS jumlah_masalah
FROM tp t LEFT JOIN paket_semester p ON p.id = t.paket_semester_id WHERE p.id IS NULL
UNION ALL
SELECT 'Materi tanpa TP', COUNT(*)
FROM materi m LEFT JOIN tp t ON t.id = m.tp_id WHERE t.id IS NULL
UNION ALL
SELECT 'Siswa tanpa pengguna', COUNT(*)
FROM siswa s LEFT JOIN pengguna p ON p.id = s.pengguna_id WHERE p.id IS NULL
UNION ALL
SELECT 'TP-CP tanpa TP', COUNT(*)
FROM tp_cp c LEFT JOIN tp t ON t.id = c.tp_id WHERE t.id IS NULL
UNION ALL
SELECT 'TP-CP tanpa versi CP', COUNT(*)
FROM tp_cp c LEFT JOIN ref_cp_versi v ON v.id = c.cp_versi_id WHERE v.id IS NULL;

-- Jumlah objek nonpribadi/agregat. Tidak menampilkan NISN, nama, hash atau token.
SELECT
    (SELECT COUNT(*) FROM pengguna) AS jumlah_pengguna,
    (SELECT COUNT(*) FROM siswa) AS jumlah_siswa,
    (SELECT COUNT(*) FROM ref_mapel) AS jumlah_mapel,
    (SELECT COUNT(*) FROM ref_cp) AS jumlah_cp,
    (SELECT COUNT(*) FROM paket_semester) AS jumlah_paket,
    (SELECT COUNT(*) FROM tp) AS jumlah_tp,
    (SELECT COUNT(*) FROM materi) AS jumlah_materi;

SELECT versi FROM schema_version ORDER BY versi;
