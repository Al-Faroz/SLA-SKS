-- SLA-SKS v2.3 | K2-0006 | Pratinjau dan konfirmasi impor akun XLSX.
-- BACKUP database + writable/ sebelum impor. Hanya SATU KALI setelah K2-0005.
-- Metadata dan hash sandi sementara disimpan PRIVAT dalam MySQL/MariaDB.
-- Tidak menghapus/mengubah pengguna, siswa, penugasan dan histori yang ada.
SET NAMES utf8mb4;

CREATE TABLE impor_akun (
  id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
  admin_id BIGINT UNSIGNED NOT NULL,
  jenis ENUM('pengelola','siswa') NOT NULL,
  versi_template VARCHAR(24) NOT NULL,
  checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  ukuran_file INT UNSIGNED NOT NULL,
  status ENUM('pratinjau','diterapkan','kedaluwarsa') NOT NULL DEFAULT 'pratinjau',
  jumlah_baru SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_ubah SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_tetap SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_tolak SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  dibuat_pada DATETIME NOT NULL,
  kedaluwarsa_pada DATETIME NOT NULL,
  diterapkan_pada DATETIME NULL,
  KEY idx_impor_akun_admin_waktu (admin_id, dibuat_pada),
  KEY idx_impor_akun_status (status, kedaluwarsa_pada),
  CONSTRAINT fk_impor_akun_admin FOREIGN KEY (admin_id)
    REFERENCES pengguna(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE impor_akun_baris (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  impor_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  nomor_baris SMALLINT UNSIGNED NOT NULL,
  tindakan ENUM('baru','ubah','tetap','ditolak') NOT NULL,
  username VARCHAR(80) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  nama_sebelum VARCHAR(150) NULL,
  peran VARCHAR(40) NOT NULL,
  peran_sebelum VARCHAR(40) NULL,
  angkatan_id BIGINT UNSIGNED NULL,
  semester_mulai TINYINT UNSIGNED NULL,
  sandi_hash VARCHAR(255) NULL,
  target_id BIGINT UNSIGNED NULL,
  target_versi INT UNSIGNED NULL,
  galat VARCHAR(255) NULL,
  UNIQUE KEY uq_impor_baris_nomor (impor_id, nomor_baris),
  KEY idx_impor_baris_tindakan (impor_id, tindakan),
  CONSTRAINT fk_impor_baris_impor FOREIGN KEY (impor_id)
    REFERENCES impor_akun(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_impor_baris_gen FOREIGN KEY (angkatan_id)
    REFERENCES ref_angkatan(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_impor_baris_target FOREIGN KEY (target_id)
    REFERENCES pengguna(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Penanda versi harus TERAKHIR. Jika impor gagal, periksa DDL dan pulihkan backup.
INSERT INTO schema_version (versi) VALUES ('K2-0006');
