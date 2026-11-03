-- SLA-SKS v2.5 | V4-0010 BANK WORD PRA-SEMESTER
-- Impor satu kali setelah backup dan konfirmasi V4-0009 telah terpasang.
-- Tabel staging khusus pra-semester, bukan impor_soal_docx untuk TP.
-- Tidak ada DROP, DELETE, TRUNCATE atau UPDATE data siswa/bank lama.

CREATE TABLE impor_soal_pra_docx (
 id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 paket_semester_id BIGINT UNSIGNED NOT NULL,
 guru_id BIGINT UNSIGNED NOT NULL,
 checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 status ENUM('pratinjau','diterapkan','kedaluwarsa') NOT NULL DEFAULT 'pratinjau',
 data_json LONGTEXT NOT NULL,
 dibuat_pada DATETIME NOT NULL,
 kedaluwarsa_pada DATETIME NOT NULL,
 diterapkan_pada DATETIME NULL,
 PRIMARY KEY(id),
 KEY ix_word_pra_paket(paket_semester_id,guru_id,status,dibuat_pada),
 CONSTRAINT fk_word_pra_paket FOREIGN KEY(paket_semester_id)
     REFERENCES paket_semester(id),
 CONSTRAINT fk_word_pra_guru FOREIGN KEY(guru_id)
     REFERENCES pengguna(id),
 CONSTRAINT ck_word_pra_data CHECK (JSON_VALID(data_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO schema_version(versi,diterapkan_pada)
VALUES ('V4-0010-BANK-WORD-PRA',CURRENT_TIMESTAMP());
