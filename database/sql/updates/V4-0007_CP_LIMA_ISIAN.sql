-- SLA-SKS V2.5 | V4-0007 | Form CP lima isian
-- Audit skema: dump sla_sks_v4 (9).sql tanggal 2026-11-02.
-- TANPA DROP, TRUNCATE, DELETE, UPDATE DATA, atau perubahan kunci/relasi.
-- Cadangkan database sebelum impor dan pastikan database terpilih sla_sks_v4.
-- Kompatibel MySQL 8 / MariaDB 10.11. Aman diulang (ALTER identik + versi unik).
--
-- Empat metadata warisan tetap tersedia untuk pembacaan/riwayat,
-- tetapi menjadi opsional bagi data CP baru.
ALTER TABLE `ref_cp_versi`
    MODIFY COLUMN `otoritas` VARCHAR(150) NULL DEFAULT NULL,
    MODIFY COLUMN `nomor_dokumen` VARCHAR(120) NULL DEFAULT NULL,
    MODIFY COLUMN `sumber_url` VARCHAR(1024) NULL DEFAULT NULL,
    MODIFY COLUMN `tanggal_berlaku` DATE NULL DEFAULT NULL;

INSERT INTO `schema_version` (`versi`)
SELECT 'V4-0007-CP-LIMA-ISIAN'
WHERE NOT EXISTS (
    SELECT 1 FROM `schema_version` WHERE `versi` = 'V4-0007-CP-LIMA-ISIAN'
);
