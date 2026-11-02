-- SLA-SKS v2.5 | Audit BACA-SAJA setelah sunting CP/BAB/TP
-- Dasar: dump privat sla_sks_v4 (11).sql, 02-11-2026 10.24 (MariaDB 10.4.32).
-- Perbaikan: tidak bergantung pada database aktif phpMyAdmin.
-- Tabel selalu memakai prefiks sla_sks_v4; tidak mengubah data maupun skema.
-- Aman dijalankan dari tab SQL / Impor meskipun phpMyAdmin berada di information_schema.
-- Database yang diperiksa HANYA sla_sks_v4.
--
-- Perintah USE hanya memilih konteks pembacaan, bukan mengubah isi database.
USE `sla_sks_v4`;

SELECT DATABASE() AS database_diperiksa, VERSION() AS engine_aktif;

SELECT `versi`, `diterapkan_pada`
FROM `sla_sks_v4`.`schema_version`
WHERE `versi` LIKE 'V4-000%'
ORDER BY `versi`;

-- Verifikasi empat metadata CP warisan telah menjadi opsional.
SELECT `COLUMN_NAME` AS kolom, `IS_NULLABLE` AS boleh_null
FROM `information_schema`.`COLUMNS`
WHERE `TABLE_SCHEMA` = 'sla_sks_v4'
  AND `TABLE_NAME` = 'ref_cp_versi'
  AND `COLUMN_NAME` IN ('otoritas','nomor_dokumen','sumber_url','tanggal_berlaku')
ORDER BY `ORDINAL_POSITION`;

-- Agregat saja: tidak menampilkan data pribadi.
SELECT
 (SELECT COUNT(*) FROM `sla_sks_v4`.`ref_cp`) AS total_cp,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`ref_cp_versi` WHERE `status`='aktif') AS cp_versi_aktif,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`cp_mapel`) AS cp_operasional,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`bab`) AS bab,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`tp`) AS total_tp,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`tp` WHERE `status`='siap') AS tp_siap,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`materi`) AS materi,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`soal`) AS soal,
 (SELECT COUNT(*) FROM `sla_sks_v4`.`progres_tp`) AS tp_sudah_dimulai_siswa;

-- Audit snapshot TP Siap dibandingkan dengan catatan penerbitan.
-- Perbedaan jumlah bukan alasan melakukan perbaikan/penghapusan otomatis.
SELECT t.`id` AS tp_id, t.`kode_tp`, t.`status`,
       (SELECT COUNT(*) FROM `sla_sks_v4`.`versi_konten_tp` v
          WHERE v.`tp_id`=t.`id`) AS snapshot_tersimpan,
       (SELECT MAX(v.`nomor_versi`) FROM `sla_sks_v4`.`versi_konten_tp` v
          WHERE v.`tp_id`=t.`id`) AS nomor_versi_terakhir,
       (SELECT COUNT(*) FROM `sla_sks_v4`.`log_aktivitas` l
          WHERE l.`peristiwa`='tp_disiapkan'
            AND l.`catatan` LIKE CONCAT('TP #',t.`id`,' diterbitkan dalam versi %'))
         AS log_penerbitan
FROM `sla_sks_v4`.`tp` t
WHERE t.`status`='siap'
ORDER BY t.`id`;

-- Tidak adanya progres siswa berarti gerbang penguncian belum diuji.
SELECT COUNT(*) AS tp_dengan_progres_siswa
FROM (SELECT DISTINCT `tp_id` FROM `sla_sks_v4`.`progres_tp`) AS digunakan;
