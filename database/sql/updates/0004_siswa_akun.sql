-- SLA-SKS v2.3 | K2-0004 | Data siswa terhubung akun dan Gen.
-- Impor hanya SATU KALI setelah K1-0001, K2-0002 dan K2-0003.
-- Backup database sebelum menjalankan. Tidak mereset/hapus akun apa pun.
SET NAMES utf8mb4;

CREATE TABLE siswa (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  pengguna_id BIGINT UNSIGNED NOT NULL,
  nisn CHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  angkatan_id BIGINT UNSIGNED NULL,
  semester_mulai TINYINT UNSIGNED NULL,
  dibuat_pada DATETIME NOT NULL,
  diperbarui_pada DATETIME NOT NULL,
  UNIQUE KEY uq_siswa_pengguna (pengguna_id),
  UNIQUE KEY uq_siswa_nisn (nisn),
  KEY idx_siswa_angkatan (angkatan_id),
  CONSTRAINT fk_siswa_pengguna FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
  CONSTRAINT fk_siswa_angkatan FOREIGN KEY (angkatan_id) REFERENCES ref_angkatan(id) ON DELETE RESTRICT,
  CONSTRAINT fk_siswa_semester_mulai FOREIGN KEY (semester_mulai) REFERENCES ref_semester(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Menjaga akun siswa yang sudah terbuat sebelum K2-0004.
-- Penempatan Gen TIDAK ditebak; Admin harus melengkapi sebelum pembelajaran.
INSERT INTO siswa (pengguna_id, nisn, angkatan_id, semester_mulai, dibuat_pada, diperbarui_pada)
SELECT p.id, p.username, NULL, NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
FROM pengguna p
INNER JOIN pengguna_peran pp ON pp.pengguna_id = p.id AND pp.peran = 'siswa'
WHERE p.username REGEXP '^[0-9]{10}$';

INSERT INTO schema_version (versi) VALUES ('K2-0004');
