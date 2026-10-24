-- SLA-SKS v2.4 / S06A / 2026-10-24
-- Fondasi persisten mesin ujian Ketat. Impor SATU KALI sesudah 0014_progres_tp_pin.sql.
-- WAJIB backup dahulu. HANYA CREATE TABLE. Tidak ada DROP/TRUNCATE/DELETE.
SET NAMES utf8mb4;

CREATE TABLE percobaan_ujian (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_publik CHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  progres_tp_id BIGINT UNSIGNED NOT NULL,
  versi_konten_tp_id BIGINT UNSIGNED NOT NULL,
  jenis ENUM('diagnostik','sumatif') NOT NULL,
  nomor_percobaan SMALLINT UNSIGNED NOT NULL,
  status ENUM('baru','berjalan','jeda_fullscreen','jeda_koneksi','selesai_sah','dibatalkan_menunggu_guru') NOT NULL DEFAULT 'baru',
  profil_keamanan ENUM('ketat') NOT NULL DEFAULT 'ketat',
  jumlah_soal SMALLINT UNSIGNED NOT NULL,
  kkm TINYINT UNSIGNED NOT NULL,
  durasi_detik INT UNSIGNED NOT NULL,
  sisa_detik INT UNSIGNED NOT NULL,
  waktu_mulai DATETIME NULL,
  waktu_aktif_terakhir DATETIME NULL,
  last_seen_server_at DATETIME NULL,
  jeda_mulai_pada DATETIME NULL,
  jeda_fullscreen_total_detik INT UNSIGNED NOT NULL DEFAULT 0,
  jeda_koneksi_total_detik INT UNSIGNED NOT NULL DEFAULT 0,
  jumlah_pelanggaran_fullscreen TINYINT UNSIGNED NOT NULL DEFAULT 0,
  waktu_selesai DATETIME NULL,
  alasan_batal VARCHAR(120) NULL,
  skor DECIMAL(5,2) NULL,
  jumlah_benar SMALLINT UNSIGNED NULL,
  dibuat_pada DATETIME NOT NULL,
  diubah_pada DATETIME NOT NULL,
  PRIMARY KEY(id),
  UNIQUE KEY uq_ujian_token(token_publik),
  UNIQUE KEY uq_ujian_progres_jenis_nomor(progres_tp_id,jenis,nomor_percobaan),
  KEY ix_ujian_status_heartbeat(status,last_seen_server_at),
  KEY ix_ujian_versi(versi_konten_tp_id),
  CONSTRAINT fk_ujian_progres FOREIGN KEY(progres_tp_id) REFERENCES progres_tp(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_ujian_konten FOREIGN KEY(versi_konten_tp_id) REFERENCES versi_konten_tp(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Snapshot per butir menyimpan opsi TERACAK dan kunci opsi TERACAK.
-- Jangan tampilkan kunci_acak atau data JSON ini langsung pada halaman siswa.
CREATE TABLE butir_percobaan (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  percobaan_ujian_id BIGINT UNSIGNED NOT NULL,
  soal_asal_id BIGINT UNSIGNED NULL,
  posisi SMALLINT UNSIGNED NOT NULL,
  pertanyaan TEXT NOT NULL,
  konten_terstruktur_json LONGTEXT NULL,
  opsi_acak_json LONGTEXT NOT NULL,
  kunci_acak CHAR(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY(id),
  UNIQUE KEY uq_butir_posisi(percobaan_ujian_id,posisi),
  KEY ix_butir_soal_asal(soal_asal_id),
  CONSTRAINT fk_butir_ujian FOREIGN KEY(percobaan_ujian_id) REFERENCES percobaan_ujian(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE jawaban_percobaan (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  percobaan_ujian_id BIGINT UNSIGNED NOT NULL,
  butir_percobaan_id BIGINT UNSIGNED NOT NULL,
  opsi_dipilih CHAR(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  waktu_disimpan DATETIME NOT NULL,
  PRIMARY KEY(id),
  UNIQUE KEY uq_jawaban_butir(percobaan_ujian_id,butir_percobaan_id),
  KEY ix_jawaban_butir(butir_percobaan_id),
  CONSTRAINT fk_jawaban_ujian FOREIGN KEY(percobaan_ujian_id) REFERENCES percobaan_ujian(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT fk_jawaban_butir FOREIGN KEY(butir_percobaan_id) REFERENCES butir_percobaan(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE riwayat_status_ujian (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  percobaan_ujian_id BIGINT UNSIGNED NOT NULL,
  status_lama VARCHAR(32) NULL,
  status_baru VARCHAR(32) NOT NULL,
  alasan VARCHAR(160) NOT NULL,
  terjadi_pada DATETIME NOT NULL,
  PRIMARY KEY(id),
  KEY ix_riwayat_ujian(percobaan_ujian_id,terjadi_pada),
  CONSTRAINT fk_riwayat_ujian FOREIGN KEY(percobaan_ujian_id) REFERENCES percobaan_ujian(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE segmen_jeda_ujian (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  percobaan_ujian_id BIGINT UNSIGNED NOT NULL,
  jenis ENUM('fullscreen','koneksi') NOT NULL,
  dimulai_pada DATETIME NOT NULL,
  berakhir_pada DATETIME NULL,
  durasi_detik INT UNSIGNED NOT NULL DEFAULT 0,
  alasan VARCHAR(160) NOT NULL,
  PRIMARY KEY(id),
  KEY ix_segmen_ujian(percobaan_ujian_id,dimulai_pada),
  CONSTRAINT fk_segmen_ujian FOREIGN KEY(percobaan_ujian_id) REFERENCES percobaan_ujian(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pelanggaran_ujian (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  percobaan_ujian_id BIGINT UNSIGNED NOT NULL,
  kunci_insiden CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  kategori ENUM('fullscreen','visibilitas','fokus','koneksi','lainnya') NOT NULL,
  dihitung TINYINT(1) NOT NULL,
  alasan VARCHAR(160) NOT NULL,
  terjadi_pada DATETIME NOT NULL,
  PRIMARY KEY(id),
  UNIQUE KEY uq_insiden_ujian(percobaan_ujian_id,kunci_insiden),
  KEY ix_insiden_ujian(percobaan_ujian_id,terjadi_pada),
  CONSTRAINT fk_pelanggaran_ujian FOREIGN KEY(percobaan_ujian_id) REFERENCES percobaan_ujian(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Uji satu kali sesudah impor:
-- SHOW TABLES LIKE '%ujian';
-- SHOW CREATE TABLE percobaan_ujian;
-- SELECT COUNT(*) AS jumlah_percobaan_awal FROM percobaan_ujian;
-- Tidak boleh membuat percobaan atau jawaban di tahap S06A.
