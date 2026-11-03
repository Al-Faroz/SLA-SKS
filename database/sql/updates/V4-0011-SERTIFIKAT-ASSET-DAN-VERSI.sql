-- SLA-SKS v2.5 | V4-0011-SERTIFIKAT | 3 November 2026
-- PR A11-2. PATCH ADDITIVE. Jangan impor jika marker V4-0011-SERTIFIKAT sudah ada.
-- Sumber audit skema: dump privat sla_sks_v4 (15).sql, 03 Nov 2026 09.32,
-- MariaDB 10.4.32, skema terakhir V4-0010-BANK-WORD-PRA.
-- Backup database DAN writable/ dahulu, jangan impor ulang dump instalasi.
-- Tidak mengubah siswa/nilai/percobaan yang sudah tersimpan.
-- Data siswa dan path privat tidak pernah diletakkan dalam file SQL ini.
-- Jika DDL gagal sebagian: jangan jalankan ulang; periksa tabel dan pulihkan backup.
--
-- Preflight baca-saja (jalankan melalui tab SQL SEBELUM impor):
-- SELECT versi FROM schema_version WHERE versi IN
-- ('V4-0010-BANK-WORD-PRA','V4-0011-SERTIFIKAT') ORDER BY versi;
-- SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
-- AND TABLE_NAME IN ('sertifikat_aset','sertifikat_aset_aktif',
-- 'sertifikat','sertifikat_aktif') ORDER BY TABLE_NAME;
--
-- Tabel aset immutable. TTD/background selalu berada di writable/private/sertifikat/.
-- "Aktif" disimpan sebagai pointer 1 per jenis agar aset historis tak diganti.
CREATE TABLE `sertifikat_aset` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis` enum('background','ttd') NOT NULL,
  `nama_acak` char(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `mime` enum('image/png','image/jpeg') NOT NULL,
  `lebar` smallint(5) UNSIGNED NOT NULL,
  `tinggi` smallint(5) UNSIGNED NOT NULL,
  `ukuran_byte` int(10) UNSIGNED NOT NULL,
  `sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dibuat_oleh` bigint(20) UNSIGNED NOT NULL,
  `dibuat_pada` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sertifikat_aset_nama` (`nama_acak`),
  KEY `ix_sertifikat_aset_jenis_waktu` (`jenis`,`dibuat_pada`),
  KEY `ix_sertifikat_aset_admin` (`dibuat_oleh`),
  CONSTRAINT `fk_sa_admin` FOREIGN KEY (`dibuat_oleh`)
    REFERENCES `pengguna`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sertifikat_aset_aktif` (
  `jenis` enum('background','ttd') NOT NULL,
  `aset_id` bigint(20) UNSIGNED NOT NULL,
  `diaktifkan_oleh` bigint(20) UNSIGNED NOT NULL,
  `diaktifkan_pada` datetime NOT NULL,
  PRIMARY KEY (`jenis`),
  KEY `ix_saa_aset` (`aset_id`),
  KEY `ix_saa_admin` (`diaktifkan_oleh`),
  CONSTRAINT `fk_saa_aset` FOREIGN KEY (`aset_id`)
    REFERENCES `sertifikat_aset`(`id`),
  CONSTRAINT `fk_saa_admin` FOREIGN KEY (`diaktifkan_oleh`)
    REFERENCES `pengguna`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Setiap versi bersifat historis, PDF dan snapshot immutable;
-- status lama 'digantikan' tidak dihapus sehingga QR lama masih dapat diverifikasi.
CREATE TABLE `sertifikat` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `siswa_id` bigint(20) UNSIGNED NOT NULL,
  `paket_semester_id` bigint(20) UNSIGNED NOT NULL,
  `versi` smallint(5) UNSIGNED NOT NULL,
  `nomor` varchar(65) NOT NULL,
  `kode_verifikasi` char(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `status` enum('berlaku','digantikan') NOT NULL DEFAULT 'berlaku',
  `menggantikan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `background_aset_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ttd_aset_id` bigint(20) UNSIGNED NOT NULL,
  `snapshot_json` longtext NOT NULL,
  `konten_sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `snapshot_sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `pdf_nama_acak` char(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `pdf_sha256` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `alasan_revisi` varchar(225) DEFAULT NULL,
  `diterbitkan_oleh` bigint(20) UNSIGNED NOT NULL,
  `diterbitkan_pada` datetime NOT NULL,
  `digantikan_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sertifikat_siswa_paket_versi` (`siswa_id`,`paket_semester_id`,`versi`),
  UNIQUE KEY `uq_sertifikat_nomor` (`nomor`),
  UNIQUE KEY `uq_sertifikat_token` (`kode_verifikasi`),
  UNIQUE KEY `uq_sertifikat_pdf_acak` (`pdf_nama_acak`),
  KEY `ix_sertifikat_paket_status` (`paket_semester_id`,`status`),
  KEY `ix_sertifikat_aset_background` (`background_aset_id`),
  KEY `ix_sertifikat_aset_ttd` (`ttd_aset_id`),
  KEY `ix_sertifikat_menggantikan` (`menggantikan_id`),
  KEY `ix_sertifikat_admin` (`diterbitkan_oleh`),
  CONSTRAINT `fk_sertifikat_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`),
  CONSTRAINT `fk_sertifikat_paket` FOREIGN KEY (`paket_semester_id`) REFERENCES `paket_semester`(`id`),
  CONSTRAINT `fk_sertifikat_background` FOREIGN KEY (`background_aset_id`) REFERENCES `sertifikat_aset`(`id`),
  CONSTRAINT `fk_sertifikat_ttd` FOREIGN KEY (`ttd_aset_id`) REFERENCES `sertifikat_aset`(`id`),
  CONSTRAINT `fk_sertifikat_asal` FOREIGN KEY (`menggantikan_id`) REFERENCES `sertifikat`(`id`),
  CONSTRAINT `fk_sertifikat_penerbit` FOREIGN KEY (`diterbitkan_oleh`) REFERENCES `pengguna`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Satu pointer aktif per siswa×paket. Lock baris siswa ketika menerbitkan,
