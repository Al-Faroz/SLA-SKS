<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/**
 * Menghasilkan SQL privat bootstrap Admin pada salinan v5 yang sudah dibersihkan.
 * Tidak mengakses database dan tidak menyimpan kredensial ke repository.
 */
final class AdminHostingSqlService
{
    private const PENANDA = 'V5-0003-ADMIN-PRODUKSI';

    public static function buat(string $username, string $nama, string $hashSandi): string
    {
        $username = trim($username);
        $nama = trim($nama);

        if (! preg_match('/^[A-Za-z0-9_.-]{3,80}$/D', $username)) {
            throw new DomainException('Username Admin harus 3–80 karakter: huruf, angka, _, titik, atau minus.');
        }
        if (preg_match('/[\x00-\x1F\x7F]/u', $nama) || mb_strlen($nama) < 3 || mb_strlen($nama) > 150) {
            throw new DomainException('Nama Admin wajib 3–150 karakter tanpa karakter kontrol.');
        }
        if (password_get_info($hashSandi)['algoName'] === 'unknown') {
            throw new DomainException('Hash sandi tidak sah. Gunakan password_hash(PASSWORD_DEFAULT).');
        }

        // Nama dalam hex utf8mb4 menghindari injeksi dan variasi SQL_MODE untuk apostrof/backslash.
        $namaHex = bin2hex($nama);
        $usernameSql = "'{$username}'";
        $hashSql = "'" . str_replace("'", "''", $hashSandi) . "'";

        $penanda = self::PENANDA;

        return <<<SQL
-- SLA-SKS v2.5 | Admin produksi satu kali | PRIVATE / JANGAN DI-COMMIT
-- Sumber: generator lokal php spark sla:admin-hosting-sql
-- PILIH database hosting hasil impor v5 di phpMyAdmin. Tidak ada USE karena
-- nama database shared hosting dapat mempunyai prefix akun.
-- Wajib sudah terpasang V5-0001-DATA-GEN2025 dan V5-0002-FAQ-DRAF.
-- Tidak berlaku untuk sla_sks_v4. Bila preflight ERROR, STOP; jangan jalankan ulang.
-- Kata sandi asli TIDAK dicantumkan. Ganti sandi setelah login pertama.

SET @sla_admin_username = {$usernameSql};
SET @sla_admin_nama = CONVERT(0x{$namaHex} USING utf8mb4);
SET @sla_admin_hash = {$hashSql};

-- Preflight gagal tertutup pada MariaDB 10.4+/MySQL 8 dengan CHECK aktif.
CREATE TEMPORARY TABLE _sla_guard_admin_hosting (
    ok TINYINT NOT NULL,
    CONSTRAINT ck_sla_guard_admin_hosting CHECK (ok = 1)
) ENGINE=MEMORY;
INSERT INTO _sla_guard_admin_hosting (ok)
SELECT CASE WHEN
    (SELECT COUNT(*) FROM schema_version WHERE versi = 'V5-0001-DATA-GEN2025') = 1
    AND (SELECT COUNT(*) FROM schema_version WHERE versi = 'V5-0002-FAQ-DRAF') = 1
    AND (SELECT COUNT(*) FROM schema_version WHERE versi = '{$penanda}') = 0
    AND (SELECT COUNT(*) FROM cp_mapel) = 15
    AND (SELECT COUNT(*) FROM pengguna p JOIN pengguna_peran r
         ON r.pengguna_id = p.id
         WHERE r.peran = 'admin' AND p.status = 'aktif') = 0
    AND (SELECT COUNT(*) FROM pengguna p JOIN pengguna_peran r
         ON r.pengguna_id = p.id
         WHERE p.id = 1 AND r.peran = 'admin' AND p.status = 'nonaktif') = 1
    AND (SELECT COUNT(*) FROM pengguna WHERE username = @sla_admin_username) = 0
    THEN 1 ELSE 0 END;
DROP TEMPORARY TABLE _sla_guard_admin_hosting;

START TRANSACTION;
INSERT INTO pengguna
    (username, nama, sandi_hash, status, wajib_ganti_sandi,
     jumlah_gagal, terkunci_sampai, versi_sesi, login_terakhir)
VALUES
    (@sla_admin_username, @sla_admin_nama, @sla_admin_hash,
     'aktif', 1, 0, NULL, 1, NULL);
SET @sla_admin_id_baru = LAST_INSERT_ID();
INSERT INTO pengguna_peran (pengguna_id, peran)
VALUES (@sla_admin_id_baru, 'admin');
INSERT INTO log_aktivitas
    (pengguna_id, peran_aktif, peristiwa, catatan, waktu)
VALUES
    (@sla_admin_id_baru, 'admin', 'admin_produksi_dibuat',
     'Bootstrap privat via SQL impor satu kali; wajib ganti sandi', NOW());
INSERT INTO schema_version (versi, diterapkan_pada)
VALUES ('{$penanda}', NOW());
COMMIT;

-- Ringkasan tanpa sandi/hash/identitas pribadi.
SELECT
    (SELECT COUNT(*) FROM pengguna p JOIN pengguna_peran r
     ON r.pengguna_id = p.id
     WHERE r.peran = 'admin' AND p.status = 'aktif') AS admin_aktif,
    (SELECT COUNT(*) FROM pengguna p JOIN pengguna_peran r
     ON r.pengguna_id = p.id
     WHERE r.peran = 'admin' AND p.status = 'aktif' AND p.wajib_ganti_sandi = 1) AS admin_wajib_ganti_sandi,
    (SELECT COUNT(*) FROM schema_version WHERE versi = '{$penanda}') AS penanda_admin_produksi;
SET @sla_admin_hash = NULL;
SET @sla_admin_nama = NULL;
SET @sla_admin_username = NULL;
SET @sla_admin_id_baru = NULL;
SQL;
    }
}
