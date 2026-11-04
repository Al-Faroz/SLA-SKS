-- SLA-SKS v2.5 / Patch additive V4-0005
-- Sumber verifikasi: dump privat sla_sks_v4 31-10-2026 14.06 WIB, MariaDB 10.4.32.
-- Dump menunjukkan bab.nama_bab VARCHAR(35) dan tp.nama_tp VARCHAR(35).
-- Master 04/05/10 menetapkan batas 50 karakter.
-- WAJIB BACKUP database dan writable/ sebelum impor pada salinan lokal.
-- Jalankan HANYA untuk database sla_sks_v4 setelah V4-0004 (bukan schema lama).
-- DDL MariaDB melakukan implicit commit; jangan mengandalkan rollback.
-- Tidak ada penghapusan/penggantian data akademik.

ALTER TABLE `bab`
  MODIFY COLUMN `nama_bab` VARCHAR(50) NOT NULL;

ALTER TABLE `tp`
  MODIFY COLUMN `nama_tp` VARCHAR(50) NULL DEFAULT NULL;

INSERT INTO `schema_version` (`versi`)
SELECT 'V4-0005-NAMA-ATP-50'
WHERE NOT EXISTS (
  SELECT 1 FROM `schema_version` WHERE `versi` = 'V4-0005-NAMA-ATP-50'
);

-- QA baca-saja setelah impor:
-- SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
-- FROM information_schema.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND (TABLE_NAME = 'bab' AND COLUMN_NAME = 'nama_bab'
--     OR TABLE_NAME = 'tp' AND COLUMN_NAME = 'nama_tp')
-- ORDER BY TABLE_NAME;
-- SELECT versi FROM schema_version WHERE versi = 'V4-0005-NAMA-ATP-50';
