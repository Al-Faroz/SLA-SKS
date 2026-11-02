-- SLA-SKS v2.5 | Pemeriksaan BACA-SAJA setelah uji sunting CP, BAB, TP.
-- Disusun dari dump XAMPP sla_sks_v4 (11).sql (02-11-2026 10.24).
-- Tidak berisi INSERT, UPDATE, DELETE, ALTER, DROP, atau data pribadi.
-- Jalankan hanya setelah memilih database sla_sks_v4 di phpMyAdmin.

SELECT DATABASE() AS database_diperiksa, VERSION() AS engine_aktif;

SELECT `versi`, `diterapkan_pada`
FROM `schema_version`
WHERE `versi` IN ('V4-0006-KOREKSI-TP-SIAP','V4-0007-CP-LIMA-ISIAN')
ORDER BY `versi`;

-- Verifikasi form CP lima isian: keempat metadata lawas adalah opsional.
SELECT `COLUMN_NAME` AS kolom, `IS_NULLABLE` AS boleh_null
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'ref_cp_versi'
  AND `COLUMN_NAME` IN ('otoritas','nomor_dokumen','sumber_url','tanggal_berlaku')
ORDER BY `ORDINAL_POSITION`;

SELECT
 (SELECT COUNT(*) FROM `ref_cp`) AS total_cp,
 (SELECT COUNT(*) FROM `ref_cp_versi` WHERE `status`='aktif') AS cp_versi_aktif,
 (SELECT COUNT(*) FROM `cp_mapel`) AS cp_operasional,
 (SELECT COUNT(*) FROM `bab`) AS bab,
 (SELECT COUNT(*) FROM `tp`) AS total_tp,
 (SELECT COUNT(*) FROM `tp` WHERE `status`='siap') AS tp_siap,
 (SELECT COUNT(*) FROM `materi`) AS materi,
 (SELECT COUNT(*) FROM `soal`) AS soal,
 (SELECT COUNT(*) FROM `progres_tp`) AS tp_sudah_dimulai_siswa;

-- Periksa kelengkapan snapshot TP Siap dan catatan penerbitan;
-- jumlah log tidak selalu sama dengan jumlah versi (log menyimpan ID snapshot).
-- Jangan memperbaiki otomatis bila jumlah tidak sama. Telusuri dulu.
SELECT t.`id` AS tp_id, t.`kode_tp`, t.`status`,
       (SELECT COUNT(*) FROM `versi_konten_tp` v WHERE v.`tp_id`=t.`id`)
         AS snapshot_tersimpan,
       (SELECT MAX(v.`nomor_versi`) FROM `versi_konten_tp` v WHERE v.`tp_id`=t.`id`)
         AS nomor_versi_terakhir,
       (SELECT COUNT(*) FROM `log_aktivitas` l
          WHERE l.`peristiwa`='tp_disiapkan'
            AND l.`catatan` LIKE CONCAT('TP #',t.`id`,' diterbitkan dalam versi %'))
         AS log_penerbitan
FROM `tp` t
WHERE t.`status`='siap'
ORDER BY t.`id`;

-- Belum ada progres bukan bukti guard TP sudah bekerja.
SELECT COUNT(*) AS tp_dengan_progres_siswa
FROM (SELECT DISTINCT `tp_id` FROM `progres_tp`) AS digunakan;
