-- SLA-SKS v2.4 / S08B / 2026-10-24
-- Tambahan progres media siswa. Impor SEKALI setelah 0014 dan 0015.
-- WAJIB backup sebelumnya. Tidak mengubah progres/ujian historis.
SET NAMES utf8mb4;

CREATE TABLE progres_materi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    progres_tp_id BIGINT UNSIGNED NOT NULL,
    materi_id BIGINT UNSIGNED NOT NULL,
    versi_konten_tp_id BIGINT UNSIGNED NOT NULL,
    jenis ENUM('pdf','youtube','audio','gambar') NOT NULL,
    status ENUM('berjalan','media_selesai') NOT NULL DEFAULT 'berjalan',
    waktu_aktif_detik INT UNSIGNED NOT NULL DEFAULT 0,
    waktu_putar_detik INT UNSIGNED NOT NULL DEFAULT 0,
    posisi_terakhir_detik INT UNSIGNED NULL,
    posisi_terjauh_detik INT UNSIGNED NOT NULL DEFAULT 0,
    durasi_media_detik INT UNSIGNED NULL,
    halaman_total SMALLINT UNSIGNED NULL,
    halaman_selesai SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    heartbeat_terakhir_pada DATETIME NULL,
    dibuat_pada DATETIME NOT NULL,
    diubah_pada DATETIME NOT NULL,
    media_selesai_pada DATETIME NULL,
    PRIMARY KEY(id),
    UNIQUE KEY uq_progres_materi_versi(progres_tp_id,materi_id,versi_konten_tp_id),
    KEY ix_progres_materi_materi(materi_id),
    KEY ix_progres_materi_versi(versi_konten_tp_id),
    CONSTRAINT fk_pm_progres FOREIGN KEY (progres_tp_id)
        REFERENCES progres_tp(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_pm_materi FOREIGN KEY (materi_id)
        REFERENCES materi(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_pm_versi FOREIGN KEY (versi_konten_tp_id)
        REFERENCES versi_konten_tp(id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Disiapkan untuk PDF.js lokal: setiap halaman harus punya waktu aktif cukup.
-- Belum boleh mengisi klaim halaman dari iframe PDF bawaan browser.
CREATE TABLE halaman_progres_materi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    progres_materi_id BIGINT UNSIGNED NOT NULL,
    nomor_halaman SMALLINT UNSIGNED NOT NULL,
    waktu_aktif_detik INT UNSIGNED NOT NULL DEFAULT 0,
    selesai_pada DATETIME NULL,
    PRIMARY KEY(id),
    UNIQUE KEY uq_halaman_progres(progres_materi_id,nomor_halaman),
    CONSTRAINT fk_halaman_pm FOREIGN KEY(progres_materi_id)
        REFERENCES progres_materi(id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Pemeriksaan pasca impor:
-- SHOW CREATE TABLE progres_materi;
-- SHOW CREATE TABLE halaman_progres_materi;
-- SELECT COUNT(*) FROM progres_materi; -- diharapkan 0
