-- SLA-SKS v2.4 S09/S10 / SQL 0017
-- Diagnostik BACA-SAJA: sebelum/ setelah impor dan saat MySQL #1050.
-- Tidak memodifikasi database. Aman pada tabel berisi data siswa.

SELECT DATABASE() AS database_aktif, VERSION() AS versi_server;

-- KEDUANYA SUDAH_ADA = jangan impor 0017 kembali.
-- HANYA SATU SUDAH_ADA = hentikan, periksa instalasi parsial.
SELECT target.nama_tabel AS tabel,
       CASE WHEN t.TABLE_NAME IS NULL THEN 'BELUM_ADA'
            ELSE 'SUDAH_ADA' END AS keberadaan,
       COALESCE(t.ENGINE, '-') AS mesin
FROM (
    SELECT 'resume_materi' AS nama_tabel
    UNION ALL
    SELECT 'tugas_hots_materi'
) AS target
LEFT JOIN information_schema.TABLES AS t
    ON t.TABLE_SCHEMA = DATABASE()
   AND t.TABLE_NAME = target.nama_tabel
ORDER BY target.nama_tabel;

-- Setiap tabel lengkap mempunyai 14 kolom (seluruhnya dikenali).
SELECT TABLE_NAME AS tabel,
       COUNT(*) AS jumlah_kolom,
       SUM(COLUMN_NAME IN (
           'id', 'progres_tp_id', 'materi_id', 'versi_konten_tp_id',
           'isi', 'status', 'revisi', 'dibuat_pada', 'diubah_pada',
           'dikirim_pada', 'nilai', 'dinilai_oleh', 'umpan_balik', 'dinilai_pada'
       )) AS kolom_dikenali
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('resume_materi', 'tugas_hots_materi')
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;

-- Verifikasi PRIMARY, UNIQUE, empat FOREIGN KEY serta CHECK yang berlaku.
SELECT TABLE_NAME AS tabel, CONSTRAINT_NAME AS nama_kendala,
       CONSTRAINT_TYPE AS jenis_kendala
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('resume_materi', 'tugas_hots_materi')
ORDER BY TABLE_NAME, CONSTRAINT_TYPE, CONSTRAINT_NAME;

-- FK masing-masing harus ke progres_tp, materi, versi_konten_tp, pengguna.
SELECT TABLE_NAME AS tabel, COLUMN_NAME AS kolom,
       REFERENCED_TABLE_NAME AS tabel_rujukan,
       REFERENCED_COLUMN_NAME AS kolom_rujukan
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('resume_materi', 'tugas_hots_materi')
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME;

-- Jika kedua tabel SUDAH_ADA, boleh lanjut SHOW CREATE TABLE secara terpisah.
-- Jangan DROP/TRUNCATE atau jalankan 0017 untuk mengatasi #1050.
