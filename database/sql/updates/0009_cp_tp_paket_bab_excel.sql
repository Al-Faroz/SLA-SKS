-- SLA-SKS v2.3 K3B–K4B, patch additive 0009
-- BACKUP DB dan writable/; prasyarat K3-0008. Hanya satu kali impor.
SET NAMES utf8mb4;
CREATE TABLE usulan_cp (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 cp_id BIGINT UNSIGNED NOT NULL,
 versi_asal_id BIGINT UNSIGNED NOT NULL,
 pengusul_id BIGINT UNSIGNED NOT NULL,
 jenis ENUM('tambahan_rujukan','koreksi_transkripsi','catatan_lokal') NOT NULL,
 elemen VARCHAR(150) NOT NULL,
 rumusan TEXT NOT NULL,
 otoritas VARCHAR(150) NOT NULL,
 nomor_dokumen VARCHAR(120) NOT NULL,
 sumber_url VARCHAR(1024) NOT NULL,
 tanggal_berlaku DATE NOT NULL,
 alasan TEXT NOT NULL,
 status ENUM('diajukan','disetujui','ditolak','dikembalikan') NOT NULL DEFAULT 'diajukan',
 catatan_keputusan TEXT NULL,
 pemutus_id BIGINT UNSIGNED NULL,
 diputus_pada DATETIME NULL,
 versi_hasil_id BIGINT UNSIGNED NULL,
 dibuat_pada DATETIME NOT NULL,
 FOREIGN KEY (cp_id) REFERENCES ref_cp(id) ON DELETE RESTRICT,
 FOREIGN KEY (versi_asal_id) REFERENCES ref_cp_versi(id) ON DELETE RESTRICT,
 FOREIGN KEY (pengusul_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 FOREIGN KEY (pemutus_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 FOREIGN KEY (versi_hasil_id) REFERENCES ref_cp_versi(id) ON DELETE RESTRICT,
 KEY idx_usulan_cp_status (status,dibuat_pada),
 KEY idx_usulan_cp_guru (pengusul_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE paket_semester (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 semester TINYINT UNSIGNED NOT NULL,
 tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
 kurikulum_id BIGINT UNSIGNED NOT NULL,
 nomor_versi SMALLINT UNSIGNED NOT NULL DEFAULT 1,
 status ENUM('draf','aktif','arsip') NOT NULL DEFAULT 'draf',
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 UNIQUE KEY uq_paket_versi (angkatan_id,mapel_id,semester,tahun_ajaran_id,nomor_versi),
 FOREIGN KEY (angkatan_id) REFERENCES ref_angkatan(id) ON DELETE RESTRICT,
 FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id) ON DELETE RESTRICT,
 FOREIGN KEY (tahun_ajaran_id) REFERENCES ref_tahun_ajaran(id) ON DELETE RESTRICT,
 FOREIGN KEY (kurikulum_id) REFERENCES ref_kurikulum(id) ON DELETE RESTRICT,
 FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE penugasan_paket (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 semester TINYINT UNSIGNED NOT NULL,
 tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
 paket_id BIGINT UNSIGNED NOT NULL,
 diperbarui_oleh BIGINT UNSIGNED NOT NULL,
 diperbarui_pada DATETIME NOT NULL,
 UNIQUE KEY uq_paket_aktif_konteks (angkatan_id,mapel_id,semester,tahun_ajaran_id),
 UNIQUE KEY uq_penugasan_paket_paket (paket_id),
 FOREIGN KEY (angkatan_id) REFERENCES ref_angkatan(id) ON DELETE RESTRICT,
 FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id) ON DELETE RESTRICT,
 FOREIGN KEY (tahun_ajaran_id) REFERENCES ref_tahun_ajaran(id) ON DELETE RESTRICT,
 FOREIGN KEY (paket_id) REFERENCES paket_semester(id) ON DELETE RESTRICT,
 FOREIGN KEY (diperbarui_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE bab (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 paket_id BIGINT UNSIGNED NOT NULL,
 urutan SMALLINT UNSIGNED NOT NULL,
 judul VARCHAR(180) NOT NULL,
 status ENUM('draf','siap') NOT NULL DEFAULT 'draf',
 struktur_versi INT UNSIGNED NOT NULL DEFAULT 1,
 disiapkan_oleh BIGINT UNSIGNED NULL,
 disiapkan_pada DATETIME NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 UNIQUE KEY uq_bab_urut (paket_id,urutan),
 FOREIGN KEY (paket_id) REFERENCES paket_semester(id) ON DELETE RESTRICT,
 FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT,
 FOREIGN KEY (disiapkan_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tp (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 bab_id BIGINT UNSIGNED NOT NULL,
 kode_tp VARCHAR(40) NOT NULL,
 rumusan TEXT NOT NULL,
 indikator TEXT NOT NULL,
 urutan_atp SMALLINT UNSIGNED NOT NULL,
 kkm TINYINT UNSIGNED NOT NULL DEFAULT 75,
 bobot_resume TINYINT UNSIGNED NOT NULL DEFAULT 20,
 bobot_tugas TINYINT UNSIGNED NOT NULL DEFAULT 30,
 bobot_sumatif TINYINT UNSIGNED NOT NULL DEFAULT 50,
 resume_min SMALLINT UNSIGNED NOT NULL DEFAULT 50,
 tugas_min SMALLINT UNSIGNED NOT NULL DEFAULT 200,
 diag_tayang SMALLINT UNSIGNED NOT NULL DEFAULT 10,
 sum_tayang SMALLINT UNSIGNED NOT NULL DEFAULT 20,
 diag_durasi SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 sum_durasi SMALLINT UNSIGNED NOT NULL DEFAULT 60,
 sum_maks_percobaan TINYINT UNSIGNED NOT NULL DEFAULT 3,
 UNIQUE KEY uq_tp_kode (bab_id,kode_tp),
 UNIQUE KEY uq_tp_urutan (bab_id,urutan_atp),
 FOREIGN KEY (bab_id) REFERENCES bab(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tp_cp (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 tp_id BIGINT UNSIGNED NOT NULL,
 cp_versi_id BIGINT UNSIGNED NOT NULL,
 utama TINYINT UNSIGNED NOT NULL DEFAULT 0,
 UNIQUE KEY uq_tp_cp (tp_id,cp_versi_id),
 FOREIGN KEY (tp_id) REFERENCES tp(id) ON DELETE RESTRICT,
 FOREIGN KEY (cp_versi_id) REFERENCES ref_cp_versi(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE berkas (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 bab_id BIGINT UNSIGNED NOT NULL,
 nama_acak VARCHAR(64) NOT NULL,
 mime VARCHAR(90) NOT NULL,
 ukuran_byte INT UNSIGNED NOT NULL,
 checksum CHAR(64) NOT NULL,
 halaman SMALLINT UNSIGNED NULL,
 durasi_detik INT UNSIGNED NULL,
 dibuat_pada DATETIME NOT NULL,
 UNIQUE KEY uq_berkas_nama (nama_acak),
 FOREIGN KEY (bab_id) REFERENCES bab(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE materi (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 tp_id BIGINT UNSIGNED NOT NULL,
 row_uuid CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 urutan SMALLINT UNSIGNED NOT NULL,
 judul VARCHAR(180) NOT NULL,
 jenis ENUM('pdf','audio','gambar','youtube') NOT NULL,
 petunjuk_resume TEXT NOT NULL,
 min_waktu_detik SMALLINT UNSIGNED NOT NULL,
 url_youtube VARCHAR(255) NULL,
 berkas_id BIGINT UNSIGNED NULL,
 aktif TINYINT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY uq_materi_uuid (tp_id,row_uuid),
 UNIQUE KEY uq_materi_urutan (tp_id,urutan),
 FOREIGN KEY (tp_id) REFERENCES tp(id) ON DELETE RESTRICT,
 FOREIGN KEY (berkas_id) REFERENCES berkas(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tugas_hots (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 tp_id BIGINT UNSIGNED NOT NULL,
 row_uuid CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 pertanyaan TEXT NOT NULL,
 petunjuk TEXT NOT NULL,
 UNIQUE KEY uq_hots_tp (tp_id),
 UNIQUE KEY uq_hots_uuid (row_uuid),
 FOREIGN KEY (tp_id) REFERENCES tp(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE soal (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 tp_id BIGINT UNSIGNED NOT NULL,
 row_uuid CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 jenis ENUM('diagnostik','sumatif') NOT NULL,
 urutan SMALLINT UNSIGNED NOT NULL,
 pertanyaan TEXT NOT NULL,
 opsi_a TEXT NOT NULL,
 opsi_b TEXT NOT NULL,
 opsi_c TEXT NULL,
 opsi_d TEXT NULL,
 opsi_e TEXT NULL,
 kunci CHAR(1) NOT NULL,
 aktif TINYINT UNSIGNED NOT NULL DEFAULT 1,
 UNIQUE KEY uq_soal_uuid (tp_id,jenis,row_uuid),
 UNIQUE KEY uq_soal_urut (tp_id,jenis,urutan),
 FOREIGN KEY (tp_id) REFERENCES tp(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE versi_konten_bab (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 bab_id BIGINT UNSIGNED NOT NULL,
 nomor_versi INT UNSIGNED NOT NULL,
 snapshot_json LONGTEXT NOT NULL,
 checksum CHAR(64) NOT NULL,
 disiapkan_oleh BIGINT UNSIGNED NOT NULL,
 disiapkan_pada DATETIME NOT NULL,
 UNIQUE KEY uq_versi_bab (bab_id,nomor_versi),
 FOREIGN KEY (bab_id) REFERENCES bab(id) ON DELETE RESTRICT,
 FOREIGN KEY (disiapkan_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE rahasia_template_bab (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 rahasia CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 dibuat_pada DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE impor_bab (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 bab_id BIGINT UNSIGNED NOT NULL,
 guru_id BIGINT UNSIGNED NOT NULL,
 checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 struktur_versi INT UNSIGNED NOT NULL,
 status ENUM('pratinjau','diterapkan','kedaluwarsa') NOT NULL DEFAULT 'pratinjau',
 data_json LONGTEXT NOT NULL,
 jumlah_tambah SMALLINT UNSIGNED NOT NULL,
 jumlah_ubah SMALLINT UNSIGNED NOT NULL,
 jumlah_galat SMALLINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 kedaluwarsa_pada DATETIME NOT NULL,
 diterapkan_pada DATETIME NULL,
 KEY idx_impor_bab_guru (guru_id,dibuat_pada),
 FOREIGN KEY (bab_id) REFERENCES bab(id) ON DELETE RESTRICT,
 FOREIGN KEY (guru_id) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE impor_baris (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 impor_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 sheet_nama VARCHAR(35) NOT NULL,
 nomor_baris SMALLINT UNSIGNED NOT NULL,
 kolom VARCHAR(32) NOT NULL,
 kode VARCHAR(50) NOT NULL,
 pesan VARCHAR(255) NOT NULL,
 KEY idx_impor_baris_error (impor_id,sheet_nama,nomor_baris),
 FOREIGN KEY (impor_id) REFERENCES impor_bab(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO schema_version (versi) VALUES ('K4-0009');