-- transaksi mengganti pointer sekaligus status historis saat revisi.
CREATE TABLE `sertifikat_aktif` (
  `siswa_id` bigint(20) UNSIGNED NOT NULL,
  `paket_semester_id` bigint(20) UNSIGNED NOT NULL,
  `sertifikat_id` bigint(20) UNSIGNED NOT NULL,
  `diubah_pada` datetime NOT NULL,
  PRIMARY KEY (`siswa_id`,`paket_semester_id`),
  UNIQUE KEY `uq_sertifikat_aktif_id` (`sertifikat_id`),
  KEY `ix_sertifikat_aktif_paket` (`paket_semester_id`),
  CONSTRAINT `fk_sertifikat_aktif_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa`(`id`),
  CONSTRAINT `fk_sertifikat_aktif_paket` FOREIGN KEY (`paket_semester_id`) REFERENCES `paket_semester`(`id`),
  CONSTRAINT `fk_sertifikat_aktif_sertifikat` FOREIGN KEY (`sertifikat_id`) REFERENCES `sertifikat`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_version` (`versi`,`diterapkan_pada`)
VALUES ('V4-0011-SERTIFIKAT',UTC_TIMESTAMP());

-- Postflight baca-saja:
-- SELECT versi FROM schema_version WHERE versi='V4-0011-SERTIFIKAT';
-- SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
-- AND TABLE_NAME IN ('sertifikat_aset','sertifikat_aset_aktif',
-- 'sertifikat','sertifikat_aktif') ORDER BY TABLE_NAME;
-- SELECT COUNT(*) FROM sertifikat;         -- wajib 0 saat patch baru
-- SELECT COUNT(*) FROM sertifikat_aset;    -- wajib 0 saat patch baru
-- Tidak ada INSERT data siswa atau sertifikat dummy.
