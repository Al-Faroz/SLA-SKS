-- SLA-SKS v2.3 | K2-0003 | Penugasan guru aktif per Gen + Mapel.
-- Prasyarat: K1-0001 dan K2-0002 sudah terpasang, K2-0003 belum ada.
-- WAJIB backup database & writable/ sebelum impor di phpMyAdmin.
-- File ini additive; TIDAK mengubah atau menghapus ref_mapel.guru_id,
-- riwayat_guru_mapel, akun, Gen, mapel, ataupun data identitas lama.
-- Tidak ada migrasi penugasan otomatis: Admin harus memilih Guru tiap Gen–Mapel.
SET NAMES utf8mb4;

CREATE TABLE penugasan_guru_gen (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 guru_id BIGINT UNSIGNED NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 diperbarui_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 diperbarui_pada DATETIME NOT NULL,
 UNIQUE KEY uq_g1_gen_mapel (angkatan_id, mapel_id),
 KEY idx_g1_guru (guru_id),
 CONSTRAINT fk_g1_gen FOREIGN KEY (angkatan_id) REFERENCES ref_angkatan(id) ON DELETE RESTRICT,
 CONSTRAINT fk_g1_mapel FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id) ON DELETE RESTRICT,
 CONSTRAINT fk_g1_guru FOREIGN KEY (guru_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 CONSTRAINT fk_g1_pembuat FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT,
 CONSTRAINT fk_g1_pengubah FOREIGN KEY (diperbarui_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE riwayat_penugasan_guru_gen (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 guru_sebelum_id BIGINT UNSIGNED NULL,
 guru_sesudah_id BIGINT UNSIGNED NULL,
 admin_id BIGINT UNSIGNED NOT NULL,
 alasan VARCHAR(255) NOT NULL,
 waktu DATETIME NOT NULL,
 KEY idx_riwayat_g1_gen_mapel (angkatan_id,mapel_id,waktu),
 CONSTRAINT fk_rpg_gen FOREIGN KEY (angkatan_id) REFERENCES ref_angkatan(id) ON DELETE RESTRICT,
 CONSTRAINT fk_rpg_mapel FOREIGN KEY (mapel_id) REFERENCES ref_mapel(id) ON DELETE RESTRICT,
 CONSTRAINT fk_rpg_lama FOREIGN KEY (guru_sebelum_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 CONSTRAINT fk_rpg_baru FOREIGN KEY (guru_sesudah_id) REFERENCES pengguna(id) ON DELETE RESTRICT,
 CONSTRAINT fk_rpg_admin FOREIGN KEY (admin_id) REFERENCES pengguna(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Penanda versi TERAKHIR; jika impor DDL terhenti, pulihkan backup dahulu.
INSERT INTO schema_version (versi) VALUES ('K2-0003');
