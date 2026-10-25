-- QA READ ONLY 0018; aman untuk DB yang mengandung data nyata.
-- HASIL dari dump lokal: resume_materi ada; tugas_hots_materi bank Guru;
-- jawaban_hots_materi belum ada. Jangan menjalankan DROP/RENAME.
SELECT DATABASE() AS database_aktif, VERSION() AS versi_mariadb_mysql;

SELECT target.nama AS tabel, CASE WHEN t.TABLE_NAME IS NULL THEN 'BELUM_ADA'
    ELSE 'SUDAH_ADA' END AS status_tabel, COALESCE(t.ENGINE, '-') AS engine
FROM ( SELECT 'resume_materi' AS nama UNION ALL
       SELECT 'tugas_hots_materi' UNION ALL
       SELECT 'jawaban_hots_materi' ) AS target
LEFT JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = DATABASE()
    AND t.TABLE_NAME = target.nama
ORDER BY target.nama;

SELECT TABLE_NAME AS tabel, COUNT(*) AS jumlah_kolom,
    SUM(COLUMN_NAME IN ('id','progres_tp_id','materi_id','versi_konten_tp_id',
        'isi','status','revisi','dibuat_pada','diubah_pada','dikirim_pada',
        'nilai','dinilai_oleh','umpan_balik','dinilai_pada')) AS kolom_jawaban_dikenali,
    SUM(COLUMN_NAME IN ('id','materi_id','row_uuid','pertanyaan','petunjuk'))
        AS kolom_bank_guru_dikenali
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('resume_materi','tugas_hots_materi','jawaban_hots_materi')
GROUP BY TABLE_NAME ORDER BY TABLE_NAME;

SELECT TABLE_NAME AS tabel, CONSTRAINT_NAME AS nama, CONSTRAINT_TYPE AS jenis
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN
    ('resume_materi','tugas_hots_materi','jawaban_hots_materi')
ORDER BY TABLE_NAME,CONSTRAINT_NAME;

SELECT TABLE_NAME AS tabel, COLUMN_NAME AS kolom,
    REFERENCED_TABLE_NAME AS tabel_rujukan
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
    AND TABLE_NAME IN ('resume_materi','tugas_hots_materi','jawaban_hots_materi')
ORDER BY TABLE_NAME,COLUMN_NAME;

-- Sebelum impor 0018: tabel bank Guru 5 kolom, tabel Resume siswa 14 kolom,
-- tabel jawaban siswa BELUM_ADA. Setelah impor: jawaban siswa 14 kolom.
-- Jika ternyata tabel sudah ada namun kolomnya salah, JANGAN impor ulang;
-- laporkan struktur untuk langkah pemulihan khusus.
