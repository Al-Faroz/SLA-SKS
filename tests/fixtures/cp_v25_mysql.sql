-- QA anonim CP operasional v2.5: DATABASE sla_sks_qa SAJA.
-- Bukan patch database lokal. Dijalankan sekali pada MySQL 8 sementara di GitHub Actions.
CREATE TABLE ref_fase (
 id TINYINT UNSIGNED PRIMARY KEY,kode VARCHAR(10) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE ref_kurikulum (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,nama VARCHAR(120) NOT NULL,
 versi VARCHAR(50) NOT NULL,sumber VARCHAR(255) NOT NULL,dasar_hukum VARCHAR(255) NOT NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,dibuat_pada DATETIME NOT NULL
) ENGINE=InnoDB;
CREATE TABLE ref_mapel (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,kode VARCHAR(24) NOT NULL,nama VARCHAR(120) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE ref_cp (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,kurikulum_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,fase_id TINYINT UNSIGNED NOT NULL,kode VARCHAR(40) NOT NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,dibuat_pada DATETIME NOT NULL,
 UNIQUE KEY uq_cp_identitas(kurikulum_id,mapel_id,fase_id,kode)
) ENGINE=InnoDB;
CREATE TABLE ref_cp_versi (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,cp_id BIGINT UNSIGNED NOT NULL,
 nomor_versi SMALLINT UNSIGNED NOT NULL,elemen VARCHAR(150) NOT NULL,rumusan TEXT NOT NULL,
 otoritas VARCHAR(150) NOT NULL,nomor_dokumen VARCHAR(120) NOT NULL,
 sumber_url VARCHAR(1024) NOT NULL,tanggal_berlaku DATE NOT NULL,
 status ENUM('draf','aktif','diarsipkan') NOT NULL DEFAULT 'draf',
 dibuat_oleh BIGINT UNSIGNED NOT NULL,dibuat_pada DATETIME NOT NULL,
 diterbitkan_oleh BIGINT UNSIGNED NULL,diterbitkan_pada DATETIME NULL,
 UNIQUE KEY uq_cp_nomor_versi(cp_id,nomor_versi)
) ENGINE=InnoDB;
CREATE TABLE cp_mapel (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,kurikulum_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,fase_id TINYINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 UNIQUE KEY uq_cp_satu_mapel(kurikulum_id,mapel_id,fase_id)
) ENGINE=InnoDB;
CREATE TABLE cp_mapel_elemen (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,cp_mapel_id BIGINT UNSIGNED NOT NULL,
 cp_versi_id BIGINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_cpmapel_elemen(cp_mapel_id,cp_versi_id)
) ENGINE=InnoDB;
CREATE TABLE log_aktivitas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,pengguna_id BIGINT UNSIGNED NOT NULL,
 peran_aktif VARCHAR(30),peristiwa VARCHAR(80),catatan TEXT,waktu DATETIME
) ENGINE=InnoDB;
INSERT INTO ref_fase VALUES(1,'D');
INSERT INTO ref_kurikulum VALUES(1,'Kurikulum Merdeka','2026','Kementerian','Peraturan referensi uji',101,'2026-01-01 00:00:00');
INSERT INTO ref_mapel VALUES(1,'SKI','Sejarah Kebudayaan Islam'),(2,'MAT','Matematika');
