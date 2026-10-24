-- SLA-SKS v2.4 · S01-0014 · 2026-10-24
-- Fondasi progres TP siswa dan version pinning. Impor SATU kali via phpMyAdmin.
-- Prasyarat: skema v2.4 yang memuat siswa, tp, paket_semester dan versi_konten_tp.
-- PERINGATAN: backup database sebelum impor. Jangan impor ulang; tidak ada DROP/DELETE.
SET NAMES utf8mb4;

CREATE TABLE progres_tp (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id BIGINT UNSIGNED NOT NULL,
    tp_id BIGINT UNSIGNED NOT NULL,
    paket_semester_id BIGINT UNSIGNED NOT NULL,
    versi_paket_yang_ditugaskan INT UNSIGNED NOT NULL,
    versi_konten_tp_id BIGINT UNSIGNED NOT NULL,
    status ENUM('belum_mulai','diagnostik','belajar','sumatif','perlu_bantuan','tuntas')
        NOT NULL DEFAULT 'diagnostik',
    dimulai_pada DATETIME NOT NULL,
    diubah_pada DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progres_siswa_tp_versi_paket
        (siswa_id, tp_id, versi_paket_yang_ditugaskan),
    KEY ix_progres_siswa_status (siswa_id, status),
    KEY ix_progres_tp (tp_id),
    KEY ix_progres_snapshot (versi_konten_tp_id),
    CONSTRAINT fk_progres_tp_siswa FOREIGN KEY (siswa_id)
        REFERENCES siswa (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_progres_tp_tp FOREIGN KEY (tp_id)
        REFERENCES tp (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_progres_tp_paket FOREIGN KEY (paket_semester_id)
        REFERENCES paket_semester (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_progres_tp_versi FOREIGN KEY (versi_konten_tp_id)
        REFERENCES versi_konten_tp (id) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verifikasi setelah impor:
-- SHOW CREATE TABLE progres_tp;
-- SELECT COUNT(*) AS jumlah_progres_awal FROM progres_tp;
-- Seharusnya jumlah awal 0; tidak ada data siswa yang diisi oleh patch ini.
