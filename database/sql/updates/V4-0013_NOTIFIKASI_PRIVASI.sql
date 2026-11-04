-- SLA-SKS v2.5 / A13-6 dan A13-7
-- V4-0013-NOTIFIKASI-PRIVASI
-- Acuan: dump privat sla_sks_v4 (18), 04-11-2026 03.59,
-- MariaDB 10.4.32, 71 tabel, schema_version terakhir V4-0012-PENGUMUMAN-FAQ.
-- Backup DB + writable terlebih dahulu; impor satu kali di database sla_sks_v4.
-- DDL implicit commit. JANGAN impor ulang setelah penanda versi terpasang.
-- Additive saja: tidak mengubah, menghapus atau mereset data siswa/akademik.

CREATE TABLE `notifikasi` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `penerima_id` bigint(20) UNSIGNED NOT NULL,
  `peran_penerima` enum('admin','guru','siswa','pimpinan') NOT NULL,
  `jenis` varchar(60) NOT NULL,
  `sumber_id` bigint(20) UNSIGNED NOT NULL,
  `versi_sumber` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `judul` varchar(150) NOT NULL,
  `pesan` varchar(400) NOT NULL,
  `tujuan` varchar(160) NOT NULL,
  `kunci_idempoten` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dibuat_pada` datetime NOT NULL,
  `dibaca_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifikasi_idempoten` (`kunci_idempoten`),
  KEY `idx_notifikasi_penerima` (`penerima_id`,`peran_penerima`,`dibuat_pada`,`id`),
  KEY `idx_notifikasi_unread` (`penerima_id`,`peran_penerima`,`dibaca_pada`),
  KEY `idx_notifikasi_sumber` (`jenis`,`sumber_id`),
  CONSTRAINT `fk_notifikasi_penerima` FOREIGN KEY (`penerima_id`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `kebijakan_privasi` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `versi` varchar(40) NOT NULL,
  `tanggal_berlaku` date NOT NULL,
  `teks` mediumtext NOT NULL,
  `checksum_sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `status` enum('draf','terbit') NOT NULL DEFAULT 'draf',
  `revisi_draf` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `dibuat_oleh` bigint(20) UNSIGNED NOT NULL,
  `diubah_oleh` bigint(20) UNSIGNED NOT NULL,
  `diterbitkan_oleh` bigint(20) UNSIGNED DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL,
  `diubah_pada` datetime NOT NULL,
  `diterbitkan_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kebijakan_versi` (`versi`),
  KEY `idx_kebijakan_berlaku` (`status`,`tanggal_berlaku`,`diterbitkan_pada`,`id`),
  CONSTRAINT `fk_kebijakan_pembuat` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_kebijakan_pengubah` FOREIGN KEY (`diubah_oleh`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_kebijakan_penerbit` FOREIGN KEY (`diterbitkan_oleh`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `riwayat_kebijakan_privasi` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `kebijakan_privasi_id` bigint(20) UNSIGNED NOT NULL,
  `aksi` enum('buat_draf','sunting_draf','terbit') NOT NULL,
  `admin_id` bigint(20) UNSIGNED NOT NULL,
  `revisi_lama` int(10) UNSIGNED DEFAULT NULL,
  `revisi_baru` int(10) UNSIGNED NOT NULL,
  `checksum_lama` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `checksum_baru` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `terjadi_pada` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_riwayat_kebijakan` (`kebijakan_privasi_id`,`id`),
  KEY `idx_riwayat_kebijakan_admin` (`admin_id`,`id`),
  CONSTRAINT `fk_rkp_kebijakan` FOREIGN KEY (`kebijakan_privasi_id`) REFERENCES `kebijakan_privasi` (`id`),
  CONSTRAINT `fk_rkp_admin` FOREIGN KEY (`admin_id`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_version` (`versi`) VALUES ('V4-0013-NOTIFIKASI-PRIVASI');

-- Verifikasi sesudah impor (baca saja):
-- SELECT versi, diterapkan_pada FROM schema_version
--   WHERE versi='V4-0013-NOTIFIKASI-PRIVASI';
-- SHOW CREATE TABLE notifikasi;
-- SHOW CREATE TABLE kebijakan_privasi;
-- SHOW CREATE TABLE riwayat_kebijakan_privasi;
-- SELECT COUNT(*) FROM notifikasi;
-- SELECT COUNT(*) FROM kebijakan_privasi;
-- SELECT COUNT(*) FROM riwayat_kebijakan_privasi;
