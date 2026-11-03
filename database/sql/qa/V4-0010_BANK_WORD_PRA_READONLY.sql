-- SLA-SKS A8-7; pemeriksaan HANYA SELECT, tanpa data pribadi.
-- Jalankan di phpMyAdmin pada sla_sks_v4 SEBELUM dan SESUDAH V4-0010.
SELECT versi,diterapkan_pada
FROM schema_version
WHERE versi IN ('V4-0009-PRA-SESI-TERJADWAL','V4-0010-BANK-WORD-PRA')
ORDER BY diterapkan_pada;
SELECT table_name,engine
FROM information_schema.tables
WHERE table_schema=DATABASE() AND table_name IN
    ('impor_soal_pra_docx','soal_pra_semester','ujian_pra_semester');
SELECT column_name,column_type,is_nullable
FROM information_schema.columns
WHERE table_schema=DATABASE()
  AND table_name='impor_soal_pra_docx'
ORDER BY ordinal_position;
SELECT 'bank_soal_pra' AS keterangan,COUNT(*) jumlah
FROM soal_pra_semester
UNION ALL
SELECT 'percobaan_pra',COUNT(*) FROM percobaan_ujian WHERE jenis='pra_semester';
-- Setelah impor, struktur staging terpasang; jumlah soal/percobaan historis
-- harus SAMA seperti sebelum impor. Jangan unggah nama/NISN/jawaban/kunci.
