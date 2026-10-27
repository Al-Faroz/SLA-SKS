-- SLA-SKS v2.5: QA baca-saja untuk integrasi Paket Draf → BAB → TP.
-- Jalankan pada phpMyAdmin database sla_sks_v4 setelah R2 selesai.
-- Tidak membuat, mengubah, atau menghapus data.
USE sla_sks_v4;

-- Pemeriksaan referensi dan prasyarat Service.
SELECT
 (SELECT COUNT(*) FROM ref_angkatan) AS jumlah_gen,
 (SELECT COUNT(*) FROM penugasan_guru_gen WHERE guru_id IS NOT NULL) AS jumlah_g1_berpenanggungjawab,
 (SELECT COUNT(*) FROM ref_tahun_ajaran) AS jumlah_tahun_ajaran,
 (SELECT COUNT(*) FROM ref_kurikulum) AS jumlah_kurikulum,
 (SELECT COUNT(*) FROM ref_semester) AS jumlah_semester,
 (SELECT COUNT(*) FROM ref_kelas WHERE nama IN ('VII','VIII','IX')) AS jumlah_kelas_fase_d,
 (SELECT COUNT(*) FROM cp_mapel) AS jumlah_cp_operasional,
 (SELECT COUNT(*) FROM paket_semester) AS jumlah_paket,
 (SELECT COUNT(*) FROM bab) AS jumlah_bab,
 (SELECT COUNT(*) FROM tp WHERE status = 'draf') AS jumlah_tp_draf,
 (SELECT COUNT(*) FROM tp WHERE status = 'arsip') AS jumlah_tp_arsip;

-- Hanya mapel/kode referensi dan ketersediaan CP; tanpa data siswa.
SELECT m.id AS mapel_id, m.kode AS kode_mapel,
       COUNT(DISTINCT cm.id) AS jumlah_cp_mapel
FROM ref_mapel m
LEFT JOIN cp_mapel cm ON cm.mapel_id = m.id
GROUP BY m.id, m.kode
ORDER BY m.kode;

-- Metadata skema saja: kolom wajib insert paket/TP, tipe dan default.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
       COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA='sla_sks_v4'
  AND TABLE_NAME IN ('paket_semester','tp')
  AND COLUMN_NAME IN
  ('angkatan_id','mapel_id','semester','tahun_ajaran_id','kurikulum_id',
   'nomor_versi','dibuat_oleh','dibuat_pada','status','id',
   'paket_semester_id','bab_id','cp_mapel_id','jenis_tp',
   'nama_tp','deskripsi_tp','urutan_dalam_bab','kode_tp','judul',
   'rumusan','indikator','urutan_atp','struktur_versi',
   'kkm','kkm_khusus','diag_tayang','sum_tayang','sum_maks_percobaan')
ORDER BY TABLE_NAME, ORDINAL_POSITION;
