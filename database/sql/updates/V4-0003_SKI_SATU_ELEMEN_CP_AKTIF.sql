-- SLA-SKS | V4-0003 | SKI hanya satu elemen CP aktif: Pemahaman Konsep.
-- PATCH LANJUTAN SETELAH V4-0002-CP-SKI-PK yang SUDAH diimpor pemilik.
-- Target: sla_sks_v4, MariaDB 10.4+ / MySQL 8. Tidak perlu impor ulang V4-0002.
-- Hanya mengarsipkan versi Keterampilan Proses SKI yang masih aktif.
-- Referensi, tautan, riwayat TP, hasil ujian, materi, dan progres siswa TIDAK dihapus.
-- Impor sekali setelah backup. Jika gagal, HENTIKAN; jangan jalankan ulang sebagian.

SET NAMES utf8mb4;
START TRANSACTION;

SET @ski_cm = (
    SELECT cm.id FROM cp_mapel cm
    JOIN ref_mapel m ON m.id=cm.mapel_id
    JOIN ref_fase f ON f.id=cm.fase_id
    JOIN ref_kurikulum k ON k.id=cm.kurikulum_id
    WHERE m.kode='SKI' AND f.kode='D'
      AND k.nama='Kurikulum Berbasis Cinta (KBC)'
      AND k.versi='Kurikulum Madrasah 2025 (KMA 1503/2025)'
    LIMIT 1
);
SET @ski_kp_aktif = (
    SELECT v.id FROM cp_mapel_elemen ce
    JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
    JOIN ref_cp r ON r.id=v.cp_id
    WHERE ce.cp_mapel_id=@ski_cm AND v.status='aktif'
      AND v.elemen='Keterampilan Proses' AND r.kode='FD-SKI-02'
    LIMIT 1
);
SET @ski_pk_resmi = (
    SELECT v.id FROM cp_mapel_elemen ce
    JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
    JOIN ref_cp r ON r.id=v.cp_id
    WHERE ce.cp_mapel_id=@ski_cm AND v.status='aktif'
      AND v.elemen='Pemahaman Konsep' AND r.kode='KBC25-SKI-D-PK'
    LIMIT 1
);
SET @ski_jumlah_elemen_aktif = (
    SELECT COUNT(*) FROM cp_mapel_elemen ce
    JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
    WHERE ce.cp_mapel_id=@ski_cm AND v.status='aktif'
);

-- Guard: hanya struktur hasil V4-0002 yang persis cocok (2 elemen aktif).
-- Nilai NULL sengaja memicu kegagalan NOT NULL jika prasyarat tidak terpenuhi.
SET @siap_v4_0003 = (
    (SELECT COUNT(*) FROM schema_version WHERE versi='V4-0001-R2') = 1
    AND (SELECT COUNT(*) FROM schema_version WHERE versi='V4-0002-CP-SKI-PK') = 1
    AND (SELECT COUNT(*) FROM schema_version WHERE versi='V4-0003-SKI-CP-TUNGGAL') = 0
    AND @ski_cm IS NOT NULL AND @ski_kp_aktif IS NOT NULL
    AND @ski_pk_resmi IS NOT NULL AND @ski_jumlah_elemen_aktif=2
);
INSERT INTO schema_version(versi)
VALUES (CASE WHEN @siap_v4_0003 THEN 'V4-0003-SKI-CP-TUNGGAL' ELSE NULL END);

-- Tidak menghapus versi atau tabel penghubung agar tetap dapat diaudit.
UPDATE ref_cp_versi
SET status='diarsipkan'
WHERE id=@ski_kp_aktif AND elemen='Keterampilan Proses' AND status='aktif';

INSERT INTO log_aktivitas(pengguna_id,peran_aktif,peristiwa,catatan,waktu)
SELECT r.dibuat_oleh,'admin','cp_ski_elemen_dinonaktifkan',
       CONCAT('CP operasional SKI=',@ski_cm,
              '; aktif hanya PK versi=',@ski_pk_resmi,
              '; KP historis=',@ski_kp_aktif),
       NOW()
FROM ref_cp_versi v
JOIN ref_cp r ON r.id=v.cp_id
WHERE v.id=@ski_pk_resmi;

COMMIT;

-- QA (baca saja):
-- SELECT v.id,r.kode,v.elemen,v.status FROM cp_mapel_elemen ce
-- JOIN ref_cp_versi v ON v.id=ce.cp_versi_id
-- JOIN ref_cp r ON r.id=v.cp_id
-- WHERE ce.cp_mapel_id=@ski_cm ORDER BY v.id;
-- Harus satu baris AKTIF: KBC25-SKI-D-PK / Pemahaman Konsep.
