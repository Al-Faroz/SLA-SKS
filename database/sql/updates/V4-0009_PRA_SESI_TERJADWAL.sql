-- SLA-SKS v2.5 | V4-0009-PRA-SESI-TERJADWAL
-- Basis audit: dump privat sla_sks_v4 (13).sql, dibuat 03-11-2026 04.32
-- MariaDB 10.4.32, schema_version terakhir V4-0008-KEPUTUSAN-UJIAN-BATAL.
-- ADITIF: tidak ada DROP/DELETE/TRUNCATE/UPDATE data lama.
-- Impor SEKALI di phpMyAdmin pada database sla_sks_v4 setelah BACKUP.
-- Pastikan V4-0008 ada dan V4-0009 belum ada lewat QA read-only.
-- Sesi utama tetap di ujian_pra_semester; tabel ini hanya remedial/susulan.
-- Jendela jadwal adalah BOLEH MULAI, durasi dihitung sejak tiap siswa klik Mulai.

CREATE TABLE jadwal_pra_sesi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ujian_pra_semester_id BIGINT UNSIGNED NOT NULL,
    jenis ENUM('remedial','susulan') NOT NULL,
    tahap ENUM('normal','remedial') NOT NULL,
    mulai_pada DATETIME NOT NULL,
    selesai_pada DATETIME NOT NULL,
    durasi_menit SMALLINT UNSIGNED NOT NULL,
    status ENUM('siap','ditutup') NOT NULL DEFAULT 'siap',
    dibuat_oleh BIGINT UNSIGNED NOT NULL,
    dibuat_pada DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ix_jadwal_pra_ujian (ujian_pra_semester_id,status,mulai_pada),
    KEY ix_jadwal_pra_waktu (mulai_pada,selesai_pada),
    KEY fk_jadwal_pra_admin (dibuat_oleh),
    CONSTRAINT fk_jadwal_pra_ujian FOREIGN KEY (ujian_pra_semester_id)
        REFERENCES ujian_pra_semester(id),
    CONSTRAINT fk_jadwal_pra_admin FOREIGN KEY (dibuat_oleh)
        REFERENCES pengguna(id),
    CONSTRAINT ck_jadwal_pra_jenis CHECK
        ((jenis='remedial' AND tahap='remedial') OR jenis='susulan'),
    CONSTRAINT ck_jadwal_pra_waktu CHECK (selesai_pada>mulai_pada),
    CONSTRAINT ck_jadwal_pra_durasi CHECK (durasi_menit BETWEEN 5 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE peserta_pra_susulan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jadwal_pra_sesi_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    ditetapkan_oleh BIGINT UNSIGNED NOT NULL,
    ditetapkan_pada DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pra_susulan_peserta (jadwal_pra_sesi_id,siswa_id),
    KEY ix_pra_susulan_siswa (siswa_id,jadwal_pra_sesi_id),
    KEY fk_pra_susulan_admin (ditetapkan_oleh),
    CONSTRAINT fk_pra_susulan_jadwal FOREIGN KEY (jadwal_pra_sesi_id)
        REFERENCES jadwal_pra_sesi(id),
    CONSTRAINT fk_pra_susulan_siswa FOREIGN KEY (siswa_id)
        REFERENCES siswa(id),
    CONSTRAINT fk_pra_susulan_admin FOREIGN KEY (ditetapkan_oleh)
        REFERENCES pengguna(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE percobaan_ujian
    ADD COLUMN jadwal_pra_sesi_id BIGINT UNSIGNED NULL AFTER ujian_pra_semester_id,
    ADD UNIQUE KEY uq_pra_percobaan_nomor
        (ujian_pra_semester_id,siswa_id,nomor_percobaan),
    ADD KEY ix_pra_percobaan_jadwal (jadwal_pra_sesi_id),
    ADD CONSTRAINT fk_pra_percobaan_sesi FOREIGN KEY (jadwal_pra_sesi_id)
        REFERENCES jadwal_pra_sesi(id);

INSERT INTO schema_version (versi,diterapkan_pada)
VALUES ('V4-0009-PRA-SESI-TERJADWAL',CURRENT_TIMESTAMP());
