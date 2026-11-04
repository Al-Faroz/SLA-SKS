-- SLA-SKS v2.5 - Audit BACA-SAJA untuk pembaruan V4-0007.
-- Dirancang dari dump privat sla_sks_v4 (10).sql, 02-11-2026.
-- Jalankan di phpMyAdmin sebelum dan sesudah impor patch V4-0007.
-- SQL ini tidak mengubah data dan tidak menampilkan identitas siswa.

SELECT DATABASE() AS database_terpilih, VERSION() AS versi_database;

-- Dari dump semula: V4-0001-R2 dan V4-0006 ada; V4-0007 belum ada.
SELECT `versi`, `diterapkan_pada`
FROM `schema_version`
WHERE `versi` LIKE 'V4-%'
ORDER BY `versi`;

-- Sebelum patch: keempatnya NO; sesudah patch: keempatnya YES.
SELECT `COLUMN_NAME` AS kolom, `COLUMN_TYPE` AS tipe,
       `IS_NULLABLE` AS boleh_null, `COLUMN_DEFAULT` AS nilai_default
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'ref_cp_versi'
  AND `COLUMN_NAME` IN
      ('otoritas', 'nomor_dokumen', 'sumber_url', 'tanggal_berlaku')
ORDER BY FIELD(`COLUMN_NAME`,
    'otoritas', 'nomor_dokumen', 'sumber_url', 'tanggal_berlaku');

-- Semua agregat ini tidak memerlukan perubahan data.
SELECT
    (SELECT COUNT(*) FROM `ref_cp`) AS jumlah_cp,
    (SELECT COUNT(*) FROM `ref_cp_versi`) AS jumlah_versi_cp,
    (SELECT COUNT(*) FROM `cp_mapel`) AS jumlah_cp_operasional,
    (SELECT COUNT(*) FROM `bab`) AS jumlah_bab,
    (SELECT COUNT(*) FROM `tp`) AS jumlah_tp,
    (SELECT COUNT(*) FROM `progres_tp`) AS jumlah_progres_tp,
    (SELECT COUNT(*) FROM `permohonan_koreksi_tp`) AS jumlah_koreksi_historis;

-- Hasil 1 jika penanda berhasil terpasang, 0 sebelum patch.
SELECT COUNT(*) AS v4_0007_terpasang
FROM `schema_version`
WHERE `versi` = 'V4-0007-CP-LIMA-ISIAN';
