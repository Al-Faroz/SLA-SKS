-- V4-0001-R2: SKEMA POLA BARU SLA-SKS (FK MariaDB diperbaiki)
-- Diterapkan HANYA pada clone database sla_sks_v4 dari dump v3 terbaru.
-- JANGAN jalankan pada sla_sks_v3. TIDAK berisi data pribadi.
-- Periksa dan backup sebelum impor. Lakukan sekali; tidak idempoten.
USE sla_sks_v4;

CREATE TABLE cp_mapel (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 kurikulum_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 fase_id TINYINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_cp_satu_mapel(kurikulum_id,mapel_id,fase_id),
 FOREIGN KEY(kurikulum_id) REFERENCES ref_kurikulum(id),
 FOREIGN KEY(mapel_id) REFERENCES ref_mapel(id),
 FOREIGN KEY(fase_id) REFERENCES ref_fase(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cp_mapel_elemen (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 cp_mapel_id BIGINT UNSIGNED NOT NULL,
 cp_versi_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_cpmapel_elemen(cp_mapel_id,cp_versi_id),
 FOREIGN KEY(cp_mapel_id) REFERENCES cp_mapel(id),
 FOREIGN KEY(cp_versi_id) REFERENCES ref_cp_versi(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO cp_mapel(kurikulum_id,mapel_id,fase_id,dibuat_pada)
SELECT DISTINCT kurikulum_id,mapel_id,fase_id,NOW() FROM ref_cp;
INSERT INTO cp_mapel_elemen(cp_mapel_id,cp_versi_id)
SELECT cm.id,cv.id FROM ref_cp_versi cv
JOIN ref_cp c ON c.id=cv.cp_id
JOIN cp_mapel cm ON cm.kurikulum_id=c.kurikulum_id AND cm.mapel_id=c.mapel_id AND cm.fase_id=c.fase_id;

CREATE TABLE bab (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 paket_semester_id BIGINT UNSIGNED NOT NULL,
 angkatan_id BIGINT UNSIGNED NOT NULL,
 mapel_id BIGINT UNSIGNED NOT NULL,
 kurikulum_id BIGINT UNSIGNED NOT NULL,
 tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
 kelas_id TINYINT UNSIGNED NOT NULL,
 nomor_bab SMALLINT UNSIGNED NOT NULL,
 nama_bab VARCHAR(35) NOT NULL,
 deskripsi_bab VARCHAR(225) NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_bab_paket_nomor(paket_semester_id,nomor_bab),
 UNIQUE KEY uq_bab_tingkat_tahun(angkatan_id,mapel_id,kurikulum_id,tahun_ajaran_id,kelas_id,nomor_bab),
 FOREIGN KEY(paket_semester_id) REFERENCES paket_semester(id),
 FOREIGN KEY(angkatan_id) REFERENCES ref_angkatan(id),
 FOREIGN KEY(mapel_id) REFERENCES ref_mapel(id),
 FOREIGN KEY(kurikulum_id) REFERENCES ref_kurikulum(id),
 FOREIGN KEY(tahun_ajaran_id) REFERENCES ref_tahun_ajaran(id),
 FOREIGN KEY(kelas_id) REFERENCES ref_kelas(id),
 CONSTRAINT ck_bab_nomor CHECK(nomor_bab BETWEEN 1 AND 999)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE tp
 MODIFY COLUMN status ENUM('draf','siap','arsip') NOT NULL DEFAULT 'draf',
 ADD COLUMN bab_id BIGINT UNSIGNED NULL AFTER paket_semester_id,
 ADD COLUMN cp_mapel_id BIGINT UNSIGNED NULL AFTER bab_id,
 ADD COLUMN jenis_tp ENUM('esensial','non_esensial') NOT NULL DEFAULT 'non_esensial' AFTER cp_mapel_id,
 ADD COLUMN nama_tp VARCHAR(35) NULL AFTER judul,
 ADD COLUMN deskripsi_tp VARCHAR(225) NULL AFTER nama_tp,
 ADD COLUMN urutan_dalam_bab SMALLINT UNSIGNED NULL AFTER urutan_atp,
 ADD COLUMN kkm_khusus TINYINT UNSIGNED NULL AFTER kkm,
 MODIFY COLUMN bobot_resume TINYINT UNSIGNED NOT NULL DEFAULT 20,
 MODIFY COLUMN bobot_tugas TINYINT UNSIGNED NOT NULL DEFAULT 20,
 MODIFY COLUMN bobot_sumatif TINYINT UNSIGNED NOT NULL DEFAULT 60,
 MODIFY COLUMN sum_tayang SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 MODIFY COLUMN sum_maks_percobaan TINYINT UNSIGNED NOT NULL DEFAULT 2,
 ADD KEY ix_tp_bab_urut(bab_id,urutan_dalam_bab),
 ADD KEY ix_tp_cpmapel(cp_mapel_id),
 ADD CONSTRAINT fk_tp_bab FOREIGN KEY(bab_id) REFERENCES bab(id),
 ADD CONSTRAINT fk_tp_cpmapel FOREIGN KEY(cp_mapel_id) REFERENCES cp_mapel(id),
 ADD CONSTRAINT ck_tp_kkm_khusus CHECK(kkm_khusus IS NULL OR kkm_khusus BETWEEN 1 AND 100);

-- Archive ONLY in v4: no arbitrary choice among 4 test media.
UPDATE tp SET status='arsip' WHERE bab_id IS NULL AND status='siap';
UPDATE materi m JOIN tp t ON t.id=m.tp_id SET m.aktif=0 WHERE t.status='arsip';
UPDATE tp t JOIN paket_semester p ON p.id=t.paket_semester_id
JOIN cp_mapel cm ON cm.kurikulum_id=p.kurikulum_id AND cm.mapel_id=p.mapel_id
SET t.cp_mapel_id=cm.id WHERE t.cp_mapel_id IS NULL;

ALTER TABLE materi
 ADD COLUMN aktif_tp_kunci BIGINT UNSIGNED GENERATED ALWAYS AS
 (CASE WHEN aktif=1 THEN tp_id ELSE NULL END) STORED,
 ADD UNIQUE KEY uq_materi_satu_aktif_per_tp(aktif_tp_kunci);

INSERT INTO pengaturan(kunci,nilai,diperbarui_oleh,diperbarui_pada)
VALUES('kkm_default',NULL,NULL,NULL)
ON DUPLICATE KEY UPDATE kunci=VALUES(kunci);

ALTER TABLE progres_materi
 ADD COLUMN detik_checkpoint INT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN checkpoint_pada DATETIME NULL,
 ADD COLUMN versi_checkpoint INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE nilai_tp (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 progres_tp_id BIGINT UNSIGNED NOT NULL,
 jalur ENUM('lulus_diagnostik','belajar_wajib') NOT NULL,
 nilai_resume DECIMAL(5,2) NULL,
 nilai_hots DECIMAL(5,2) NULL,
 nilai_sumatif DECIMAL(5,2) NULL,
 nilai_tp DECIMAL(5,2) NULL,
 status_nilai ENUM('sementara','final') NOT NULL DEFAULT 'sementara',
 diubah_pada DATETIME NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_nilai_satu_progres(progres_tp_id),
 FOREIGN KEY(progres_tp_id) REFERENCES progres_tp(id),
 CONSTRAINT ck_ntp_nilai CHECK((nilai_sumatif IS NULL OR nilai_sumatif BETWEEN 0 AND 100) AND (nilai_tp IS NULL OR nilai_tp BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ujian_pra_semester (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 paket_semester_id BIGINT UNSIGNED NOT NULL,
 status ENUM('draf','siap','ditutup') NOT NULL DEFAULT 'draf',
 mulai_pada DATETIME NULL,
 selesai_pada DATETIME NULL,
 jumlah_soal SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 durasi_menit SMALLINT UNSIGNED NOT NULL DEFAULT 60,
 kkm_khusus TINYINT UNSIGNED NULL,
 dibuat_oleh BIGINT UNSIGNED NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_pra_per_paket(paket_semester_id),
 FOREIGN KEY(paket_semester_id) REFERENCES paket_semester(id),
 FOREIGN KEY(dibuat_oleh) REFERENCES pengguna(id),
 CONSTRAINT ck_pra_30 CHECK(jumlah_soal>=30),
 CONSTRAINT ck_pra_jadwal CHECK(mulai_pada IS NULL OR selesai_pada IS NULL OR selesai_pada>mulai_pada),
 CONSTRAINT ck_pra_kkm CHECK(kkm_khusus IS NULL OR kkm_khusus BETWEEN 1 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE soal_pra_semester (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 ujian_pra_semester_id BIGINT UNSIGNED NOT NULL,
 bab_id BIGINT UNSIGNED NOT NULL,
 urutan SMALLINT UNSIGNED NOT NULL,
 pertanyaan TEXT NOT NULL,
 konten_terstruktur_json LONGTEXT NULL,
 opsi_a TEXT NOT NULL,
 opsi_b TEXT NOT NULL,
 opsi_c TEXT NOT NULL,
 opsi_d TEXT NOT NULL,
 opsi_e TEXT NOT NULL,
 kunci ENUM('A','B','C','D','E') NOT NULL,
 aktif TINYINT UNSIGNED NOT NULL DEFAULT 1,
 PRIMARY KEY(id),
 UNIQUE KEY uq_soalpra_urutan(ujian_pra_semester_id,urutan),
 KEY ix_soalpra_bab(bab_id),
 FOREIGN KEY(ujian_pra_semester_id) REFERENCES ujian_pra_semester(id),
 FOREIGN KEY(bab_id) REFERENCES bab(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- V4-R2: Lepaskan FK lama sebelum ubah nullability kolom MariaDB.
ALTER TABLE percobaan_ujian
 DROP FOREIGN KEY fk_ujian_progres,
 DROP FOREIGN KEY fk_ujian_konten;

ALTER TABLE percobaan_ujian
 MODIFY COLUMN progres_tp_id BIGINT UNSIGNED NULL,
 MODIFY COLUMN versi_konten_tp_id BIGINT UNSIGNED NULL,
 MODIFY COLUMN jenis ENUM('diagnostik','sumatif','pra_semester') NOT NULL,
 ADD COLUMN ujian_pra_semester_id BIGINT UNSIGNED NULL AFTER versi_konten_tp_id,
 ADD COLUMN siswa_id BIGINT UNSIGNED NULL AFTER ujian_pra_semester_id,
 ADD COLUMN tahap ENUM('normal','remedial') NOT NULL DEFAULT 'normal' AFTER jenis,
 ADD KEY ix_pra_percobaan(ujian_pra_semester_id,siswa_id,tahap),
 ADD CONSTRAINT fk_percobaan_pra FOREIGN KEY(ujian_pra_semester_id) REFERENCES ujian_pra_semester(id),
 ADD CONSTRAINT fk_percobaan_pra_siswa FOREIGN KEY(siswa_id) REFERENCES siswa(id),
 ADD CONSTRAINT ck_percobaan_jalur CHECK(
 (jenis='pra_semester' AND ujian_pra_semester_id IS NOT NULL AND siswa_id IS NOT NULL AND progres_tp_id IS NULL AND versi_konten_tp_id IS NULL)
 OR (jenis IN('diagnostik','sumatif') AND ujian_pra_semester_id IS NULL AND progres_tp_id IS NOT NULL AND versi_konten_tp_id IS NOT NULL)
 );
ALTER TABLE percobaan_ujian
 ADD CONSTRAINT fk_ujian_progres FOREIGN KEY(progres_tp_id) REFERENCES progres_tp(id),
 ADD CONSTRAINT fk_ujian_konten FOREIGN KEY(versi_konten_tp_id) REFERENCES versi_konten_tp(id);

ALTER TABLE butir_percobaan
 ADD COLUMN soal_pra_semester_id BIGINT UNSIGNED NULL AFTER soal_asal_id,
 ADD KEY ix_butir_pra_soal(soal_pra_semester_id),
 ADD CONSTRAINT fk_butir_pra_soal FOREIGN KEY(soal_pra_semester_id) REFERENCES soal_pra_semester(id);

INSERT INTO schema_version(versi,diterapkan_pada) VALUES('V4-0001-R2',CURRENT_TIMESTAMP());
