-- Fixture ANONIM untuk QA patch CP SKI; BUKAN dump lokal pengguna.
CREATE TABLE schema_version(versi VARCHAR(40) NOT NULL PRIMARY KEY,diterapkan_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT INTO schema_version (versi) VALUES ('V4-0001-R2');
CREATE TABLE ref_mapel(id BIGINT UNSIGNED NOT NULL PRIMARY KEY,kode VARCHAR(24),nama VARCHAR(140));
INSERT INTO ref_mapel VALUES(4,'SKI','Sejarah Kebudayaan Islam');
CREATE TABLE ref_kurikulum(id BIGINT UNSIGNED NOT NULL PRIMARY KEY,nama VARCHAR(120),versi VARCHAR(50),sumber VARCHAR(255),dasar_hukum VARCHAR(255));
INSERT INTO ref_kurikulum VALUES(1,'Kurikulum Berbasis Cinta (KBC)','Kurikulum Madrasah 2025 (KMA 1503/2025)','Kementerian Agama RI','KMA Nomor 1503 Tahun 2025');
CREATE TABLE ref_fase(id TINYINT UNSIGNED NOT NULL PRIMARY KEY,kode VARCHAR(8),nama VARCHAR(60));
INSERT INTO ref_fase VALUES(1,'D','Fase D');
CREATE TABLE ref_cp(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kurikulum_id BIGINT UNSIGNED NOT NULL,mapel_id BIGINT UNSIGNED NOT NULL,
  fase_id TINYINT UNSIGNED NOT NULL,kode VARCHAR(40) NOT NULL,
  dibuat_oleh BIGINT UNSIGNED NOT NULL,dibuat_pada DATETIME NOT NULL,
  UNIQUE KEY uq_cp_identitas(kurikulum_id,mapel_id,fase_id,kode)
);
CREATE TABLE ref_cp_versi(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cp_id BIGINT UNSIGNED NOT NULL,nomor_versi SMALLINT UNSIGNED NOT NULL,
  elemen VARCHAR(150) NOT NULL,rumusan TEXT NOT NULL,
  otoritas VARCHAR(150) NOT NULL,nomor_dokumen VARCHAR(120) NOT NULL,
  sumber_url VARCHAR(1024) NOT NULL,tanggal_berlaku DATE NOT NULL,
  status ENUM('draf','aktif','diarsipkan') NOT NULL DEFAULT 'draf',
  dibuat_oleh BIGINT UNSIGNED NOT NULL,dibuat_pada DATETIME NOT NULL,
  diterbitkan_oleh BIGINT UNSIGNED NULL,diterbitkan_pada DATETIME NULL,
  UNIQUE KEY uq_cp_nomor_versi(cp_id,nomor_versi)
);
CREATE TABLE cp_mapel(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kurikulum_id BIGINT UNSIGNED NOT NULL,mapel_id BIGINT UNSIGNED NOT NULL,
  fase_id TINYINT UNSIGNED NOT NULL,dibuat_pada DATETIME NOT NULL,
  UNIQUE KEY uq_cp_satu_mapel(kurikulum_id,mapel_id,fase_id)
);
CREATE TABLE cp_mapel_elemen(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cp_mapel_id BIGINT UNSIGNED NOT NULL,cp_versi_id BIGINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_cpmapel_elemen(cp_mapel_id,cp_versi_id)
);
CREATE TABLE log_aktivitas(
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,pengguna_id BIGINT UNSIGNED,
  peran_aktif ENUM('admin','guru','siswa','pimpinan'),peristiwa VARCHAR(60) NOT NULL,
  catatan VARCHAR(255),waktu DATETIME NOT NULL
);
CREATE TABLE tp_cp(id BIGINT UNSIGNED PRIMARY KEY,tp_id BIGINT UNSIGNED NOT NULL,
  cp_versi_id BIGINT UNSIGNED NOT NULL,utama TINYINT UNSIGNED NOT NULL);
INSERT INTO ref_cp VALUES
  (2,1,4,1,'FD-SKI-01',1,'2026-10-21 08:32:57'),
  (3,1,4,1,'FD-SKI-02',1,'2026-10-21 08:33:45');
INSERT INTO ref_cp_versi VALUES
  (2,2,1,'Pemahaman Konsep','Rumusan PK Lama','Dirjen Pendidikan Islam',
  'SK Dirjen Pendis 9941 Tahun 2025','https://example.edu/ref-resmi',
  '2026-01-01','aktif',1,'2026-10-21 08:32:57',1,'2026-10-21 08:33:03'),
  (3,3,1,'Keterampilan Proses','Rumusan Keterampilan Proses',
  'Dirjen Pendidikan Islam','SK Dirjen Pendis 9941 Tahun 2025',
  'https://example.edu/ref-resmi','2026-01-01','aktif',1,
  '2026-10-21 08:33:45',1,'2026-10-21 08:33:48');
INSERT INTO cp_mapel VALUES(1,1,4,1,'2026-10-26 09:41:42');
INSERT INTO cp_mapel_elemen VALUES(1,1,2),(2,1,3);
INSERT INTO tp_cp VALUES(1,1,2,1);
