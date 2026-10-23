-- Fixture QA hanya untuk database CI sementara. BUKAN dump instalasi SLA-SKS.
-- Tidak memuat data siswa, nama nyata, hash sandi, atau kredensial.
CREATE TABLE pengguna (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 nama VARCHAR(150) NOT NULL,
 status VARCHAR(12) NOT NULL DEFAULT 'aktif',
 wajib_ganti_sandi TINYINT NOT NULL DEFAULT 0,
 versi_sesi INT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
CREATE TABLE pengguna_peran (
 pengguna_id BIGINT UNSIGNED NOT NULL,
 peran VARCHAR(20) NOT NULL,
 PRIMARY KEY (pengguna_id, peran),
 FOREIGN KEY (pengguna_id) REFERENCES pengguna(id)
) ENGINE=InnoDB;
CREATE TABLE ref_angkatan (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 kode VARCHAR(20) NOT NULL,
 nama VARCHAR(100) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE ref_mapel (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 kode VARCHAR(24) NOT NULL,
 nama VARCHAR(140) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE penugasan_guru_gen (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 guru_id BIGINT UNSIGNED NULL,
 UNIQUE KEY uq_gen_mapel (angkatan_id,mapel_id),
 FOREIGN KEY (angkatan_id) REFERENCES ref_angkatan(id),
 FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id),
 FOREIGN KEY (guru_id) REFERENCES pengguna(id)
) ENGINE=InnoDB;
CREATE TABLE paket_semester (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 semester TINYINT UNSIGNED NOT NULL,
 tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
 kurikulum_id BIGINT UNSIGNED NOT NULL,
 nomor_versi SMALLINT UNSIGNED NOT NULL,
 status ENUM('draf','aktif','arsip') NOT NULL
) ENGINE=InnoDB;
CREATE TABLE tp (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 paket_semester_id BIGINT UNSIGNED NOT NULL,
 kode_tp VARCHAR(40) NOT NULL,
 judul VARCHAR(180) NOT NULL,
 rumusan TEXT NOT NULL,
 indikator TEXT NOT NULL,
 urutan_atp SMALLINT UNSIGNED NOT NULL,
 status ENUM('draf','siap') NOT NULL,
 struktur_versi INT UNSIGNED NOT NULL,
 FOREIGN KEY (paket_semester_id) REFERENCES paket_semester(id)
) ENGINE=InnoDB;
CREATE TABLE materi (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 tp_id BIGINT UNSIGNED NOT NULL,
 row_uuid CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 urutan SMALLINT UNSIGNED NOT NULL,
 judul VARCHAR(180) NOT NULL,
 jenis ENUM('pdf','audio','gambar','youtube') NOT NULL,
 petunjuk_resume TEXT NOT NULL,
 petunjuk_hots TEXT NULL,
 min_waktu_detik SMALLINT UNSIGNED NOT NULL,
 url_youtube VARCHAR(255) NULL,
 berkas_id BIGINT UNSIGNED NULL,
 aktif TINYINT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY uq_materi_row_uuid (row_uuid),
 FOREIGN KEY (tp_id) REFERENCES tp(id)
) ENGINE=InnoDB;

INSERT INTO pengguna (id,nama,status,wajib_ganti_sandi,versi_sesi) VALUES
(1,'Guru Penguji A','aktif',0,1),(2,'Guru Penguji B','aktif',0,1),(3,'Admin Penguji','aktif',0,1),(4,'Guru Wajib Ganti Sandi','aktif',1,1),
(5,'Guru Tanpa G1','aktif',0,1),(6,'Guru Nonaktif','nonaktif',0,1);
INSERT INTO pengguna_peran (pengguna_id,peran) VALUES (1,'guru'),(2,'guru'),(3,'admin'),(4,'guru'),(5,'guru'),(6,'guru');
INSERT INTO ref_angkatan VALUES (1,'GEN-UJI-A','Gen Uji A'),(2,'GEN-UJI-B','Gen Uji B');
INSERT INTO ref_mapel VALUES (1,'MAP-UJI','Mapel Uji');
INSERT INTO penugasan_guru_gen VALUES (1,1,1,1),(2,2,1,2);
INSERT INTO paket_semester VALUES (1,1,1,4,1,1,1,'draf'),(2,2,1,4,1,1,1,'draf');
INSERT INTO tp VALUES
(1,1,'TP-01','TP untuk Guru A','Rumusan uji','Indikator uji',1,'draf',1),
(2,2,'TP-02','TP untuk Guru B','Rumusan uji','Indikator uji',1,'draf',1);

INSERT INTO materi (id,tp_id,row_uuid,urutan,judul,jenis,petunjuk_resume,petunjuk_hots,min_waktu_detik,url_youtube,berkas_id,aktif) VALUES
(1,1,'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',1,'Materi Uji A','pdf','Resume Uji A','HOTS Uji A',0,NULL,NULL,1),
(2,2,'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',1,'Materi Uji B','youtube','Resume Uji B','HOTS Uji B',0,'https://youtu.be/abcdefghijk',NULL,1);
