-- A8 V4-0009 audit: HANYA BACA, aman untuk phpMyAdmin.
-- Jalankan SEBELUM impor V4-0009 pada database sla_sks_v4.
SELECT versi,diterapkan_pada FROM schema_version
WHERE versi IN ('V4-0008-KEPUTUSAN-UJIAN-BATAL','V4-0009-PRA-SESI-TERJADWAL')
ORDER BY diterapkan_pada;
SELECT 'ujian_pra_semester' tabel,COUNT(*) jumlah FROM ujian_pra_semester
UNION ALL SELECT 'soal_pra_semester',COUNT(*) FROM soal_pra_semester
UNION ALL SELECT 'percobaan_ujian',COUNT(*) FROM percobaan_ujian;

-- Pemeriksaan berikut aman SEBELUM maupun SETELAH impor:
SELECT table_name FROM information_schema.tables
WHERE table_schema=DATABASE()
  AND table_name IN ('jadwal_pra_sesi','peserta_pra_susulan');
SELECT column_name,column_type,is_nullable
FROM information_schema.columns
WHERE table_schema=DATABASE()
  AND table_name='percobaan_ujian'
  AND column_name='jadwal_pra_sesi_id';
SELECT versi,diterapkan_pada FROM schema_version
WHERE versi='V4-0009-PRA-SESI-TERJADWAL';
SELECT table_name,constraint_name,constraint_type
FROM information_schema.table_constraints
WHERE table_schema=DATABASE()
  AND table_name IN ('jadwal_pra_sesi','peserta_pra_susulan','percobaan_ujian')
  AND (constraint_name LIKE 'fk_%' OR constraint_name LIKE 'uq_pra_%'
       OR constraint_name LIKE 'ck_jadwal_%')
ORDER BY table_name,constraint_name;
-- Semua SELECT aman saat tabel baru belum ada. Tidak ada nama/NISN/jawaban/kunci/hash.
