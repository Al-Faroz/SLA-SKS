-- SLA-SKS v2.5 / V4-0008 / 2026-11-02
-- Keputusan Guru atas percobaan ujian yang dibatalkan.
-- Acuan: Master 07 bagian 5, 03 hak akses dan 16 E2E-038.
-- Basis audit: dump sla_sks_v4 (11).sql, dibuat 02-Nov-2026 10:24,
-- MariaDB 10.4.32, schema_version hingga V4-0007.
-- SEKALI IMPOR setelah BACKUP; tidak ada DROP/DELETE/TRUNCATE/UPDATE data lama.
-- Jalankan di database sla_sks_v4, bukan v3.
USE sla_sks_v4;

CREATE TABLE keputusan_ujian_batal (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    percobaan_ujian_id BIGINT UNSIGNED NOT NULL,
    keputusan ENUM('ulang_diagnostik','langsung_belajar','ulang_sumatif','tunda_tinjauan') NOT NULL,
    guru_id BIGINT UNSIGNED NOT NULL,
    alasan VARCHAR(255) NOT NULL,
    diputus_pada DATETIME NOT NULL,
    digunakan_pada DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_keputusan_per_percobaan (percobaan_ujian_id),
    KEY ix_keputusan_guru (guru_id,diputus_pada),
    CONSTRAINT fk_kub_percobaan FOREIGN KEY (percobaan_ujian_id)
        REFERENCES percobaan_ujian (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_kub_guru FOREIGN KEY (guru_id)
        REFERENCES pengguna (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Audit append-only: keputusan tunda boleh ditindaklanjuti kemudian dengan
-- ulang_sumatif; keputusan awal tetap tercatat tanpa ditimpa.
CREATE TABLE riwayat_keputusan_ujian_batal (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    keputusan_ujian_batal_id BIGINT UNSIGNED NOT NULL,
    guru_id BIGINT UNSIGNED NOT NULL,
    keputusan_lama VARCHAR(32) NULL,
    keputusan_baru VARCHAR(32) NOT NULL,
    alasan VARCHAR(255) NOT NULL,
    terjadi_pada DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ix_riwayat_kub (keputusan_ujian_batal_id,terjadi_pada),
    CONSTRAINT fk_rkub_keputusan FOREIGN KEY (keputusan_ujian_batal_id)
        REFERENCES keputusan_ujian_batal (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_rkub_guru FOREIGN KEY (guru_id)
        REFERENCES pengguna (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO schema_version (versi,diterapkan_pada)
VALUES ('V4-0008-KEPUTUSAN-UJIAN-BATAL', CURRENT_TIMESTAMP());

-- QA pasca impor (baca-saja):
-- SELECT versi FROM schema_version WHERE versi='V4-0008-KEPUTUSAN-UJIAN-BATAL';
-- SHOW CREATE TABLE keputusan_ujian_batal;
-- SHOW CREATE TABLE riwayat_keputusan_ujian_batal;
-- SELECT COUNT(*) FROM keputusan_ujian_batal;
