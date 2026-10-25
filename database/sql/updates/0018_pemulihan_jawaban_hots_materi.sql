-- SLA-SKS S09/S10 pemulihan untuk instalasi 0017 PARSIAL, 25-10-2026.
-- SOURCE DUMP LOKAL DIINSPEKSI: tugas_hots_materi = BANK pertanyaan Guru.
-- Jangan DROP, RENAME, ALTER atau TRUNCATE tabel itu.
-- Instalasi dari nol memakai 0017 terbaru, TIDAK memerlukan 0018.
-- Prasyarat: jalankan qa/0018_verifikasi_jawaban_hots_readonly.sql.
-- Impor file ini hanya bila jawaban_hots_materi BELUM_ADA dan
-- resume_materi SUDAH_ADA dengan seluruh 14 kolom. Backup dahulu.
-- DDL MySQL/MariaDB tidak selalu dapat di-rollback secara transaksional.
SET NAMES utf8mb4;

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

-- Verifikasi setelah impor melalui file QA baca saja.
