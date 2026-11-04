-- SLA-SKS v2.5 — Audit BAB, BACA SAJA.
-- HANYA database sla_sks_v4 yang sudah diimpor dengan R2.
-- Tidak mengubah skema/isi. Jalankan dari phpMyAdmin > SQL.
USE sla_sks_v4;

SELECT 'versi_v4' AS pemeriksaan, MAX(versi) AS hasil
FROM schema_version WHERE versi='V4-0001-R2';

SELECT COUNT(*) AS jumlah_bab
FROM bab;

-- Setiap BAB harus cocok dengan identitas paket semester dan kelas.
SELECT b.id, b.paket_semester_id, b.nomor_bab, p.semester,
       b.nama_bab, b.kelas_id AS kelas_di_bab,
       r.kelas_id AS kelas_semester
FROM bab b
JOIN paket_semester p ON p.id=b.paket_semester_id
JOIN ref_semester r ON r.id=p.semester
WHERE b.angkatan_id <> p.angkatan_id
   OR b.mapel_id <> p.mapel_id
   OR b.kurikulum_id <> p.kurikulum_id
   OR b.tahun_ajaran_id <> p.tahun_ajaran_id
   OR b.kelas_id <> r.kelas_id;

-- Jika ada dua nomor BAB sama di semester berpasangan, hasil tidak boleh ada.
SELECT b.angkatan_id,b.mapel_id,b.kurikulum_id,b.tahun_ajaran_id,
       b.kelas_id,b.nomor_bab,COUNT(*) AS jumlah_duplikat
FROM bab b
GROUP BY b.angkatan_id,b.mapel_id,b.kurikulum_id,b.tahun_ajaran_id,
         b.kelas_id,b.nomor_bab
HAVING COUNT(*) > 1;

-- Ringkas BAB yang sudah ditambahkan, tanpa data pribadi siswa.
SELECT p.angkatan_id,p.mapel_id,p.semester,b.nomor_bab,
       b.nama_bab,b.deskripsi_bab
FROM bab b
JOIN paket_semester p ON p.id=b.paket_semester_id
ORDER BY p.angkatan_id,p.mapel_id,p.semester,b.nomor_bab;
