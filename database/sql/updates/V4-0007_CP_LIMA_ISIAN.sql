-- SLA-SKS v2.5 - V4-0007: CP dengan lima isian.
-- Disesuaikan dengan dump privat sla_sks_v4 (10).sql,
-- diekspor 02-11-2026 pukul 10.02, MariaDB 10.4.32.
--
-- TARGET: database sla_sks_v4 yang sudah mengandung V4-0001-R2 s.d. V4-0006.
-- BUKAN dump instalasi. Impor hanya file ini pada database yang sudah ada.
-- Sebelum impor: backup database dan folder writable/; pilih sla_sks_v4.
--
-- Empat kolom metadata warisan tidak lagi menjadi isian wajib CP.
-- Nilai historis tetap disimpan. Tidak ada DROP, DELETE, UPDATE data,
-- TRUNCATE, CREATE ulang tabel, perubahan FK, atau perubahan identitas CP.
-- ALTER dapat diulang jika impor sebelumnya berhasil sebagian.

ALTER TABLE `ref_cp_versi`
    MODIFY COLUMN `otoritas` VARCHAR(150) NULL DEFAULT NULL,
    MODIFY COLUMN `nomor_dokumen` VARCHAR(120) NULL DEFAULT NULL,
    MODIFY COLUMN `sumber_url` VARCHAR(1024) NULL DEFAULT NULL,
    MODIFY COLUMN `tanggal_berlaku` DATE NULL DEFAULT NULL;

-- Catat penanda versi tepat satu kali.
INSERT INTO `schema_version` (`versi`)
SELECT 'V4-0007-CP-LIMA-ISIAN'
WHERE NOT EXISTS (
    SELECT 1 FROM `schema_version`
    WHERE `versi` = 'V4-0007-CP-LIMA-ISIAN'
);
