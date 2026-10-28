-- Fixture sintetis untuk CI MySQL 8: khusus SLA_QA_DB=sla_sks_qa.
-- Tidak untuk XAMPP/produksi, tidak mengandung siswa ataupun data riil.
CREATE TABLE schema_version (versi VARCHAR(40) NOT NULL PRIMARY KEY);
INSERT INTO schema_version VALUES ('V4-0001-R2');

CREATE TABLE ref_angkatan (
 id BIGINT UNSIGNED PRIMARY KEY, kode VARCHAR(30), nama VARCHAR(120), semester_mulai TINYINT UNSIGNED NOT NULL
);
CREATE TABLE ref_mapel (id BIGINT UNSIGNED PRIMARY KEY,kode VARCHAR(24),nama VARCHAR(120));
CREATE TABLE ref_fase (id TINYINT UNSIGNED PRIMARY KEY,kode VARCHAR(12));
CREATE TABLE ref_kurikulum (id BIGINT UNSIGNED PRIMARY KEY,nama VARCHAR(120),versi VARCHAR(30));
CREATE TABLE ref_tahun_ajaran (id BIGINT UNSIGNED PRIMARY KEY,nama VARCHAR(40));
CREATE TABLE ref_kelas (id TINYINT UNSIGNED PRIMARY KEY,nama VARCHAR(10),fase_id TINYINT UNSIGNED);
CREATE TABLE ref_semester (id TINYINT UNSIGNED PRIMARY KEY,kelas_id TINYINT UNSIGNED);
CREATE TABLE penugasan_guru_gen (
 id BIGINT UNSIGNED PRIMARY KEY,guru_id BIGINT UNSIGNED,angkatan_id BIGINT UNSIGNED,mapel_id BIGINT UNSIGNED,
 UNIQUE KEY uq_g1(angkatan_id,mapel_id)
);
CREATE TABLE paket_semester (
 id BIGINT UNSIGNED PRIMARY KEY, angkatan_id BIGINT UNSIGNED,mapel_id BIGINT UNSIGNED,
 semester TINYINT UNSIGNED,tahun_ajaran_id BIGINT UNSIGNED,kurikulum_id BIGINT UNSIGNED,
 nomor_versi INT UNSIGNED NOT NULL DEFAULT 1,status ENUM('draf','aktif','arsip') NOT NULL DEFAULT 'draf',
 UNIQUE KEY uq_pkg(angkatan_id,mapel_id,semester,tahun_ajaran_id)
);
CREATE TABLE cp_mapel (
 id BIGINT UNSIGNED PRIMARY KEY,kurikulum_id BIGINT UNSIGNED,mapel_id BIGINT UNSIGNED,fase_id TINYINT UNSIGNED
);
CREATE TABLE ref_cp_versi (
 id BIGINT UNSIGNED PRIMARY KEY,elemen VARCHAR(150),rumusan TEXT,
 nomor_versi SMALLINT UNSIGNED NOT NULL DEFAULT 1,status ENUM('aktif','draf') NOT NULL
);
CREATE TABLE cp_mapel_elemen (
 id BIGINT UNSIGNED PRIMARY KEY,cp_mapel_id BIGINT UNSIGNED,cp_versi_id BIGINT UNSIGNED,
 UNIQUE KEY uq_elem(cp_mapel_id,cp_versi_id)
);
CREATE TABLE bab (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 paket_semester_id BIGINT UNSIGNED NOT NULL,angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,kurikulum_id BIGINT UNSIGNED NOT NULL,
 tahun_ajaran_id BIGINT UNSIGNED NOT NULL,kelas_id TINYINT UNSIGNED NOT NULL,
 nomor_bab SMALLINT UNSIGNED NOT NULL,nama_bab VARCHAR(35) NOT NULL,
 deskripsi_bab VARCHAR(225) NOT NULL,dibuat_pada DATETIME NOT NULL,
 UNIQUE KEY uq_bab_paket_nomor(paket_semester_id,nomor_bab),
 UNIQUE KEY uq_bab_kelas(angkatan_id,mapel_id,kurikulum_id,tahun_ajaran_id,kelas_id,nomor_bab)
);
CREATE TABLE tp (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 paket_semester_id BIGINT UNSIGNED NOT NULL,bab_id BIGINT UNSIGNED NULL,
 cp_mapel_id BIGINT UNSIGNED NULL,
 jenis_tp ENUM('esensial','non_esensial') NOT NULL DEFAULT 'non_esensial',
 nama_tp VARCHAR(35), deskripsi_tp VARCHAR(225),
 judul VARCHAR(180) NOT NULL,rumusan TEXT NOT NULL,indikator TEXT NOT NULL,
 urutan_atp SMALLINT UNSIGNED NOT NULL,urutan_dalam_bab SMALLINT UNSIGNED,
 kode_tp VARCHAR(40) NOT NULL,
 status ENUM('draf','siap','arsip') NOT NULL DEFAULT 'draf',
 struktur_versi INT UNSIGNED NOT NULL,
 bobot_resume TINYINT UNSIGNED NOT NULL DEFAULT 20,
 bobot_tugas TINYINT UNSIGNED NOT NULL DEFAULT 20,
 bobot_sumatif TINYINT UNSIGNED NOT NULL DEFAULT 60,
 diag_tayang SMALLINT UNSIGNED NOT NULL DEFAULT 10,
 sum_tayang SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 sum_maks_percobaan TINYINT UNSIGNED NOT NULL DEFAULT 2,
 UNIQUE KEY uq_tp_paket_kode(paket_semester_id,kode_tp),
 UNIQUE KEY uq_tp_urut(paket_semester_id,urutan_atp),
 CONSTRAINT ck_fixture_nama_tp CHECK (nama_tp <> 'GAGAL-SENGAJA')
);
CREATE TABLE materi (id BIGINT UNSIGNED PRIMARY KEY,tp_id BIGINT UNSIGNED);
CREATE TABLE versi_konten_tp (id BIGINT UNSIGNED PRIMARY KEY,tp_id BIGINT UNSIGNED);
CREATE TABLE progres_tp (id BIGINT UNSIGNED PRIMARY KEY,tp_id BIGINT UNSIGNED);
CREATE TABLE log_aktivitas (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 pengguna_id BIGINT UNSIGNED NOT NULL,peran_aktif VARCHAR(20),peristiwa VARCHAR(80),
 catatan TEXT,alamat_ip VARCHAR(48),waktu DATETIME
);
INSERT INTO ref_angkatan VALUES (1,'GEN2025','Gen 2025',4),(2,'GEN2026','Gen 2026',4);
INSERT INTO ref_mapel VALUES (5,'SKI','Sejarah Kebudayaan Islam');
INSERT INTO ref_fase VALUES (1,'D');
INSERT INTO ref_kurikulum VALUES (1,'Kurikulum Merdeka','2026');
INSERT INTO ref_tahun_ajaran VALUES (1,'2026/2027');
INSERT INTO ref_kelas VALUES (1,'VII',1),(2,'VIII',1),(3,'IX',1);
INSERT INTO ref_semester VALUES (1,1),(2,1),(3,2),(4,2),(5,3),(6,3);
INSERT INTO penugasan_guru_gen VALUES (1,101,1,5),(2,102,2,5);
INSERT INTO paket_semester VALUES (11,1,5,4,1,1,1,'draf'),(22,2,5,4,1,1,1,'draf');
INSERT INTO cp_mapel VALUES (1,1,5,1);
INSERT INTO ref_cp_versi VALUES (51,'Pemahaman Konsep','Contoh rumusan CP SKI sintetis',1,'aktif');
INSERT INTO cp_mapel_elemen VALUES (1,1,51);
