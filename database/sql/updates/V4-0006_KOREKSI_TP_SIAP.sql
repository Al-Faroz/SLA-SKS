-- V4-0006 · Permohonan koreksi TP Siap berversi (02-11-2026)
-- Impor SATU KALI melalui phpMyAdmin pada sla_sks_v4 setelah backup DB dan writable/.
-- Tidak menimpa versi_konten_tp / soal / materi / progres / percobaan historis.
-- Kompatibel MariaDB 10.4 dan MySQL 8; tanpa CodeIgniter Migration/Seeder.
-- Jangan impor dump produksi ke GitHub.
CREATE TABLE IF NOT EXISTS `permohonan_koreksi_tp` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tp_id` BIGINT UNSIGNED NOT NULL,
  `versi_dasar_id` BIGINT UNSIGNED NOT NULL,
  `guru_id` BIGINT UNSIGNED NOT NULL,
  `kategori` ENUM('A','B','C') NOT NULL,
  `alasan` VARCHAR(500) NOT NULL,
  `status` ENUM('draf','diajukan','disetujui','ditolak') NOT NULL DEFAULT 'draf',
  `usulan_json` LONGTEXT NOT NULL,
  `checksum_dasar` CHAR(64) NOT NULL,
  `admin_id` BIGINT UNSIGNED DEFAULT NULL,
  `catatan_keputusan` VARCHAR(500) DEFAULT NULL,
  `versi_terbit_id` BIGINT UNSIGNED DEFAULT NULL,
  `dibuat_pada` DATETIME NOT NULL,
  `diajukan_pada` DATETIME DEFAULT NULL,
  `diputus_pada` DATETIME DEFAULT NULL,
  -- Hanya satu koreksi draf/diajukan boleh aktif untuk satu TP; histori tetap utuh.
  `kunci_terbuka` BIGINT UNSIGNED GENERATED ALWAYS AS
     (CASE WHEN `status` IN ('draf','diajukan') THEN `tp_id` ELSE NULL END) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_koreksi_tp_terbuka` (`kunci_terbuka`),
  KEY `ix_koreksi_tp_status` (`status`,`dibuat_pada`),
  KEY `ix_koreksi_tp_guru` (`guru_id`,`dibuat_pada`),
  KEY `ix_koreksi_tp_dasar` (`versi_dasar_id`),
  CONSTRAINT `fk_koreksi_tp_tp` FOREIGN KEY (`tp_id`) REFERENCES `tp` (`id`),
  CONSTRAINT `fk_koreksi_tp_dasar` FOREIGN KEY (`versi_dasar_id`) REFERENCES `versi_konten_tp` (`id`),
  CONSTRAINT `fk_koreksi_tp_guru` FOREIGN KEY (`guru_id`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_koreksi_tp_admin` FOREIGN KEY (`admin_id`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_koreksi_tp_terbit` FOREIGN KEY (`versi_terbit_id`) REFERENCES `versi_konten_tp` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `schema_version` (`versi`,`diterapkan_pada`)
VALUES ('V4-0006-KOREKSI-TP-SIAP',NOW());

-- Verifikasi setelah impor:
-- SHOW CREATE TABLE permohonan_koreksi_tp;
-- SELECT versi,diterapkan_pada FROM schema_version WHERE versi='V4-0006-KOREKSI-TP-SIAP';
