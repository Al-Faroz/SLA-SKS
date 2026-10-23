-- Fixture QA hanya untuk database CI sementara. BUKAN dump instalasi SLA-SKS.
-- Tidak memuat data siswa, nama nyata, hash sandi, atau kredensial.
CREATE TABLE pengguna (
 id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 nama VARCHAR(150) NOT NULL
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
INSERT INTO pengguna VALUES (1,'Guru Penguji A'),(2,'Guru Penguji B');
INSERT INTO ref_angkatan VALUES (1,'GEN-UJI-A','Gen Uji A'),(2,'GEN-UJI-B','Gen Uji B');
INSERT INTO ref_mapel VALUES (1,'MAP-UJI','Mapel Uji');
INSERT INTO penugasan_guru_gen VALUES (1,1,1,1),(2,2,1,2);
INSERT INTO paket_semester VALUES (1,1,1,4,1,1,1,'draf'),(2,2,1,4,1,1,1,'draf');
INSERT INTO tp VALUES
(1,1,'TP-01','TP untuk Guru A','Rumusan uji','Indikator uji',1,'draf',1),
(2,2,'TP-02','TP untuk Guru B','Rumusan uji','Indikator uji',1,'draf',1);
