-- SLA-SKS v2.2 | K2-0002 | Data Induk Akademik dan Identitas Madrasah
-- Patch K1 ke K2. Impor SATU KALI setelah backup, tidak ada DROP/TRUNCATE/DELETE.
SET NAMES utf8mb4;

CREATE TABLE ref_jenjang (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 kode VARCHAR(12) NOT NULL UNIQUE,
 nama VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_fase (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 kode VARCHAR(8) NOT NULL UNIQUE,
 nama VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_kelas (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 jenjang_id TINYINT UNSIGNED NOT NULL,
 fase_id TINYINT UNSIGNED NOT NULL,
 nama VARCHAR(10) NOT NULL,
 urutan TINYINT UNSIGNED NOT NULL,
 UNIQUE KEY uq_kelas_urutan (jenjang_id, urutan),
 FOREIGN KEY (jenjang_id) REFERENCES ref_jenjang(id) ON DELETE RESTRICT,
 FOREIGN KEY (fase_id) REFERENCES ref_fase(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_semester (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 kelas_id TINYINT UNSIGNED NOT NULL,
 semester_akademik ENUM('ganjil','genap') NOT NULL,
 UNIQUE KEY uq_semester_kelas (kelas_id, semester_akademik),
 FOREIGN KEY (kelas_id) REFERENCES ref_kelas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_angkatan (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 kode VARCHAR(20) NOT NULL UNIQUE,
 nama VARCHAR(100) NOT NULL,
 semester_mulai TINYINT UNSIGNED NOT NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 FOREIGN KEY (semester_mulai) REFERENCES ref_semester(id) ON DELETE RESTRICT,
 FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_tahun_ajaran (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 nama VARCHAR(20) NOT NULL UNIQUE,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE ref_mapel (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 kode VARCHAR(24) NOT NULL UNIQUE,
 nama VARCHAR(140) NOT NULL,
 guru_id BIGINT UNSIGNED NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 KEY idx_mapel_guru (guru_id),
 FOREIGN KEY (guru_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE riwayat_guru_mapel (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 mapel_id BIGINT UNSIGNED NOT NULL,
 guru_sebelum_id BIGINT UNSIGNED NULL,
 guru_sesudah_id BIGINT UNSIGNED NULL,
 admin_id BIGINT UNSIGNED NOT NULL,
 alasan VARCHAR(255) NOT NULL,
 waktu DATETIME NOT NULL,
 KEY idx_riwayat_mapel_waktu (mapel_id, waktu),
 FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id) ON DELETE RESTRICT,
 FOREIGN KEY (guru_sebelum_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 FOREIGN KEY (guru_sesudah_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 FOREIGN KEY (admin_id) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pengaturan (
 kunci VARCHAR(80) NOT NULL PRIMARY KEY,
 nilai TEXT NULL,
 diperbarui_oleh BIGINT UNSIGNED NULL,
 diperbarui_pada DATETIME NULL,
 FOREIGN KEY (diperbarui_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE riwayat_identitas_madrasah (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 admin_id BIGINT UNSIGNED NOT NULL,
 data_sebelum LONGTEXT NOT NULL,
 data_sesudah LONGTEXT NOT NULL,
 waktu DATETIME NOT NULL,
 KEY idx_riwayat_identitas_waktu (waktu),
 FOREIGN KEY (admin_id) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO ref_jenjang (id,kode,nama) VALUES (1,'MTs','Madrasah Tsanawiyah');
INSERT INTO ref_fase (id,kode,nama) VALUES (1,'D','Fase D');
INSERT INTO ref_kelas (id,jenjang_id,fase_id,nama,urutan) VALUES
 (7,1,1,'VII',1),(8,1,1,'VIII',2),(9,1,1,'IX',3);
INSERT INTO ref_semester (id,kelas_id,semester_akademik) VALUES
 (1,7,'ganjil'),(2,7,'genap'),(3,8,'ganjil'),(4,8,'genap'),(5,9,'ganjil'),(6,9,'genap');

INSERT INTO pengaturan (kunci, nilai) VALUES
 ('nama_madrasah',NULL),('alamat_madrasah',NULL),('npsn',NULL),
 ('nsm',NULL),('telepon',NULL),('email',NULL),('website',NULL),
 ('kota_penerbitan',NULL),('kepala_madrasah_nama',NULL),('kepala_madrasah_nip',NULL);

-- Penanda versi terakhir agar impor gagal tidak salah tercatat sebagai selesai.
INSERT INTO schema_version (versi) VALUES ('K2-0002');
