-- SLA-SKS v2.3 K3-0008; append-only schema patch after K2-0007.
SET NAMES utf8mb4;
CREATE TABLE ref_kurikulum (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
nama VARCHAR(120) NOT NULL,
versi VARCHAR(50) NOT NULL,
sumber VARCHAR(255) NOT NULL,
dasar_hukum VARCHAR(255) NOT NULL,
dibuat_oleh BIGINT UNSIGNED NOT NULL,
dibuat_pada DATETIME NOT NULL,
UNIQUE KEY uq_kurikulum_nama_versi (nama,versi),
FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE ref_cp (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
kurikulum_id BIGINT UNSIGNED NOT NULL,
mapel_id BIGINT UNSIGNED NOT NULL,
fase_id TINYINT UNSIGNED NOT NULL,
kode VARCHAR(40) NOT NULL,
dibuat_oleh BIGINT UNSIGNED NOT NULL,
dibuat_pada DATETIME NOT NULL,
UNIQUE KEY uq_cp_identitas (kurikulum_id,mapel_id,fase_id,kode),
FOREIGN KEY (kurikulum_id) REFERENCES ref_kurikulum(id) ON DELETE RESTRICT,
FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id) ON DELETE RESTRICT,
FOREIGN KEY (fase_id) REFERENCES ref_fase(id) ON DELETE RESTRICT,
FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE ref_cp_versi (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
cp_id BIGINT UNSIGNED NOT NULL,
nomor_versi SMALLINT UNSIGNED NOT NULL,
elemen VARCHAR(150) NOT NULL,
rumusan TEXT NOT NULL,
otoritas VARCHAR(150) NOT NULL,
nomor_dokumen VARCHAR(120) NOT NULL,
sumber_url VARCHAR(1024) NOT NULL,
tanggal_berlaku DATE NOT NULL,
status ENUM('draf','aktif','diarsipkan') NOT NULL DEFAULT 'draf',
dibuat_oleh BIGINT UNSIGNED NOT NULL,
dibuat_pada DATETIME NOT NULL,
diterbitkan_oleh BIGINT UNSIGNED NULL,
diterbitkan_pada DATETIME NULL,
UNIQUE KEY uq_cp_nomor_versi (cp_id,nomor_versi),
KEY idx_cp_versi_status (cp_id,status),
FOREIGN KEY (cp_id) REFERENCES ref_cp(id) ON DELETE RESTRICT,
FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT,
FOREIGN KEY (diterbitkan_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO schema_version (versi) VALUES ('K3-0008');
