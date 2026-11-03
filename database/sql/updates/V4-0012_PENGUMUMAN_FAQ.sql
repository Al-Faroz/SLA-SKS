-- SLA-SKS v2.5 / A13-2
-- V4-0012-PENGUMUMAN-FAQ
-- Acuan: dump privat sla_sks_v4 (17), 03-11-2026 16.02,
-- MariaDB 10.4.32, schema_version sampai V4-0011-SERTIFIKAT.
-- BACKUP database + writable/ sebelum impor sekali di sla_sks_v4.
-- Jangan impor ulang bila versi/tabel sudah ada. DDL implicit commit.
-- Tidak ada data siswa, akun dummy, DROP, DELETE, UPDATE tabel lama.
-- Teks disimpan plain text; tampilan memakai escape HTML.

CREATE TABLE `pengumuman` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `status` enum('draf','terbit','arsip') NOT NULL DEFAULT 'draf',
  `dibuat_oleh` bigint(20) UNSIGNED NOT NULL,
  `diubah_oleh` bigint(20) UNSIGNED NOT NULL,
  `versi` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `dibuat_pada` datetime NOT NULL,
  `diubah_pada` datetime NOT NULL,
  `diterbitkan_pada` datetime DEFAULT NULL,
  `diarsipkan_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pengumuman_status_terbit` (`status`,`diterbitkan_pada`,`id`),
  KEY `idx_pengumuman_pembuat` (`dibuat_oleh`,`id`),
  CONSTRAINT `fk_pengumuman_pembuat` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_pengumuman_pengubah` FOREIGN KEY (`diubah_oleh`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `faq` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pertanyaan` varchar(240) NOT NULL,
  `jawaban` text NOT NULL,
  `urutan` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draf','terbit','arsip') NOT NULL DEFAULT 'draf',
  `dibuat_oleh` bigint(20) UNSIGNED NOT NULL,
  `diubah_oleh` bigint(20) UNSIGNED NOT NULL,
  `versi` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `dibuat_pada` datetime NOT NULL,
  `diubah_pada` datetime NOT NULL,
  `diterbitkan_pada` datetime DEFAULT NULL,
  `diarsipkan_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_faq_status_urutan` (`status`,`urutan`,`id`),
  KEY `idx_faq_pembuat` (`dibuat_oleh`,`id`),
  CONSTRAINT `fk_faq_pembuat` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_faq_pengubah` FOREIGN KEY (`diubah_oleh`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `riwayat_informasi` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis` enum('pengumuman','faq') NOT NULL,
  `objek_id` bigint(20) UNSIGNED NOT NULL,
  `aksi` enum('buat','sunting','terbit','arsip') NOT NULL,
  `pengguna_id` bigint(20) UNSIGNED NOT NULL,
  `peran_aktif` enum('admin','guru') NOT NULL,
  `sebelum_json` longtext DEFAULT NULL,
  `sesudah_json` longtext NOT NULL,
  `terjadi_pada` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_riwayat_informasi_objek` (`jenis`,`objek_id`,`id`),
  KEY `idx_riwayat_informasi_aktor` (`pengguna_id`,`id`),
  CONSTRAINT `fk_riwayat_informasi_pengguna` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_version` (`versi`) VALUES ('V4-0012-PENGUMUMAN-FAQ');

-- Verifikasi baca-saja SETELAH impor:
-- SELECT versi, diterapkan_pada FROM schema_version WHERE versi='V4-0012-PENGUMUMAN-FAQ';
-- SELECT COUNT(*) FROM pengumuman;
-- SELECT COUNT(*) FROM faq;
-- SELECT COUNT(*) FROM riwayat_informasi;
-- SHOW CREATE TABLE pengumuman;
-- SHOW CREATE TABLE faq;
-- SHOW CREATE TABLE riwayat_informasi;
