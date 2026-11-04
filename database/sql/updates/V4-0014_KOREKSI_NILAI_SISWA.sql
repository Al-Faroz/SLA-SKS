-- SLA-SKS v2.5 / A14-2 Koreksi Nilai Akademik Resume/HOTS
-- V4-0014-KOREKSI-NILAI-SISWA
-- Audit dasar: dump PRIVAT sla_sks_v4 (19).sql, 04 Nov 2026 09.28
-- MariaDB 10.4.32; 71 tabel; V4-0012-PENGUMUMAN-FAQ masih marker terakhir.
-- SEBELUM IMPOR: backup DB + writable; PASANG V4-0013 terlebih dahulu.
-- Jangan impor jika tabel sudah ada/marker sudah tercatat.
-- Patch additive, tidak menyentuh data hasil/nilai/sertifikat yang ada.

CREATE TABLE `permohonan_koreksi_nilai` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `jenis` enum('resume','hots') NOT NULL,
  `sumber_id` bigint(20) UNSIGNED NOT NULL,
  `progres_tp_id` bigint(20) UNSIGNED NOT NULL,
  `versi_konten_tp_id` bigint(20) UNSIGNED NOT NULL,
  `versi_koreksi` int(10) UNSIGNED NOT NULL,
  `nilai_awal` tinyint(3) UNSIGNED NOT NULL,
  `nilai_usulan` tinyint(3) UNSIGNED NOT NULL,
  `nilai_tp_awal` decimal(5,2) DEFAULT NULL,
  `nilai_tp_usulan` decimal(5,2) DEFAULT NULL,
  `status_nilai_awal` enum('sementara','final') NOT NULL,
  `alasan` varchar(500) NOT NULL,
  `guru_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('diajukan','disetujui','ditolak') NOT NULL DEFAULT 'diajukan',
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `catatan_keputusan` varchar(500) DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL,
  `diputus_pada` datetime DEFAULT NULL,
  -- NULL untuk permohonan selesai; hanya 1 pengajuan terbuka per sumber.
  `kunci_terbuka` varchar(50) GENERATED ALWAYS AS (
    CASE WHEN `status`='diajukan' THEN CONCAT(`jenis`,':',`sumber_id`) ELSE NULL END
  ) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_koreksi_nilai_terbuka` (`kunci_terbuka`),
  UNIQUE KEY `uq_koreksi_nilai_versi` (`jenis`,`sumber_id`,`versi_koreksi`),
  KEY `ix_koreksi_nilai_admin` (`status`,`dibuat_pada`,`id`),
  KEY `ix_koreksi_nilai_guru` (`guru_id`,`dibuat_pada`),
  KEY `ix_koreksi_nilai_progres` (`progres_tp_id`),
  KEY `fk_koreksi_nilai_admin` (`admin_id`),
  KEY `fk_koreksi_nilai_versi_konten` (`versi_konten_tp_id`),
  CONSTRAINT `fk_koreksi_nilai_progres` FOREIGN KEY (`progres_tp_id`) REFERENCES `progres_tp` (`id`),
  CONSTRAINT `fk_koreksi_nilai_versi_konten` FOREIGN KEY (`versi_konten_tp_id`) REFERENCES `versi_konten_tp` (`id`),
  CONSTRAINT `fk_koreksi_nilai_guru` FOREIGN KEY (`guru_id`) REFERENCES `pengguna` (`id`),
  CONSTRAINT `fk_koreksi_nilai_admin` FOREIGN KEY (`admin_id`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `riwayat_koreksi_nilai` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `permohonan_id` bigint(20) UNSIGNED NOT NULL,
  `aksi` enum('diajukan','disetujui','ditolak') NOT NULL,
  `pelaku_id` bigint(20) UNSIGNED NOT NULL,
  `peran_aktif` enum('guru','admin') NOT NULL,
  `nilai_awal` tinyint(3) UNSIGNED NOT NULL,
  `nilai_usulan` tinyint(3) UNSIGNED NOT NULL,
  `nilai_tp_awal` decimal(5,2) DEFAULT NULL,
  `nilai_tp_usulan` decimal(5,2) DEFAULT NULL,
  `versi_koreksi` int(10) UNSIGNED NOT NULL,
  `waktu` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_riwayat_koreksi_aksi` (`permohonan_id`,`aksi`),
  KEY `ix_riwayat_koreksi_waktu` (`waktu`,`id`),
  KEY `fk_riwayat_koreksi_pelaku` (`pelaku_id`),
  CONSTRAINT `fk_riwayat_koreksi_permohonan` FOREIGN KEY (`permohonan_id`)
    REFERENCES `permohonan_koreksi_nilai` (`id`),
  CONSTRAINT `fk_riwayat_koreksi_pelaku` FOREIGN KEY (`pelaku_id`) REFERENCES `pengguna` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schema_version` (`versi`) VALUES ('V4-0014-KOREKSI-NILAI-SISWA');

-- Baca saja setelah impor:
-- SELECT versi,diterapkan_pada FROM schema_version
-- WHERE versi IN ('V4-0013-NOTIFIKASI-PRIVASI','V4-0014-KOREKSI-NILAI-SISWA');
-- SHOW CREATE TABLE permohonan_koreksi_nilai;
-- SHOW CREATE TABLE riwayat_koreksi_nilai;
-- SELECT COUNT(*) FROM permohonan_koreksi_nilai;
-- SELECT COUNT(*) FROM riwayat_koreksi_nilai;
