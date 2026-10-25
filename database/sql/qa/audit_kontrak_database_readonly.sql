-- SLA-SKS v2.4 QA metadata ONLY: BACA-SAJA.
-- Tidak ada operasi DDL/DML; aman pada tabel yang belum ada.
SELECT DATABASE() AS database_aktif, VERSION() AS versi_server;

SELECT target.nama AS tabel,
       CASE WHEN t.TABLE_NAME IS NULL THEN 'BELUM_ADA' ELSE 'SUDAH_ADA' END AS keberadaan,
       COALESCE(t.ENGINE, '-') AS engine
FROM (
  SELECT 'tp' AS nama UNION ALL SELECT 'materi' UNION ALL
  SELECT 'tugas_hots_materi' UNION ALL SELECT 'jawaban_hots_materi' UNION ALL
  SELECT 'resume_materi' UNION ALL SELECT 'progres_tp' UNION ALL
  SELECT 'progres_materi' UNION ALL SELECT 'halaman_progres_materi' UNION ALL
  SELECT 'schema_version'
) target
LEFT JOIN information_schema.TABLES t
  ON t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME=target.nama
ORDER BY target.nama;

SELECT TABLE_NAME AS tabel, COLUMN_NAME AS kolom, COLUMN_TYPE AS tipe,
       IS_NULLABLE AS boleh_null, ORDINAL_POSITION AS urut
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN
  ('tp','materi','tugas_hots_materi','jawaban_hots_materi','resume_materi')
ORDER BY TABLE_NAME,ORDINAL_POSITION;

SELECT TABLE_NAME AS tabel,CONSTRAINT_NAME AS nama,
       CONSTRAINT_TYPE AS jenis
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN
  ('materi','tugas_hots_materi','jawaban_hots_materi','resume_materi')
ORDER BY TABLE_NAME,CONSTRAINT_NAME;

SELECT TABLE_NAME AS tabel,COLUMN_NAME AS kolom,
       REFERENCED_TABLE_NAME AS rujukan,REFERENCED_COLUMN_NAME AS kolom_rujukan
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
  AND TABLE_NAME IN ('tugas_hots_materi','jawaban_hots_materi','resume_materi')
ORDER BY TABLE_NAME,COLUMN_NAME;
