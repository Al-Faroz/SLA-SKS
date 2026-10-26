-- SLA-SKS v2.4 S09/S10 (2026-10-25).
-- Impor SEKALI sesudah 0016; WAJIB backup database; jangan mereset nilai/progres.
-- Kedua tabel mengikat siswa melalui progres_tp dan snapshot TP terpin.
-- Instalasi baru: periksa resume_materi/jawaban_hots_materi belum ada.
-- Dump lokal dengan 0017 parsial: jalankan hanya patch pemulihan 0018.
-- tugas_hots_materi adalah bank pertanyaan Guru; JANGAN ubah/hapus.
-- Verifikasi dulu melalui database/sql/qa/0017_verifikasi_kegiatan_readonly.sql.
-- CHECK inline pada kolom nilai agar lebih kompatibel dengan parser phpMyAdmin.
SET NAMES utf8mb4;

CREATE TABLE resume_materi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    progres_tp_id BIGINT UNSIGNED NOT NULL,
    materi_id BIGINT UNSIGNED NOT NULL,
    versi_konten_tp_id BIGINT UNSIGNED NOT NULL,
    isi MEDIUMTEXT NOT NULL,
    status ENUM('draf','final') NOT NULL DEFAULT 'draf',
    revisi INT UNSIGNED NOT NULL DEFAULT 1,
    dibuat_pada DATETIME NOT NULL,
    diubah_pada DATETIME NOT NULL,
    dikirim_pada DATETIME NULL,
    nilai TINYINT UNSIGNED NULL CHECK (nilai <= 100),
    dinilai_oleh BIGINT UNSIGNED NULL,
    umpan_balik VARCHAR(1000) NULL,
    dinilai_pada DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resume_per_materi (progres_tp_id,materi_id,versi_konten_tp_id),
    KEY ix_resume_materi (materi_id,status),
    KEY ix_resume_penilai (dinilai_oleh),
    CONSTRAINT fk_resume_progres FOREIGN KEY (progres_tp_id)
        REFERENCES progres_tp(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_resume_materi FOREIGN KEY (materi_id)
        REFERENCES materi(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_resume_versi FOREIGN KEY (versi_konten_tp_id)
        REFERENCES versi_konten_tp(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_resume_penilai FOREIGN KEY (dinilai_oleh)
        REFERENCES pengguna(id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE jawaban_hots_materi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    progres_tp_id BIGINT UNSIGNED NOT NULL,
    materi_id BIGINT UNSIGNED NOT NULL,
    versi_konten_tp_id BIGINT UNSIGNED NOT NULL,
    isi MEDIUMTEXT NOT NULL,
    status ENUM('draf','final') NOT NULL DEFAULT 'draf',
    revisi INT UNSIGNED NOT NULL DEFAULT 1,
    dibuat_pada DATETIME NOT NULL,
    diubah_pada DATETIME NOT NULL,
    dikirim_pada DATETIME NULL,
    nilai TINYINT UNSIGNED NULL CHECK (nilai <= 100),
    dinilai_oleh BIGINT UNSIGNED NULL,
    umpan_balik VARCHAR(1000) NULL,
    dinilai_pada DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_hots_per_materi (progres_tp_id,materi_id,versi_konten_tp_id),
    KEY ix_hots_materi (materi_id,status),
    KEY ix_hots_penilai (dinilai_oleh),
    CONSTRAINT fk_jawaban_hots_progres FOREIGN KEY (progres_tp_id)
        REFERENCES progres_tp(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_jawaban_hots_materi FOREIGN KEY (materi_id)
        REFERENCES materi(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_jawaban_hots_versi FOREIGN KEY (versi_konten_tp_id)
        REFERENCES versi_konten_tp(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_jawaban_hots_penilai FOREIGN KEY (dinilai_oleh)
        REFERENCES pengguna(id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verifikasi pasca impor (baca saja):
-- SHOW CREATE TABLE resume_materi;
-- SHOW CREATE TABLE jawaban_hots_materi;
-- SELECT COUNT(*) FROM resume_materi;
-- SELECT COUNT(*) FROM jawaban_hots_materi;
