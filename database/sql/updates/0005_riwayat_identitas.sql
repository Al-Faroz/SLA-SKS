-- SLA-SKS v2.3 | K2-0005 | Audit perubahan identitas dan status akun.
-- WAJIB backup database + writable/ sebelum impor. Prasyarat K1-0001 s.d. K2-0004.
-- Impor SATU KALI, jangan impor ulang dump/patch lama.
SET NAMES utf8mb4;

CREATE TABLE riwayat_identitas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pengguna_id BIGINT UNSIGNED NOT NULL,
  admin_id BIGINT UNSIGNED NOT NULL,
  jenis ENUM('pengelola','siswa') NOT NULL,
  aksi ENUM('ubah_identitas','ubah_status') NOT NULL,
  username_sebelum VARCHAR(80) NOT NULL,
  username_sesudah VARCHAR(80) NOT NULL,
  nama_sebelum VARCHAR(150) NOT NULL,
  nama_sesudah VARCHAR(150) NOT NULL,
  peran_sebelum VARCHAR(80) NOT NULL,
  peran_sesudah VARCHAR(80) NOT NULL,
  status_sebelum ENUM('aktif','nonaktif') NOT NULL,
  status_sesudah ENUM('aktif','nonaktif') NOT NULL,
  waktu DATETIME NOT NULL,
  KEY idx_identitas_pengguna_waktu (pengguna_id,waktu),
  KEY idx_identitas_admin_waktu (admin_id,waktu),
  CONSTRAINT fk_identitas_pengguna FOREIGN KEY (pengguna_id)
    REFERENCES pengguna(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_identitas_admin FOREIGN KEY (admin_id)
    REFERENCES pengguna(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO schema_version (versi) VALUES ('K2-0005');
