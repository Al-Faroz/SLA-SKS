-- SLA-SKS v2.2: K1 skema minimum identitas dan akses.
-- Impor SATU KALI pada database SLA-SKS kosong. Bukan dump LMS final.
-- UTC untuk nilai DATETIME login dan audit.
SET NAMES utf8mb4;
CREATE TABLE schema_version (
    versi VARCHAR(40) NOT NULL PRIMARY KEY,
    diterapkan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pengguna (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    sandi_hash VARCHAR(255) NOT NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    wajib_ganti_sandi TINYINT(1) NOT NULL DEFAULT 1,
    jumlah_gagal TINYINT UNSIGNED NOT NULL DEFAULT 0,
    terkunci_sampai DATETIME NULL,
    versi_sesi INT UNSIGNED NOT NULL DEFAULT 1,
    login_terakhir DATETIME NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pengguna_username (username),
    KEY idx_pengguna_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE pengguna_peran (
    pengguna_id BIGINT UNSIGNED NOT NULL,
    peran ENUM('admin','guru','siswa','pimpinan') NOT NULL,
    PRIMARY KEY (pengguna_id, peran),
    CONSTRAINT fk_pengguna_peran_pengguna FOREIGN KEY (pengguna_id)
        REFERENCES pengguna(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE batas_login (
    kunci CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
    jumlah_gagal TINYINT UNSIGNED NOT NULL DEFAULT 0,
    mulai_gagal DATETIME NOT NULL,
    terkunci_sampai DATETIME NULL,
    KEY idx_batas_login_sampai (terkunci_sampai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE log_aktivitas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pengguna_id BIGINT UNSIGNED NULL,
    peran_aktif ENUM('admin','guru','siswa','pimpinan') NULL,
    peristiwa VARCHAR(60) NOT NULL,
    catatan VARCHAR(255) NULL,
    alamat_ip VARCHAR(45) NULL,
    waktu DATETIME NOT NULL,
    KEY idx_log_pengguna_waktu (pengguna_id, waktu),
    KEY idx_log_peristiwa_waktu (peristiwa, waktu),
    CONSTRAINT fk_log_pengguna FOREIGN KEY (pengguna_id)
        REFERENCES pengguna(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- CI4 DatabaseHandler dengan matchIP=false.
CREATE TABLE ci_sessions (
    id VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    `timestamp` INT UNSIGNED NOT NULL DEFAULT 0,
    data BLOB NOT NULL,
    KEY idx_ci_sessions_timestamp (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO schema_version (versi) VALUES ('K1-0001');
