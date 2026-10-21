-- SLA-SKS v2.3 | K2-0007 | Profil Pengelola dan foto privat.
-- Backup database + writable/ dahulu. Setelah K2-0006 dan hanya SATU KALI.
-- Tidak mengubah/menghapus akun, siswa, penugasan maupun histori.
SET NAMES utf8mb4;

CREATE TABLE foto_profil (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pengguna_id BIGINT UNSIGNED NOT NULL,
  nama_berkas VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  mime ENUM('image/jpeg','image/png') NOT NULL,
  ukuran_byte INT UNSIGNED NOT NULL,
  lebar SMALLINT UNSIGNED NOT NULL,
  tinggi SMALLINT UNSIGNED NOT NULL,
  status ENUM('menunggu','disetujui') NOT NULL DEFAULT 'menunggu',
  disetujui_oleh BIGINT UNSIGNED NULL,
  dibuat_pada DATETIME NOT NULL,
  disetujui_pada DATETIME NULL,
  UNIQUE KEY uq_foto_profil_berkas (nama_berkas),
  KEY idx_foto_profil_pengguna (pengguna_id),
  KEY idx_foto_profil_status (status,dibuat_pada),
  CONSTRAINT fk_foto_profil_pemilik FOREIGN KEY (pengguna_id)
    REFERENCES pengguna(id) ON DELETE RESTRICT,
  CONSTRAINT fk_foto_profil_penyetuju FOREIGN KEY (disetujui_oleh)
    REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE profil_pengelola (
  pengguna_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  telepon_pribadi VARCHAR(25) NULL,
  foto_aset_id BIGINT UNSIGNED NULL,
  foto_menunggu_id BIGINT UNSIGNED NULL,
  diperbarui_pada DATETIME NOT NULL,
  KEY idx_profil_foto_aktif (foto_aset_id),
  CONSTRAINT fk_profil_pengelola_pengguna FOREIGN KEY (pengguna_id)
    REFERENCES pengguna(id) ON DELETE RESTRICT,
  CONSTRAINT fk_profil_foto_aktif FOREIGN KEY (foto_aset_id)
    REFERENCES foto_profil(id) ON DELETE RESTRICT,
  CONSTRAINT fk_profil_foto_menunggu FOREIGN KEY (foto_menunggu_id)
    REFERENCES foto_profil(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO schema_version (versi) VALUES ('K2-0007');
